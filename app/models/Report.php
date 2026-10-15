<?php
/**
 * SIGAP-POLDA: Report Model (Manajemen Laporan Pengaduan)
 */

namespace App\Models;

use App\Core\Model;
use App\Config\Constants;

class Report extends Model
{
    /**
     * Dapatkan Seluruh Kategori Perkara
     */
    public function getCategories(): array
    {
        return $this->fetchAll("SELECT * FROM report_categories ORDER BY id ASC");
    }

    /**
     * Dapatkan Laporan Berdasarkan Nomor Tiket
     */
    public function findByTicket(string $ticketNumber): ?array
    {
        return $this->fetch(
            "SELECT r.*, c.name AS category_name, c.sla_hours, 
                    s.name AS satker_name, s.code AS satker_code,
                    inv.name AS investigator_name, inv.rank AS investigator_rank, inv.phone AS investigator_phone,
                    f.rating_stars, f.comments AS feedback_comments
             FROM reports r
             JOIN report_categories c ON r.category_id = c.id
             LEFT JOIN satkers s ON r.current_satker_id = s.id
             LEFT JOIN users inv ON r.assigned_investigator_id = inv.id
             LEFT JOIN report_feedbacks f ON r.id = f.report_id
             WHERE r.ticket_number = ? LIMIT 1",
            [$ticketNumber]
        );
    }

