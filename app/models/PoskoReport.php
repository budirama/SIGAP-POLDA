<?php
/**
 * SIGAP-POLRI: Laporan Posko (draft, isian dinamis, lampiran, pengiriman & penomoran)
 */

namespace App\Models;

use App\Core\Model;

class PoskoReport extends Model
{
    public const STATUS_LABELS = [
        'DRAFT'          => 'Draft',
        'SUBMITTED'      => 'Dikirim',
        'IN_REVIEW'      => 'Dalam verifikasi',
        'NEEDS_REVISION' => 'Perlu perbaikan',
        'APPROVED'       => 'Disetujui',
        'FORWARDED'      => 'Diteruskan',
        'CANCELLED'      => 'Dibatalkan',
    ];
    // Status yang masih dapat diubah oleh unit pembuat
    public const EDITABLE_STATUSES = ['DRAFT', 'NEEDS_REVISION'];
    public const MAX_ATTACHMENTS = 10;

    private const GENERAL_COLUMNS = [
        'period_start', 'period_end', 'period_key', 'event_time', 'region', 'situation_summary', 'description',
        'obstacles', 'follow_up', 'assistance_needed', 'priority', 'reference_ticket', 'deadline_at',
    ];

    public function find(int $id): ?array
    {
        return $this->fetch(
            "SELECT r.*, t.code AS type_code, t.name AS type_name, t.kind AS type_kind, t.period_unit,
                    t.requires_approval, t.allows_nil,
                    s.name AS satker_name, s.code AS satker_code, s.level AS satker_level, s.parent_id AS satker_parent_id,
                    h.name AS holder_name, h.level AS holder_level, h.parent_id AS holder_parent_id,
                    u.name AS creator_name, u.rank AS creator_rank
             FROM posko_reports r
             JOIN posko_report_types t ON t.id = r.report_type_id
             JOIN satkers s ON s.id = r.satker_id
             LEFT JOIN satkers h ON h.id = r.holder_satker_id
             LEFT JOIN users u ON u.id = r.created_by
             WHERE r.id = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Daftar laporan dalam cakupan wilayah. Draft hanya terlihat oleh unitnya sendiri ($ownUnitId).
    /**
     * Helper pembuat klausa WHERE dan parameter untuk daftar & ekspor laporan
     *
     * @param int[] $scopeIds
     * @return array{0: string, 1: array}
     */
    private function buildScopeQuery(array $scopeIds, ?int $ownUnitId, array $filters): array
    {
        $scopeIds = array_values(array_filter(array_map('intval', $scopeIds)));
        if (!$scopeIds) {
            return ['1 = 0', []];
        }

        $where = [
            "r.satker_id IN (" . implode(',', $scopeIds) . ")",
            // Draft (termasuk draft yang dibatalkan sebelum pernah dikirim) hanya untuk unit pembuat
            "(r.report_number IS NOT NULL OR r.satker_id = ?)",
        ];
        $params = [$ownUnitId ?? 0];

        if (!empty($filters['type'])) {
            $where[] = "r.report_type_id = ?";
            $params[] = (int)$filters['type'];
        }
        if (!empty($filters['status']) && isset(self::STATUS_LABELS[$filters['status']])) {
            $where[] = "r.status = ?";
            $params[] = $filters['status'];
        }
        if (($filters['kind'] ?? '') === 'recap') {
            $where[] = "r.is_recap = 1";
        } elseif (($filters['kind'] ?? '') === 'nil') {
            $where[] = "r.is_nil = 1";
        } elseif (($filters['kind'] ?? '') === 'source') {
            $where[] = "r.is_recap = 0";
        }
        if (!empty($filters['satker']) && in_array((int)$filters['satker'], $scopeIds, true)) {
            $where[] = "r.satker_id = ?";
            $params[] = (int)$filters['satker'];
        }
        if (!empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', $filters['month'])) {
            $where[] = "DATE_FORMAT(COALESCE(r.period_start, r.event_time, r.created_at), '%Y-%m') = ?";
            $params[] = $filters['month'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(r.report_number LIKE ? OR r.situation_summary LIKE ? OR s.name LIKE ?)";
            $kw = '%' . $filters['search'] . '%';
            array_push($params, $kw, $kw, $kw);
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Daftar laporan dalam cakupan wilayah. Draft hanya terlihat oleh unitnya sendiri ($ownUnitId).
     *
     * @param int[] $scopeIds
     */
    public function paginate(array $scopeIds, ?int $ownUnitId, array $filters, int $page, int $perPage = 25): array
    {
        [$sqlWhere, $params] = $this->buildScopeQuery($scopeIds, $ownUnitId, $filters);
        if ($sqlWhere === '1 = 0') {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'perPage' => $perPage];
        }

        $total = (int)($this->fetch(
            "SELECT COUNT(*) AS n FROM posko_reports r JOIN satkers s ON s.id = r.satker_id WHERE {$sqlWhere}",
            $params
        )['n'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT r.id, r.report_number, r.status, r.priority, r.is_nil, r.is_recap, r.needs_review, r.period_start, r.period_end, r.event_time,
                    r.deadline_at, r.submitted_at, r.updated_at, r.situation_summary, r.satker_id,
                    t.name AS type_name, t.period_unit, s.name AS satker_name, s.level AS satker_level
             FROM posko_reports r
             JOIN posko_report_types t ON t.id = r.report_type_id
             JOIN satkers s ON s.id = r.satker_id
             WHERE {$sqlWhere}
             ORDER BY COALESCE(r.submitted_at, r.updated_at) DESC, r.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    /**
     * Ambil seluruh laporan sesuai filter & batas wilayah untuk ekspor Excel / cetak daftar.
     * Menerapkan pembatasan scopeSatkerIds() dan filter yang persis sama dengan tampilan.
     *
     * @param int[] $scopeIds
     */
    public function allForExport(array $scopeIds, ?int $ownUnitId, array $filters, int $limit = 5000): array
    {
        [$sqlWhere, $params] = $this->buildScopeQuery($scopeIds, $ownUnitId, $filters);
        if ($sqlWhere === '1 = 0') {
            return [];
        }

        return $this->fetchAll(
            "SELECT r.id, r.report_number, r.status, r.priority, r.is_nil, r.is_recap, r.needs_review,
                    r.period_start, r.period_end, r.period_key, r.event_time, r.deadline_at, r.submitted_at,
                    r.created_at, r.updated_at, r.region, r.situation_summary, r.description, r.obstacles,
                    r.follow_up, r.assistance_needed, r.reference_ticket, r.version,
                    r.recap_computed_at, r.satker_id,
                    t.code AS type_code, t.name AS type_name, t.kind AS type_kind, t.period_unit,
                    s.name AS satker_name, s.code AS satker_code, s.level AS satker_level,
                    h.name AS holder_name,
                    u.name AS creator_name, u.rank AS creator_rank
             FROM posko_reports r
             JOIN posko_report_types t ON t.id = r.report_type_id
             JOIN satkers s ON s.id = r.satker_id
             LEFT JOIN satkers h ON h.id = r.holder_satker_id
             LEFT JOIN users u ON u.id = r.created_by
             WHERE {$sqlWhere}
             ORDER BY COALESCE(r.submitted_at, r.updated_at) DESC, r.id DESC
             LIMIT {$limit}",
            $params
        );
    }

    public function existsForPeriod(int $satkerId, int $typeId, string $periodStart, int $exceptId = 0, bool $isRecap = false): bool
    {
        return $this->fetch(
            "SELECT id FROM posko_reports
             WHERE satker_id = ? AND report_type_id = ? AND period_key = ? AND is_recap = ? AND id <> ? LIMIT 1",
            [$satkerId, $typeId, $periodStart, $isRecap ? 1 : 0, $exceptId]
        ) !== null;
    }

    /**
     * Rekap aktif unit untuk jenis & periode (bila ada)
     */
    public function findRecap(int $satkerId, int $typeId, string $periodStart): ?array
    {
        return $this->fetch(
            "SELECT id, status FROM posko_reports WHERE satker_id = ? AND report_type_id = ? AND period_key = ? AND is_recap = 1 LIMIT 1",
            [$satkerId, $typeId, $periodStart]
        );
    }

    /**
     * Buat draft beserta nilai isiannya
     */
    public function createDraft(int $typeId, int $satkerId, array $general, array $values, int $userId,
                                bool $isRecap = false, bool $isNil = false): int
    {
        $this->beginTransaction();
        try {
            $cols = array_merge(['report_type_id', 'satker_id', 'status', 'is_recap', 'is_nil', 'created_by', 'updated_by'], self::GENERAL_COLUMNS);
            $params = array_merge([$typeId, $satkerId, 'DRAFT', $isRecap ? 1 : 0, $isNil ? 1 : 0, $userId, $userId], $this->generalParams($general));
            $this->execute(
                "INSERT INTO posko_reports (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ")",
                $params
            );
            $id = $this->lastInsertId();
            $this->replaceValues($id, $values);
            $this->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Perbarui laporan yang masih dapat diubah (atomik terhadap status)
     */
    public function updateEditable(int $id, array $general, array $values, int $userId): bool
    {
        $this->beginTransaction();
        try {
            // Status diperiksa pada baris terkunci (bukan dari jumlah baris berubah: UPDATE dengan nilai
            // identik di MySQL menghasilkan 0 baris berubah walau laporan masih dapat diubah)
            $row = $this->lockRow($id);
            if (!$row || !in_array($row['status'], self::EDITABLE_STATUSES, true)) {
                $this->rollBack();
                return false;
            }
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", self::GENERAL_COLUMNS));
            $this->execute(
                "UPDATE posko_reports SET {$set}, updated_by = ?, updated_at = NOW() WHERE id = ?",
                array_merge($this->generalParams($general), [$userId, $id])
            );
            $this->replaceValues($id, $values);
            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Perbarui narasi/analisis rekap; nilai agregat hanya diubah lewat penghitungan ulang
     */
    public function updateNarrative(int $id, array $narrative, int $userId): bool
    {
        $cols = ['region', 'situation_summary', 'description', 'obstacles', 'follow_up', 'assistance_needed', 'priority'];
        $set = implode(', ', array_map(fn($c) => "{$c} = ?", $cols));
        $params = array_map(fn($c) => ($narrative[$c] ?? null) === '' ? null : ($narrative[$c] ?? null), $cols);
        $this->beginTransaction();
        try {
            $row = $this->lockRow($id);
            if (!$row || !(int)$row['is_recap'] || !in_array($row['status'], self::EDITABLE_STATUSES, true)) {
                $this->rollBack();
                return false;
            }
            $this->execute("UPDATE posko_reports SET {$set}, updated_by = ?, updated_at = NOW() WHERE id = ?", array_merge($params, [$userId, $id]));
            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * @return array<string, array{value_number: ?string, value_text: ?string}>
     */
    public function values(int $id): array
    {
        $out = [];
        foreach ($this->fetchAll("SELECT field_key, value_number, value_text FROM posko_report_values WHERE report_id = ?", [$id]) as $row) {
            $out[$row['field_key']] = $row;
        }
        return $out;
    }

    /**
     * Kirim (atau kirim ulang setelah perbaikan) ke unit induk langsung: nomor unik diberikan sekali,
     * waktu kirim tercatat, isi disimpan sebagai snapshot versi. Null bila status tidak sah.
     */
    public function submit(int $id, int $userId, int $actorSatkerId, int $holderSatkerId): ?string
    {
        $this->beginTransaction();
        try {
            $row = $this->lockRow($id);
            if (!$row || !in_array($row['status'], self::EDITABLE_STATUSES, true)) {
                $this->rollBack();
                return null;
            }
            $code = $this->fetch("SELECT code FROM satkers WHERE id = ?", [$row['satker_id']])['code'];
            $number = $row['report_number'] ?: $this->nextNumber($code);

            $this->execute(
                "UPDATE posko_reports
                 SET status = 'SUBMITTED', report_number = ?, submitted_at = NOW(), holder_satker_id = ?,
                     reviewed_by = NULL, approved_by = NULL, approved_at = NULL, forwarded_at = NULL, updated_by = ?
                 WHERE id = ?",
                [$number, $holderSatkerId, $userId, $id]
            );
            $this->snapshot($id, (int)$row['version'], $userId);
            $this->addHistory($id, $userId, $actorSatkerId, $row['status'] === 'DRAFT' ? 'submit' : 'resubmit',
                $row['status'], 'SUBMITTED', $row['holder_satker_id'], $holderSatkerId, (int)$row['version'], null);
            $this->commit();
            return $number;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Transisi status atomik: baris dikunci, status asal & penjaga diperiksa, lalu satu baris riwayat dicatat.
     *
     * @param string[] $from    status asal yang sah
     * @param callable|null $guard fn(array $row): bool — pemeriksaan tambahan atas baris terkunci
     * @param array|callable $sets kolom tambahan, atau fn(array $row): array
     * @return array|null baris sebelum transisi, null bila ditolak
     */
    public function transition(int $id, string $action, array $from, string $to, int $actorId, int $actorSatkerId,
                               array|callable $sets = [], ?string $notes = null, ?callable $guard = null): ?array
    {
        $this->beginTransaction();
        try {
            $row = $this->lockRow($id);
            if (!$row || !in_array($row['status'], $from, true) || ($guard && !$guard($row))) {
                $this->rollBack();
                return null;
            }
            $sets = is_callable($sets) ? $sets($row) : $sets;
            $sets['status'] = $to;
            $sets['updated_by'] = $actorId;

            $assign = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($sets)));
            $this->execute("UPDATE posko_reports SET {$assign} WHERE id = ?", array_merge(array_values($sets), [$id]));

            $holderTo = array_key_exists('holder_satker_id', $sets) ? $sets['holder_satker_id'] : $row['holder_satker_id'];
            $this->addHistory($id, $actorId, $actorSatkerId, $action, $row['status'], $to,
                $row['holder_satker_id'], $holderTo, (int)($sets['version'] ?? $row['version']), $notes);
            $this->commit();
            return $row;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Catat penerimaan laporan oleh unit pemegang (tanpa mengubah status), sekali per unit per versi
     */
    public function acknowledge(int $id, int $actorId, int $actorSatkerId): bool
    {
        $this->beginTransaction();
        try {
            $row = $this->lockRow($id);
            if (!$row || $row['status'] !== 'FORWARDED' || (int)$row['holder_satker_id'] !== $actorSatkerId
                || $this->acknowledgedBy($id, $actorSatkerId, (int)$row['version'])) {
                $this->rollBack();
                return false;
            }
            $this->addHistory($id, $actorId, $actorSatkerId, 'acknowledge', 'FORWARDED', 'FORWARDED',
                $row['holder_satker_id'], $row['holder_satker_id'], (int)$row['version'], null);
            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function acknowledgedBy(int $id, int $satkerId, int $version): bool
    {
        return $this->fetch(
            "SELECT id FROM posko_report_history WHERE report_id = ? AND action = 'acknowledge' AND actor_satker_id = ? AND version = ? LIMIT 1",
            [$id, $satkerId, $version]
        ) !== null;
    }

    public function history(int $id): array
    {
        return $this->fetchAll(
            "SELECT h.*, u.name AS actor_name, u.rank AS actor_rank, sa.name AS actor_satker_name,
                    sf.name AS holder_from_name, st.name AS holder_to_name
             FROM posko_report_history h
             LEFT JOIN users u ON u.id = h.actor_id
             LEFT JOIN satkers sa ON sa.id = h.actor_satker_id
             LEFT JOIN satkers sf ON sf.id = h.holder_from
             LEFT JOIN satkers st ON st.id = h.holder_to
             WHERE h.report_id = ? ORDER BY h.id ASC",
            [$id]
        );
    }

    /**
     * Catatan riwayat terbaru untuk aksi tertentu (mis. catatan koreksi saat dikembalikan)
     */
    public function lastNote(int $id, array $actions): ?array
    {
        $in = implode(',', array_fill(0, count($actions), '?'));
        return $this->fetch(
            "SELECT h.*, u.name AS actor_name, u.rank AS actor_rank, sa.name AS actor_satker_name
             FROM posko_report_history h
             LEFT JOIN users u ON u.id = h.actor_id
             LEFT JOIN satkers sa ON sa.id = h.actor_satker_id
             WHERE h.report_id = ? AND h.action IN ({$in}) ORDER BY h.id DESC LIMIT 1",
            array_merge([$id], $actions)
        );
    }

    public function versions(int $id): array
    {
        return $this->fetchAll(
            "SELECT v.*, u.name AS changed_by_name FROM posko_report_versions v
             LEFT JOIN users u ON u.id = v.changed_by
             WHERE v.report_id = ? ORDER BY v.version ASC",
            [$id]
        );
    }

    /**
     * Antrean tindakan unit: laporan yang sedang dipegang unit ini. Laporan mendesak (prioritas tinggi/kritis) di atas.
     */
    public function inbox(int $holderSatkerId): array
    {
        return $this->fetchAll(
            "SELECT r.id, r.report_number, r.status, r.priority, r.period_start, r.period_end, r.event_time, r.deadline_at,
                    r.submitted_at, r.forwarded_at, r.version, r.situation_summary, r.satker_id, r.is_recap, r.is_nil, r.needs_review,
                    t.name AS type_name, t.period_unit, t.kind AS type_kind, s.name AS satker_name, s.parent_id AS satker_parent_id
             FROM posko_reports r
             JOIN posko_report_types t ON t.id = r.report_type_id
             JOIN satkers s ON s.id = r.satker_id
             WHERE r.holder_satker_id = ? AND r.status IN ('SUBMITTED', 'IN_REVIEW', 'APPROVED', 'FORWARDED')
             ORDER BY (r.priority = 'CRITICAL') DESC, (r.priority = 'HIGH') DESC,
                      COALESCE(r.forwarded_at, r.submitted_at) ASC",
            [$holderSatkerId]
        );
    }

    // ---------------------------------------------------------------- Lampiran

    public function attachments(int $reportId): array
    {
        return $this->fetchAll(
            "SELECT a.*, u.name AS uploader_name FROM posko_report_attachments a
             LEFT JOIN users u ON u.id = a.uploaded_by
             WHERE a.report_id = ? ORDER BY a.id ASC",
            [$reportId]
        );
    }

    public function countAttachments(int $reportId): int
    {
        return (int)($this->fetch("SELECT COUNT(*) AS n FROM posko_report_attachments WHERE report_id = ?", [$reportId])['n'] ?? 0);
    }

    public function addAttachment(int $reportId, array $stored, string $originalName, int $userId): int
    {
        $this->execute(
            "INSERT INTO posko_report_attachments (report_id, file_path, file_name_original, mime, size_bytes, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$reportId, $stored['file_path'], mb_substr($originalName, 0, 255), $stored['mime'], (int)$stored['size'], $userId]
        );
        return $this->lastInsertId();
    }

    public function findAttachment(int $id): ?array
    {
        return $this->fetch("SELECT * FROM posko_report_attachments WHERE id = ? LIMIT 1", [$id]);
    }

    public function deleteAttachment(int $id): void
    {
        $this->execute("DELETE FROM posko_report_attachments WHERE id = ?", [$id]);
    }

    // ---------------------------------------------------------------- Internal

    private function lockRow(int $id): ?array
    {
        return $this->fetch("SELECT * FROM posko_reports WHERE id = ? FOR UPDATE", [$id]);
    }

    private function addHistory(int $reportId, ?int $actorId, ?int $actorSatkerId, string $action, ?string $from, ?string $to,
                                $holderFrom, $holderTo, int $version, ?string $notes): void
    {
        $this->execute(
            "INSERT INTO posko_report_history (report_id, actor_id, actor_satker_id, action, status_from, status_to,
                holder_from, holder_to, version, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$reportId, $actorId, $actorSatkerId, $action, $from, $to,
             $holderFrom !== null ? (int)$holderFrom : null, $holderTo !== null ? (int)$holderTo : null, $version, $notes]
        );
    }

    /**
     * Simpan isi laporan saat ini sebagai snapshot versi (data umum, nilai isian, daftar lampiran)
     */
    private function snapshot(int $reportId, int $version, int $userId): void
    {
        $row = $this->fetch("SELECT * FROM posko_reports WHERE id = ?", [$reportId]);
        $data = [
            'general' => array_intersect_key($row, array_flip(array_merge(self::GENERAL_COLUMNS, ['priority']))),
            'values' => array_map(fn($v) => $v['value_number'] ?? $v['value_text'], $this->values($reportId)),
            'attachments' => array_column($this->attachments($reportId), 'file_name_original'),
        ];
        $reason = $version > 1 ? ($this->fetch(
            "SELECT notes FROM posko_report_history WHERE report_id = ? AND action = 'revise' AND version = ? ORDER BY id DESC LIMIT 1",
            [$reportId, $version]
        )['notes'] ?? null) : null;

        $this->execute(
            "INSERT INTO posko_report_versions (report_id, version, snapshot_json, reason, changed_by) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE snapshot_json = VALUES(snapshot_json), changed_by = VALUES(changed_by), created_at = NOW()",
            [$reportId, $version, json_encode($data, JSON_UNESCAPED_UNICODE), $reason, $userId]
        );
    }

    /**
     * Catatan pembuatan draft pada riwayat
     */
    public function logCreate(int $reportId, int $userId, int $satkerId): void
    {
        $this->addHistory($reportId, $userId, $satkerId, 'create', null, 'DRAFT', null, null, 1, null);
    }

    private function generalParams(array $general): array
    {
        $general['period_key'] = $general['period_start'] ?? null;
        return array_map(fn($c) => ($general[$c] ?? null) === '' ? null : ($general[$c] ?? null), self::GENERAL_COLUMNS);
    }

    /**
     * @param array<string, array{number: ?string, text: ?string}> $values
     */
    private function replaceValues(int $reportId, array $values): void
    {
        $this->execute("DELETE FROM posko_report_values WHERE report_id = ?", [$reportId]);
        foreach ($values as $key => $v) {
            if (($v['number'] ?? null) === null && ($v['text'] ?? null) === null) {
                continue;
            }
            $this->execute(
                "INSERT INTO posko_report_values (report_id, field_key, value_number, value_text) VALUES (?, ?, ?, ?)",
                [$reportId, $key, $v['number'] ?? null, $v['text'] ?? null]
            );
        }
    }

    /**
     * Nomor berikutnya PSK/{KODE-UNIT}/{YYYYMM}/{NNNN} (dipanggil di dalam transaksi)
     */
    private function nextNumber(string $unitCode): string
    {
        $period = date('Ym');
        $scope = "{$unitCode}/{$period}";
        $this->execute(
            "INSERT INTO posko_number_sequences (scope, last_seq) VALUES (?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_seq = LAST_INSERT_ID(last_seq + 1)",
            [$scope]
        );
        $seq = (int)$this->db->query("SELECT LAST_INSERT_ID()")->fetchColumn();
        return 'PSK/' . $scope . '/' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}
