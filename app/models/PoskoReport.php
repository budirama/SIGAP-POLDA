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
        'period_start', 'period_end', 'event_time', 'region', 'situation_summary', 'description',
        'obstacles', 'follow_up', 'assistance_needed', 'priority', 'reference_ticket', 'deadline_at',
    ];

    public function find(int $id): ?array
    {
        return $this->fetch(
            "SELECT r.*, t.code AS type_code, t.name AS type_name, t.kind AS type_kind, t.period_unit,
                    s.name AS satker_name, s.code AS satker_code, s.level AS satker_level, s.parent_id AS satker_parent_id,
                    u.name AS creator_name, u.rank AS creator_rank
             FROM posko_reports r
             JOIN posko_report_types t ON t.id = r.report_type_id
             JOIN satkers s ON s.id = r.satker_id
             LEFT JOIN users u ON u.id = r.created_by
             WHERE r.id = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Daftar laporan dalam cakupan wilayah. Draft hanya terlihat oleh unitnya sendiri ($ownUnitId).
     *
     * @param int[] $scopeIds
     */
    public function paginate(array $scopeIds, ?int $ownUnitId, array $filters, int $page, int $perPage = 25): array
    {
        $scopeIds = array_values(array_filter(array_map('intval', $scopeIds)));
        if (!$scopeIds) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'perPage' => $perPage];
        }

        $where = ["r.satker_id IN (" . implode(',', $scopeIds) . ")", "(r.status <> 'DRAFT' OR r.satker_id = ?)"];
        $params = [$ownUnitId ?? 0];

        if (!empty($filters['type'])) {
            $where[] = "r.report_type_id = ?";
            $params[] = (int)$filters['type'];
        }
        if (!empty($filters['status']) && isset(self::STATUS_LABELS[$filters['status']])) {
            $where[] = "r.status = ?";
            $params[] = $filters['status'];
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
        $sqlWhere = implode(' AND ', $where);

        $total = (int)($this->fetch(
            "SELECT COUNT(*) AS n FROM posko_reports r JOIN satkers s ON s.id = r.satker_id WHERE {$sqlWhere}",
            $params
        )['n'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT r.id, r.report_number, r.status, r.priority, r.is_nil, r.period_start, r.period_end, r.event_time,
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

    public function existsForPeriod(int $satkerId, int $typeId, string $periodStart, int $exceptId = 0): bool
    {
        return $this->fetch(
            "SELECT id FROM posko_reports WHERE satker_id = ? AND report_type_id = ? AND period_start = ? AND id <> ? LIMIT 1",
            [$satkerId, $typeId, $periodStart, $exceptId]
        ) !== null;
    }

    /**
     * Buat draft beserta nilai isiannya
     */
    public function createDraft(int $typeId, int $satkerId, array $general, array $values, int $userId): int
    {
        $this->beginTransaction();
        try {
            $cols = array_merge(['report_type_id', 'satker_id', 'status', 'created_by', 'updated_by'], self::GENERAL_COLUMNS);
            $params = array_merge([$typeId, $satkerId, 'DRAFT', $userId, $userId], $this->generalParams($general));
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
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", self::GENERAL_COLUMNS));
            $changed = $this->affected(
                "UPDATE posko_reports SET {$set}, updated_by = ?, updated_at = NOW()
                 WHERE id = ? AND status IN ('" . implode("','", self::EDITABLE_STATUSES) . "')",
                array_merge($this->generalParams($general), [$userId, $id])
            );
            if ($changed !== 1) {
                $this->rollBack();
                return false;
            }
            $this->replaceValues($id, $values);
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
     * Kirim laporan: beri nomor unik (sekali) & catat waktu kirim. Mengembalikan nomor laporan, atau null bila status tidak sah.
     */
    public function submit(int $id, int $userId): ?string
    {
        $this->beginTransaction();
        try {
            $row = $this->fetch(
                "SELECT r.id, r.status, r.report_number, s.code AS satker_code
                 FROM posko_reports r JOIN satkers s ON s.id = r.satker_id
                 WHERE r.id = ? FOR UPDATE",
                [$id]
            );
            if (!$row || !in_array($row['status'], self::EDITABLE_STATUSES, true)) {
                $this->rollBack();
                return null;
            }

            $number = $row['report_number'] ?: $this->nextNumber($row['satker_code']);
            $this->execute(
                "UPDATE posko_reports SET status = 'SUBMITTED', report_number = ?, submitted_at = NOW(), updated_by = ? WHERE id = ?",
                [$number, $userId, $id]
            );
            $this->commit();
            return $number;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
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

    private function generalParams(array $general): array
    {
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