    /**
     * Dapatkan Laporan Berdasarkan ID
     */
    public function findById(int $id): ?array
    {
        return $this->fetch(
            "SELECT r.*, c.name AS category_name, c.sla_hours, 
                    s.name AS satker_name, s.code AS satker_code,
                    inv.name AS investigator_name, inv.rank AS investigator_rank, inv.phone AS investigator_phone,
                    u.name AS reporter_account_name
             FROM reports r
             JOIN report_categories c ON r.category_id = c.id
             LEFT JOIN satkers s ON r.current_satker_id = s.id
             LEFT JOIN users inv ON r.assigned_investigator_id = inv.id
             LEFT JOIN users u ON r.reporter_id = u.id
             WHERE r.id = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Dapatkan Lampiran Berkas Laporan
     */
    public function getAttachments(int $reportId): array
    {
        return $this->fetchAll(
            "SELECT * FROM report_attachments WHERE report_id = ? ORDER BY id ASC",
            [$reportId]
        );
    }

    public function findAttachment(int $id): ?array
    {
        return $this->fetch("SELECT * FROM report_attachments WHERE id = ? LIMIT 1", [$id]);
    }

    public function findCaseMessage(int $id): ?array
    {
        return $this->fetch("SELECT * FROM case_messages WHERE id = ? LIMIT 1", [$id]);
    }

    /**
     * Dapatkan Timeline Riwayat Laporan (Filter Catatan Publik vs Internal)
     */
    public function getTimeline(int $reportId, bool $includeInternal = false): array
    {
        $sql = "SELECT t.*, u.name AS actor_name, u.rank AS actor_rank, u.role AS actor_role
                FROM report_timeline t
                JOIN users u ON t.actor_id = u.id
                WHERE t.report_id = ? ";

        if (!$includeInternal) {
            $sql .= "AND (t.public_notes IS NOT NULL AND t.public_notes != '') ";
        }

        $sql .= "ORDER BY t.created_at ASC, t.id ASC";

        return $this->fetchAll($sql, [$reportId]);
    }

    /**
     * Buat Laporan Baru dari Portal Warga
     */
    public function createReport(array $data): string
    {
        $this->beginTransaction();
        try {
            // Generate Nomor Tiket: LP-POLDA-YYYYMM-XXXX
            $ticketNumber = $this->nextTicketNumber(date('Ym'));

            // PIN Keamanan 6 Digit
            $securityPin = (string)random_int(100000, 999999);

            // Hitung SLA Deadline berdasarkan kategori
            $cat = $this->fetch("SELECT sla_hours FROM report_categories WHERE id = ?", [$data['category_id']]);
            $slaHours = $cat ? (int)$cat['sla_hours'] : 48;
            $slaDeadline = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));

            $sql = "INSERT INTO reports (
                        ticket_number, security_pin, source_channel, social_feed_id, social_post_url,
                        reporter_id, is_anonymous, reporter_name_raw, reporter_nik_raw, reporter_phone,
                        category_id, title, description, incident_date, incident_time, incident_location,
                        latitude, longitude, status, urgency_level, sla_deadline
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, 'SUBMITTED', ?, ?
                    )";

            $this->execute($sql, [
                $ticketNumber,
                $securityPin,
                $data['source_channel'] ?? Constants::CHANNEL_WEB,
                $data['social_feed_id'] ?? null,
                $data['social_post_url'] ?? null,
                $data['reporter_id'] ?? null,
                $data['is_anonymous'] ?? 0,
                $data['reporter_name_raw'],
                $data['reporter_nik_raw'] ?? null,
                $data['reporter_phone'],
                $data['category_id'],
                $data['title'],
                $data['description'],
                $data['incident_date'],
                $data['incident_time'] ?? date('H:i:s'),
                $data['incident_location'],
                $data['latitude'] ?? null,
                $data['longitude'] ?? null,
                $data['urgency_level'] ?? 'NORMAL',
                $slaDeadline
            ]);

            $reportId = $this->lastInsertId();

            // Simpan Lampiran jika ada
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $att) {
                    $this->execute(
                        "INSERT INTO report_attachments (report_id, uploader_id, uploader_type, file_type, file_path, file_name_original, file_size_kb, caption)
                         VALUES (?, ?, 'REPORTER', ?, ?, ?, ?, ?)",
                        [
                            $reportId,
                            $data['reporter_id'] ?? null,
                            $att['file_type'] ?? 'IMAGE',
                            $att['file_path'],
                            $att['file_name_original'] ?? 'Lampiran',
                            $att['file_size_kb'] ?? 0,
                            $att['caption'] ?? 'Bukti dari pelapor'
                        ]
                    );
                }
            }

            // Catat Timeline Pertama (Laporan Terkirim)
            $actorId = $data['reporter_id'] ?? 1; // Default sistem / reporter
            $this->execute(
                "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, public_notes, internal_notes)
                 VALUES (?, ?, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polda.', 'Laporan baru masuk melalui portal publik.')",
                [$reportId, $actorId]
            );

            // Jika laporan berasal dari social_feed, update status social_feed
            if (!empty($data['social_feed_id'])) {
                $this->execute(
                    "UPDATE social_feeds SET status = 'CONVERTED_TO_TICKET', converted_report_id = ? WHERE id = ?",
                    [$reportId, $data['social_feed_id']]
                );
            }

            $this->commit();
            return $ticketNumber;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Ambil nomor urut tiket berikutnya secara atomik (aman untuk laporan bersamaan).
     * Baris sequence periode dikunci hingga transaksi selesai; bila belum ada,
     * diinisialisasi dari nomor tiket tertinggi yang sudah tersimpan.
     * Wajib dipanggil di dalam transaksi pembuatan laporan.
     */
    private function nextTicketNumber(string $period): string
    {
        $prefix = "LP-POLDA-{$period}-";

        $this->execute(
            "INSERT INTO ticket_sequences (period, last_seq)
             SELECT ?, LAST_INSERT_ID(COALESCE(MAX(CAST(SUBSTRING(ticket_number, ?) AS UNSIGNED)), 0) + 1)
             FROM reports WHERE ticket_number LIKE ?
             ON DUPLICATE KEY UPDATE last_seq = LAST_INSERT_ID(last_seq + 1)",
            [$period, strlen($prefix) + 1, $prefix . '%']
        );

        $seq = (int)$this->db->query("SELECT LAST_INSERT_ID()")->fetchColumn();
        return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Dapatkan Daftar Laporan dengan Filter Backoffice
     */
    public function getFilteredReports(array $filters = []): array
    {
        $sql = "SELECT r.*, c.name AS category_name, s.name AS satker_name, 
                       inv.name AS investigator_name, inv.rank AS investigator_rank
                FROM reports r
                JOIN report_categories c ON r.category_id = c.id
                LEFT JOIN satkers s ON r.current_satker_id = s.id
                LEFT JOIN users inv ON r.assigned_investigator_id = inv.id
                WHERE 1=1 ";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= "AND r.status = ? ";
            $params[] = $filters['status'];
        }

        if (!empty($filters['satker_id'])) {
            $sql .= "AND r.current_satker_id = ? ";
            $params[] = $filters['satker_id'];
        }

        if (!empty($filters['investigator_id'])) {
            $sql .= "AND r.assigned_investigator_id = ? ";
            $params[] = $filters['investigator_id'];
        }

        if (!empty($filters['is_kapolda_attention'])) {
            $sql .= "AND r.is_kapolda_attention = 1 ";
        }

        if (!empty($filters['channel'])) {
            $sql .= "AND r.source_channel = ? ";
            $params[] = $filters['channel'];
        }

        if (!empty($filters['search'])) {
            $sql .= "AND (r.ticket_number LIKE ? OR r.title LIKE ? OR r.reporter_name_raw LIKE ?) ";
            $kw = "%{$filters['search']}%";
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        $sql .= "ORDER BY r.is_kapolda_attention DESC, r.created_at DESC LIMIT 100";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Disposisi Laporan oleh SPKT ke Satuan Kerja
     */
    public function dispatchReport(int $reportId, int $satkerId, int $actorId, string $notes): bool
    {
        $this->beginTransaction();
        try {
            $report = $this->findById($reportId);
            $satker = $this->fetch("SELECT name FROM satkers WHERE id = ?", [$satkerId]);
            $satkerName = $satker['name'] ?? 'Satker Terkait';

            $this->execute(
                "UPDATE reports SET status = 'DISPATCHED', current_satker_id = ? WHERE id = ?",
                [$satkerId, $reportId]
            );

            $this->execute(
                "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, public_notes, internal_notes)
                 VALUES (?, ?, ?, 'DISPATCHED', 'Disposisi ke Satker Terkait', ?, ?)",
                [
                    $reportId,
                    $actorId,
                    $report['status'],
                    "Laporan telah diverifikasi oleh SPKT Polda dan didisposisikan ke {$satkerName} untuk tindak lanjut.",
                    "Catatan Disposisi SPKT: {$notes}"
                ]
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * Penunjukan Penyidik oleh Kanit/Kasatker
     */
    public function assignInvestigator(int $reportId, int $investigatorId, int $actorId, string $sprintgasNumber): bool
    {
        $this->beginTransaction();
        try {
            $report = $this->findById($reportId);
            $inv = $this->fetch("SELECT name, rank FROM users WHERE id = ?", [$investigatorId]);
            $invTitle = "{$inv['rank']} {$inv['name']}";

            $this->execute(
                "UPDATE reports SET status = 'ASSIGNED', assigned_investigator_id = ? WHERE id = ?",
                [$investigatorId, $reportId]
            );

            $this->execute(
                "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, public_notes, internal_notes)
                 VALUES (?, ?, ?, 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', ?, ?)",
                [
                    $reportId,
                    $actorId,
                    $report['status'],
                    "Penyelidikan kasus ini telah dipercayakan kepada Penyidik {$invTitle}.",
                    "Sprintgas No: {$sprintgasNumber}. Penugasan kepada {$invTitle}."
                ]
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * Terbitkan SP2HP Elektronik oleh Penyidik
     */
    public function issueSp2hp(int $reportId, int $actorId, string $sp2hpNumber, string $publicNotes, string $internalNotes, string $newStatus = 'INVESTIGATING'): bool
    {
        $this->beginTransaction();
        try {
            $report = $this->findById($reportId);

            $this->execute(
                "UPDATE reports SET status = ?, closed_at = IF(? = 'CLOSED', NOW(), closed_at) WHERE id = ?",
                [$newStatus, $newStatus, $reportId]
            );

            $this->execute(
                "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, public_notes, internal_notes, is_sp2hp, sp2hp_number)
                 VALUES (?, ?, ?, ?, 'Penerbitan SP2HP Elektronik', ?, ?, 1, ?)",
                [
                    $reportId,
                    $actorId,
                    $report['status'],
                    $newStatus,
                    $publicNotes,
                    $internalNotes,
                    $sp2hpNumber
                ]
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * Selesaikan Laporan dengan Berita Acara (BAP)
     */
    public function closeReport(int $reportId, int $actorId, string $resolutionNotes): bool
    {
        $this->beginTransaction();
        try {
            $report = $this->findById($reportId);

            $this->execute(
                "UPDATE reports SET status = 'CLOSED', closed_at = NOW() WHERE id = ?",
                [$reportId]
            );

            $this->execute(
                "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, public_notes, internal_notes, is_sp2hp, sp2hp_number)
                 VALUES (?, ?, ?, 'CLOSED', 'Penanganan Kasus Selesai', ?, ?, 1, 'SP2HP/FINAL/CLOSED')",
                [
                    $reportId,
                    $actorId,
                    $report['status'],
                    "Perkara ini telah berhasil diselesaikan oleh tim kepolisian. Terima kasih atas partisipasi aktif masyarakat. Silakan luangkan waktu sejenak untuk mengisi Survei Kepuasan Masyarakat (SKM).",
                    "Catatan Penutupan Kasus (BAP): {$resolutionNotes}"
                ]
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * Tolak Laporan oleh SPKT (Hoax / Tidak Memenuhi Unsur Pidana)
     */
    public function rejectReport(int $reportId, int $actorId, string $reason): bool
    {
        $this->beginTransaction();
        try {
            $report = $this->findById($reportId);

            $this->execute(
                "UPDATE reports SET status = 'REJECTED', rejection_reason = ? WHERE id = ?",
                [$reason, $reportId]
            );

            $this->execute(
                "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, public_notes, internal_notes)
                 VALUES (?, ?, ?, 'REJECTED', 'Laporan Tidak Dapat Diproses', ?, ?)",
                [
                    $reportId,
                    $actorId,
                    $report['status'],
                    "Laporan tidak dapat ditindaklanjuti dengan alasan: {$reason}",
                    "Ditolak oleh SPKT: {$reason}"
                ]
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * Berikan Atensi Kapolda (Instruksi Prioritas Bintang Merah)
     */
    public function giveKapoldaAttention(int $reportId, int $kapoldaId, string $directiveText): bool
    {
        $this->beginTransaction();
        try {
            $report = $this->findById($reportId);
            $targetSatkerId = $report['current_satker_id'] ?? 2; // Default Ditreskrimum jika belum terdisposisi

            $this->execute(
                "UPDATE reports SET is_kapolda_attention = 1, kapolda_attention_at = NOW(), urgency_level = 'HIGH' WHERE id = ?",
                [$reportId]
            );

            $this->execute(
                "INSERT INTO kapolda_directives (report_id, created_by, directive_text, target_satker_id, status)
                 VALUES (?, ?, ?, ?, 'PENDING')",
                [$reportId, $kapoldaId, $directiveText, $targetSatkerId]
            );

            $this->execute(
                "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, public_notes, internal_notes)
                 VALUES (?, ?, ?, ?, 'Instruksi Khusus: Atensi Kapolda', 'Laporan Anda mendapatkan atensi khusus langsung dari Kapolda untuk penanganan prioritas cepat.', ?)",
                [
                    $reportId,
                    $kapoldaId,
                    $report['status'],
                    $report['status'],
                    "ATENSI KAPOLDA: {$directiveText}"
                ]
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * Data Titik GIS untuk Heatmap Kriminalitas Kapolda
     */
    public function getHeatmapPoints(): array
    {
        return $this->fetchAll(
            "SELECT id, ticket_number, title, category_id, status, is_kapolda_attention,
                    latitude, longitude, incident_location, created_at
             FROM reports
             WHERE latitude IS NOT NULL AND longitude IS NOT NULL
             ORDER BY created_at DESC LIMIT 500"
        );
    }

    /**
     * Data Agregat Statistik Executive Dashboard
     */
    public function getExecutiveStats(): array
    {
        $totalReports = (int)($this->fetch("SELECT COUNT(*) AS c FROM reports")['c'] ?? 0);
        $closedReports = (int)($this->fetch("SELECT COUNT(*) AS c FROM reports WHERE status = 'CLOSED'")['c'] ?? 0);
        $investigating = (int)($this->fetch("SELECT COUNT(*) AS c FROM reports WHERE status IN ('ASSIGNED', 'INVESTIGATING')")['c'] ?? 0);
        $kapoldaAttention = (int)($this->fetch("SELECT COUNT(*) AS c FROM reports WHERE is_kapolda_attention = 1")['c'] ?? 0);
        $socialReports = (int)($this->fetch(
            "SELECT COUNT(*) AS c FROM reports WHERE source_channel IN (?, ?, ?, ?)",
            [Constants::CHANNEL_TWITTER, Constants::CHANNEL_IG, Constants::CHANNEL_FB, Constants::CHANNEL_TIKTOK]
        )['c'] ?? 0);

        $clearanceRate = $totalReports > 0 ? round(($closedReports / $totalReports) * 100, 1) : 0.0;

        // Rata-rata rating SKM (null bila belum ada feedback, jangan tampilkan angka fiktif)
        $avgFeedback = $this->fetch("SELECT AVG(rating_stars) AS a FROM report_feedbacks")['a'] ?? null;

        return [
            'total_reports' => $totalReports,
            'closed_reports' => $closedReports,
            'investigating' => $investigating,
            'kapolda_attention' => $kapoldaAttention,
            'social_reports' => $socialReports,
            'clearance_rate' => $clearanceRate,
            'avg_rating' => $avgFeedback !== null ? round((float)$avgFeedback, 1) : null
        ];
    }

    /**
     * Dapatkan Seluruh Percakapan Klarifikasi Perkara (Polisi Bersama Masyarakat)
     */
    public function getCaseMessages(int $reportId): array
    {
        return $this->fetchAll(
            "SELECT m.*, u.rank, u.role
             FROM case_messages m
             LEFT JOIN users u ON m.sender_id = u.id
             WHERE m.report_id = ?
             ORDER BY m.created_at ASC",
            [$reportId]
        );
    }

    /**
     * Kirim Pesan Klarifikasi Dua Arah (Warga / Penyidik)
     */
    public function addCaseMessage(
        int $reportId,
        string $senderType,
        ?int $senderId,
        string $senderName,
        string $messageText,
        ?string $attachmentPath = null
    ): bool {
        return $this->execute(
            "INSERT INTO case_messages (report_id, sender_type, sender_id, sender_name, message_text, attachment_path, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $reportId,
                $senderType,
                $senderId,
                $senderName,
                trim($messageText),
                $attachmentPath
            ]
        );
    }
}

