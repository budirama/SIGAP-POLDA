<?php
/**
 * SIGAP-POLRI: Tiket Disposisi ke Satker Fungsi
 *
 * SPKT mengirim tiket ke satker (Mabes / Polda / Polres / satuan fungsi). Satker tujuan menerima,
 * mengembalikan ke SPKT, atau meneruskan ke jajaran di bawahnya. Tiket yang tidak diterima
 * hingga batas waktu (SLA tanggap per prioritas) dieskalasi ke satuan induk & SPKT.
 */

namespace App\Models;

use App\Core\Model;
use App\Config\Constants;

class Disposition extends Model
{
    public const FUNCTIONS = [
        'RESKRIMUM'  => 'Reserse Kriminal Umum',
        'JATANRAS'   => 'Jatanras (Kejahatan & Kekerasan)',
        'RESKRIMSUS' => 'Reserse Kriminal Khusus',
        'NARKOBA'    => 'Reserse Narkoba',
        'SIBER'      => 'Siber',
        'LANTAS'     => 'Lalu Lintas',
        'PROPAM'     => 'Profesi & Pengamanan (Propam)',
        'SAMAPTA'    => 'Samapta / Sabhara',
    ];

    /** Fungsi pengganti bila satuan fungsi tidak ada di wilayah (mis. Polres tanpa unit Siber) */
    private const FALLBACK = [
        'JATANRAS'   => 'RESKRIMUM',
        'SIBER'      => 'RESKRIMSUS',
        'RESKRIMSUS' => 'RESKRIMUM',
    ];

    /** Batas jam satker menerima tiket, menurut tingkat urgensi laporan */
    public const ACK_HOURS = [
        'EMERGENCY' => 1,
        'HIGH'      => 6,
        'NORMAL'    => 24,
    ];

    public const STATUSES = [
        'PENDING'   => 'Menunggu Diterima',
        'ACCEPTED'  => 'Diterima',
        'RETURNED'  => 'Dikembalikan ke SPKT',
        'FORWARDED' => 'Diteruskan',
        'REVOKED'   => 'Dicabut / Didisposisi Ulang',
    ];

    /** Status laporan yang masih boleh diteruskan oleh satker pemegang tiket */
    public const FORWARDABLE_STATUSES = [Constants::STATUS_DISPATCHED, Constants::STATUS_ASSIGNED];

    // ------------------------------------------------------------------
    // Kirim / teruskan tiket
    // ------------------------------------------------------------------

    /**
     * Disposisi laporan ke satker tujuan (oleh SPKT bila $fromSatkerId null, atau diteruskan satker).
     * Tiket aktif sebelumnya ditutup (FORWARDED bila diteruskan, REVOKED bila didisposisi ulang SPKT),
     * penyidik lama dicabut, dan instruksi Atensi Kapolri yang terbuka ikut berpindah.
     */
    public function dispatch(int $reportId, int $toSatkerId, int $actorId, string $notes, ?int $fromSatkerId = null): bool
    {
        $this->beginTransaction();
        try {
            $report = $this->fetch(
                "SELECT r.status, r.current_satker_id, r.urgency_level, r.assigned_investigator_id,
                        inv.name AS investigator_name, inv.rank AS investigator_rank
                 FROM reports r LEFT JOIN users inv ON inv.id = r.assigned_investigator_id
                 WHERE r.id = ? FOR UPDATE",
                [$reportId]
            );
            $target = $this->fetch("SELECT id, name FROM satkers WHERE id = ? AND is_active = 1", [$toSatkerId]);

            $allowed = $fromSatkerId === null
                ? Constants::DISPATCHABLE_STATUSES
                : self::FORWARDABLE_STATUSES;
            if (!$report || !$target || !in_array($report['status'], $allowed, true)
                || ($fromSatkerId !== null && (int)$report['current_satker_id'] !== $fromSatkerId)
                || (int)$report['current_satker_id'] === $toSatkerId) {
                $this->rollBack();
                return false;
            }

            $this->execute(
                "UPDATE report_dispositions SET status = ?, responded_by = COALESCE(responded_by, ?), responded_at = COALESCE(responded_at, NOW())
                 WHERE report_id = ? AND status IN ('PENDING', 'ACCEPTED')",
                [$fromSatkerId === null ? 'REVOKED' : 'FORWARDED', $actorId, $reportId]
            );

            $hours = self::ACK_HOURS[$report['urgency_level']] ?? self::ACK_HOURS['NORMAL'];
            $this->execute(
                "INSERT INTO report_dispositions (report_id, from_satker_id, to_satker_id, disposed_by, notes, ack_deadline)
                 VALUES (?, ?, ?, ?, ?, NOW() + INTERVAL {$hours} HOUR)",
                [$reportId, $fromSatkerId, $toSatkerId, $actorId, $notes]
            );

            $this->execute(
                "UPDATE reports SET status = 'DISPATCHED', current_satker_id = ?, assigned_investigator_id = NULL WHERE id = ?",
                [$toSatkerId, $reportId]
            );
            $this->execute(
                "UPDATE kapolda_directives SET target_satker_id = ? WHERE report_id = ? AND status IN ('PENDING', 'ACKNOWLEDGED')",
                [$toSatkerId, $reportId]
            );

            $from = $fromSatkerId === null ? 'SPKT Polri' : ($this->fetch("SELECT name FROM satkers WHERE id = ?", [$fromSatkerId])['name'] ?? 'Satker');
            $internal = "Catatan disposisi {$from}: {$notes} (batas terima {$hours} jam)";
            if (!empty($report['assigned_investigator_id'])) {
                $internal .= " Penugasan penyidik sebelumnya ({$report['investigator_rank']} {$report['investigator_name']}) dicabut.";
            }
            $title = match (true) {
                $fromSatkerId !== null => 'Diteruskan ke Satuan Jajaran',
                !empty($report['current_satker_id']) => 'Disposisi Ulang ke Satker Lain',
                default => 'Disposisi ke Satker Terkait',
            };
            $this->timeline($reportId, $actorId, $report['status'], 'DISPATCHED', $title,
                $fromSatkerId === null
                    ? "Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke {$target['name']} untuk tindak lanjut."
                    : "Laporan diteruskan oleh {$from} kepada {$target['name']} untuk penanganan.",
                $internal
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            error_log('[SIGAP-POLRI] Gagal disposisi laporan #' . $reportId . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Satker menerima tiket yang menunggu (juga dipanggil otomatis saat menunjuk penyidik)
     */
    public function accept(int $reportId, int $satkerId, int $actorId, string $notes = ''): bool
    {
        $this->beginTransaction();
        try {
            $disp = $this->fetch(
                "SELECT d.id, d.escalated_at, s.name AS satker_name FROM report_dispositions d
                 JOIN satkers s ON s.id = d.to_satker_id
                 WHERE d.report_id = ? AND d.to_satker_id = ? AND d.status = 'PENDING'
                 ORDER BY d.id DESC LIMIT 1 FOR UPDATE",
                [$reportId, $satkerId]
            );
            if (!$disp) {
                $this->rollBack();
                return false;
            }

            $this->execute(
                "UPDATE report_dispositions SET status = 'ACCEPTED', responded_by = ?, responded_at = NOW(), response_notes = ? WHERE id = ?",
                [$actorId, $notes !== '' ? $notes : null, (int)$disp['id']]
            );
            $status = $this->fetch("SELECT status FROM reports WHERE id = ?", [$reportId])['status'] ?? 'DISPATCHED';
            $this->timeline($reportId, $actorId, $status, $status, 'Tiket Diterima Satker',
                "Laporan telah diterima oleh {$disp['satker_name']} dan sedang disiapkan penanganannya.",
                $notes !== '' ? "Catatan penerimaan: {$notes}" : null
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Satker mengembalikan tiket ke SPKT (salah sasaran / bukan kewenangan) untuk didisposisi ulang
     */
    public function returnToSpkt(int $reportId, int $satkerId, int $actorId, string $reason): bool
    {
        $this->beginTransaction();
        try {
            $report = $this->fetch("SELECT status, current_satker_id FROM reports WHERE id = ? FOR UPDATE", [$reportId]);
            $disp = $this->fetch(
                "SELECT d.id, s.name AS satker_name FROM report_dispositions d JOIN satkers s ON s.id = d.to_satker_id
                 WHERE d.report_id = ? AND d.to_satker_id = ? AND d.status IN ('PENDING', 'ACCEPTED')
                 ORDER BY d.id DESC LIMIT 1",
                [$reportId, $satkerId]
            );
            if (!$report || !$disp || $report['status'] !== Constants::STATUS_DISPATCHED || (int)$report['current_satker_id'] !== $satkerId) {
                $this->rollBack();
                return false;
            }

            $this->execute(
                "UPDATE report_dispositions SET status = 'RETURNED', responded_by = ?, responded_at = NOW(), response_notes = ? WHERE id = ?",
                [$actorId, $reason, (int)$disp['id']]
            );
            $this->execute(
                "UPDATE reports SET status = 'VERIFIED_SPKT', current_satker_id = NULL, assigned_investigator_id = NULL WHERE id = ?",
                [$reportId]
            );
            $this->execute(
                "UPDATE kapolda_directives SET target_satker_id = NULL WHERE report_id = ? AND status IN ('PENDING', 'ACKNOWLEDGED')",
                [$reportId]
            );
            $this->timeline($reportId, $actorId, 'DISPATCHED', 'VERIFIED_SPKT', 'Dikembalikan ke SPKT untuk Disposisi Ulang',
                'Laporan sedang ditinjau ulang oleh SPKT untuk diteruskan ke satuan yang berwenang.',
                "Dikembalikan oleh {$disp['satker_name']}: {$reason}"
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    private function timeline(int $reportId, int $actorId, string $from, string $to, string $title, string $public, ?string $internal): void
    {
        $this->execute(
            "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, public_notes, internal_notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$reportId, $actorId, $from, $to, $title, $public, $internal]
        );
    }

    // ------------------------------------------------------------------
    // Eskalasi SLA tanggap
    // ------------------------------------------------------------------

    /**
     * Tandai tiket yang belum diterima melewati batas waktu & catat di timeline internal.
     * Dipanggil oleh scripts/disposition_watchdog.php (cron) dan saat inbox SPKT/Satker dibuka.
     */
    public function escalateOverdue(): int
    {
        $rows = $this->fetchAll(
            "SELECT d.id, d.report_id, d.ack_deadline, s.name AS satker_name, p.name AS parent_name
             FROM report_dispositions d
             JOIN satkers s ON s.id = d.to_satker_id
             LEFT JOIN satkers p ON p.id = s.parent_id
             WHERE d.status = 'PENDING' AND d.escalated_at IS NULL AND d.ack_deadline < NOW()
             LIMIT 200"
        );

        $count = 0;
        foreach ($rows as $row) {
            // Klaim baris secara atomik agar dua watchdog bersamaan tidak mencatat ganda
            if ($this->affected("UPDATE report_dispositions SET escalated_at = NOW() WHERE id = ? AND escalated_at IS NULL", [(int)$row['id']]) === 0) {
                continue;
            }
            $status = $this->fetch("SELECT status FROM reports WHERE id = ?", [(int)$row['report_id']])['status'] ?? 'DISPATCHED';
            $this->execute(
                "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, public_notes, internal_notes)
                 VALUES (?, NULL, ?, ?, 'Eskalasi: Tiket Belum Diterima Satker', NULL, ?)",
                [(int)$row['report_id'], $status, $status,
                 "Tiket untuk {$row['satker_name']} melewati batas terima " . date('d/m/Y H:i', strtotime($row['ack_deadline']))
                 . '. Dieskalasi ke ' . ($row['parent_name'] ?: 'pimpinan') . ' dan SPKT.']
            );
            $count++;
        }
        return $count;
    }

    // ------------------------------------------------------------------
    // Kueri inbox & pemantauan
    // ------------------------------------------------------------------

    /**
     * Tiket aktif terakhir per laporan: [report_id => baris disposisi]
     */
    public function latestForReports(array $reportIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $reportIds)));
        if (!$ids) {
            return [];
        }
        $rows = $this->fetchAll(
            "SELECT d.*, fs.name AS from_satker_name, u.name AS disposed_by_name
             FROM report_dispositions d
             LEFT JOIN satkers fs ON fs.id = d.from_satker_id
             LEFT JOIN users u ON u.id = d.disposed_by
             WHERE d.id IN (SELECT MAX(id) FROM report_dispositions WHERE report_id IN (" . implode(',', $ids) . ") GROUP BY report_id)"
        );
        return array_column($rows, null, 'report_id');
    }

    public function historyForReport(int $reportId): array
    {
        return $this->fetchAll(
            "SELECT d.*, fs.name AS from_satker_name, ts.name AS to_satker_name, u.name AS disposed_by_name, r.name AS responder_name
             FROM report_dispositions d
             LEFT JOIN satkers fs ON fs.id = d.from_satker_id
             JOIN satkers ts ON ts.id = d.to_satker_id
             LEFT JOIN users u ON u.id = d.disposed_by
             LEFT JOIN users r ON r.id = d.responded_by
             WHERE d.report_id = ? ORDER BY d.id",
            [$reportId]
        );
    }

    public function countPendingForSatker(int $satkerId): int
    {
        return (int)($this->fetch(
            "SELECT COUNT(*) AS c FROM report_dispositions WHERE to_satker_id = ? AND status = 'PENDING'",
            [$satkerId]
        )['c'] ?? 0);
    }

    /**
     * Tiket terlambat (dieskalasi & belum diterima) di jajaran sebuah satker, atau seluruhnya bila $rootSatkerId null
     */
    public function escalatedWithin(?int $rootSatkerId): array
    {
        $sql = "SELECT d.*, s.name AS satker_name, r.ticket_number, r.title, r.urgency_level
                FROM report_dispositions d
                JOIN satkers s ON s.id = d.to_satker_id
                JOIN reports r ON r.id = d.report_id
                WHERE d.status = 'PENDING' AND d.escalated_at IS NOT NULL";
        $params = [];
        if ($rootSatkerId !== null) {
            $sql .= " AND s.path LIKE CONCAT((SELECT path FROM satkers WHERE id = ?), '%') AND s.id <> ?";
            $params = [$rootSatkerId, $rootSatkerId];
        }
        return $this->fetchAll($sql . " ORDER BY d.ack_deadline ASC LIMIT 50", $params);
    }

    /**
     * Tiket yang dikembalikan satker & belum didisposisi ulang (perlu tindakan SPKT)
     */
    public function returnedAwaitingSpkt(): array
    {
        return $this->fetchAll(
            "SELECT d.*, s.name AS satker_name, r.ticket_number, r.title
             FROM report_dispositions d
             JOIN satkers s ON s.id = d.to_satker_id
             JOIN reports r ON r.id = d.report_id
             WHERE d.status = 'RETURNED' AND r.status = 'VERIFIED_SPKT'
               AND d.id = (SELECT MAX(id) FROM report_dispositions WHERE report_id = d.report_id)
             ORDER BY d.responded_at DESC LIMIT 50"
        );
    }

    // ------------------------------------------------------------------
    // Pilihan tujuan & rekomendasi
    // ------------------------------------------------------------------

    /**
     * Wilayah tujuan (Mabes, Polda, Polres, Polsek) untuk pemilihan bertahap di SPKT
     */
    public function territorialUnits(): array
    {
        return $this->fetchAll(
            "SELECT s.id, s.parent_id, s.type, s.name, p.name AS parent_name
             FROM satkers s LEFT JOIN satkers p ON p.id = s.parent_id
             WHERE s.is_active = 1 AND s.type IN ('MABES', 'POLDA', 'POLRES', 'POLSEK')
             ORDER BY FIELD(s.type, 'MABES', 'POLDA', 'POLRES', 'POLSEK'), p.name, s.name"
        );
    }

    /**
     * Satuan di dalam satu wilayah yang dapat menerima tiket: wilayah itu sendiri + satuan fungsinya
     * (tanpa wilayah bawahan, yang dipilih terpisah)
     */
    public function unitsInTerritory(int $territoryId): array
    {
        $root = $this->fetch("SELECT id, path, type, name FROM satkers WHERE id = ? AND is_active = 1", [$territoryId]);
        if (!$root) {
            return [];
        }
        $units = $this->fetchAll(
            "SELECT s.id, s.name, s.type, s.function_code FROM satkers s
             WHERE s.is_active = 1 AND s.type = 'DIREKTORAT' AND s.path LIKE ?
               AND NOT EXISTS (
                   SELECT 1 FROM satkers t WHERE t.type IN ('POLDA', 'POLRES', 'POLSEK') AND t.id <> ?
                     AND s.path LIKE CONCAT(t.path, '%') AND t.path LIKE ?
               )
             ORDER BY LENGTH(s.path), s.name",
            [$root['path'] . '%', $territoryId, $root['path'] . '%']
        );
        return array_merge([['id' => (int)$root['id'], 'name' => $root['name'], 'type' => $root['type'], 'function_code' => null]], $units);
    }

    /**
     * Satuan yang boleh menerima terusan dari satker ini:
     * satuan fungsi → seluruh jajaran wilayah induknya; satuan wilayah → seluruh jajaran di bawahnya.
     */
    public function forwardTargets(int $satkerId): array
    {
        $unit = $this->fetch("SELECT id, parent_id, type, path FROM satkers WHERE id = ?", [$satkerId]);
        if (!$unit) {
            return [];
        }
        $baseId = $unit['type'] === 'DIREKTORAT' && $unit['parent_id'] ? (int)$unit['parent_id'] : (int)$unit['id'];
        // Direktorat Mabes meneruskan ke satuan fungsi sejenis di Polda/Polres, bukan ke seluruh Polri
        if ($baseId === (int)($this->fetch("SELECT id FROM satkers WHERE type = 'MABES' LIMIT 1")['id'] ?? 0)) {
            return $this->fetchAll(
                "SELECT s.id, s.name, s.type FROM satkers s
                 WHERE s.is_active = 1 AND s.id <> ? AND s.type = 'DIREKTORAT'
                   AND s.function_code = (SELECT function_code FROM satkers WHERE id = ?)
                 ORDER BY LENGTH(s.path), s.name",
                [$satkerId, $satkerId]
            );
        }
        $base = $this->fetch("SELECT path FROM satkers WHERE id = ?", [$baseId]);
        return $this->fetchAll(
            "SELECT s.id, s.name, s.type FROM satkers s
             WHERE s.is_active = 1 AND s.id <> ? AND s.path LIKE ?
             ORDER BY LENGTH(s.path), FIELD(s.type, 'POLDA', 'DIREKTORAT', 'POLRES', 'POLSEK'), s.name",
            [$satkerId, ($base['path'] ?? '/-/') . '%']
        );
    }

    /**
     * Rekomendasi tujuan untuk satu laporan: fungsi dari kategori, wilayah dari lokasi kejadian.
     *
     * @return array{territory_id:int, satker_id:int, satker_name:string, reason:string}|null
     */
    public function recommend(array $report): ?array
    {
        $function = $report['category_function'] ?? null;
        if ($function === null && !empty($report['category_id'])) {
            $function = $this->fetch("SELECT function_code FROM report_categories WHERE id = ?", [(int)$report['category_id']])['function_code'] ?? null;
        }

        [$territory, $why] = $this->territoryFor($report);
        $unit = $function ? $this->functionalUnit((int)$territory['id'], $function) : null;
        $unit ??= $territory;

        $reason = $why . ($function ? ' · fungsi ' . (self::FUNCTIONS[$function] ?? $function) . ' dari kategori laporan' : '');
        return [
            'territory_id' => (int)$territory['id'],
            'satker_id'    => (int)$unit['id'],
            'satker_name'  => $unit['name'],
            'reason'       => $reason,
        ];
    }

    /**
     * Wilayah dari lokasi: nama Polres/Polsek pada alamat TKP, lalu Polda terdekat dari koordinat, lalu Mabes
     *
     * @return array{0:array, 1:string}
     */
    public function territoryFor(array $report): array
    {
        $location = mb_strtolower(' ' . ($report['incident_location'] ?? '') . ' ');
        if (trim($location) !== '') {
            $best = null;
            foreach ($this->territoryKeys() as $row) {
                if (str_contains($location, ' ' . $row['key']) && ($best === null || mb_strlen($row['key']) > mb_strlen($best['key']))) {
                    $best = $row;
                }
            }
            if ($best) {
                return [$best, 'Wilayah ' . $best['name'] . ' (cocok dengan alamat TKP)'];
            }
        }

        if (!empty($report['latitude']) && !empty($report['longitude'])) {
            $polda = $this->fetch(
                "SELECT id, name, type, (POW(latitude - ?, 2) + POW((longitude - ?) * COS(RADIANS(?)), 2)) AS d2
                 FROM satkers WHERE type = 'POLDA' AND is_active = 1 AND latitude IS NOT NULL
                 ORDER BY d2 ASC LIMIT 1",
                [(float)$report['latitude'], (float)$report['longitude'], (float)$report['latitude']]
            );
            // ±1,5 derajat ≈ 165 km: di luar itu koordinat Polda terdekat tidak cukup meyakinkan
            if ($polda && (float)$polda['d2'] <= 2.25) {
                return [$polda, 'Wilayah ' . $polda['name'] . ' (Polda terdekat dari titik koordinat TKP)'];
            }
        }

        $mabes = $this->fetch("SELECT id, name, type FROM satkers WHERE type = 'MABES' LIMIT 1");
        return [$mabes, 'Wilayah tidak terdeteksi dari alamat TKP: diarahkan ke direktorat Mabes'];
    }

    private ?array $territoryKeyCache = null;

    /** Kata kunci wilayah Polres/Polsek: nama tanpa awalan satuan (Polres Metro Jakarta Barat → "jakarta barat") */
    private function territoryKeys(): array
    {
        if ($this->territoryKeyCache !== null) {
            return $this->territoryKeyCache;
        }
        $rows = $this->fetchAll("SELECT id, name, type FROM satkers WHERE is_active = 1 AND type IN ('POLRES', 'POLSEK')");
        $keys = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim(preg_replace('/^(Polrestabes|Polresta|Polres Metro|Polres|Polsek Metro|Polsek|Polsubsektor)\s+/i', '', $row['name'])));
            if (mb_strlen($key) >= 4) {
                $keys[] = $row + ['key' => $key];
            }
        }
        return $this->territoryKeyCache = $keys;
    }

    /**
     * Satuan fungsi teratas di wilayah untuk fungsi tertentu (mengikuti fungsi pengganti bila tidak ada)
     */
    public function functionalUnit(int $territoryId, string $function): ?array
    {
        $root = $this->fetch("SELECT path FROM satkers WHERE id = ?", [$territoryId]);
        if (!$root) {
            return null;
        }
        $seen = [];
        while ($function !== null && !isset($seen[$function])) {
            $seen[$function] = true;
            $unit = $this->fetch(
                "SELECT id, name, type FROM satkers
                 WHERE is_active = 1 AND type = 'DIREKTORAT' AND function_code = ? AND path LIKE ?
                 ORDER BY LENGTH(path) ASC LIMIT 1",
                [$function, $root['path'] . '%']
            );
            if ($unit) {
                return $unit;
            }
            $function = self::FALLBACK[$function] ?? null;
        }
        return null;
    }
}
