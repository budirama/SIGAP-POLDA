<?php
/**
 * SIGAP-POLRI: KapoldaDirective Model (Instruksi Atensi Kapolri ke Satker)
 */

namespace App\Models;

use App\Core\Model;

class KapoldaDirective extends Model
{
    public const OPEN_STATUSES = ['PENDING', 'ACKNOWLEDGED'];

    public function findById(int $id): ?array
    {
        return $this->fetch(
            "SELECT d.*, r.ticket_number, r.title AS report_title, r.status AS report_status, r.current_satker_id
             FROM kapolda_directives d
             JOIN reports r ON r.id = d.report_id
             WHERE d.id = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Instruksi yang masih terbuka untuk Satker (null = seluruh Satker, untuk superadmin)
     */
    public function getOpenForSatker(?int $satkerId): array
    {
        $sql = "SELECT d.*, r.ticket_number, r.title AS report_title, r.status AS report_status,
                       u.name AS creator_name, u.rank AS creator_rank, s.name AS satker_name
                FROM kapolda_directives d
                JOIN reports r ON r.id = d.report_id
                JOIN users u ON u.id = d.created_by
                LEFT JOIN satkers s ON s.id = d.target_satker_id
                WHERE d.status IN ('PENDING', 'ACKNOWLEDGED') AND d.target_satker_id IS NOT NULL ";
        $params = [];
        if ($satkerId !== null) {
            $sql .= "AND d.target_satker_id = ? ";
            $params[] = $satkerId;
        }

        return $this->fetchAll($sql . "ORDER BY d.status = 'PENDING' DESC, d.created_at ASC", $params);
    }

    /**
     * Status instruksi terbaru per laporan: [report_id => ['status' => ..., 'response_notes' => ...]]
     */
    public function latestByReports(array $reportIds): array
    {
        $reportIds = array_values(array_unique(array_map('intval', $reportIds)));
        if (!$reportIds) {
            return [];
        }

        $in = implode(',', array_fill(0, count($reportIds), '?'));
        $rows = $this->fetchAll(
            "SELECT d.report_id, d.status, d.response_notes, d.responded_at
             FROM kapolda_directives d
             JOIN (SELECT report_id, MAX(id) AS id FROM kapolda_directives WHERE report_id IN ({$in}) GROUP BY report_id) last
               ON last.id = d.id",
            $reportIds
        );

        return array_column($rows, null, 'report_id');
    }

    /**
     * Tanggapi instruksi: ACKNOWLEDGED (diterima) atau RESOLVED (diselesaikan).
     * Transisi hanya dari status terbuka; tanggapan dicatat di timeline sebagai catatan internal.
     */
    public function respond(int $id, int $actorId, string $newStatus, string $notes): bool
    {
        $allowedFrom = $newStatus === 'ACKNOWLEDGED' ? ['PENDING'] : self::OPEN_STATUSES;

        $this->beginTransaction();
        try {
            $directive = $this->fetch("SELECT d.*, r.status AS report_status FROM kapolda_directives d JOIN reports r ON r.id = d.report_id WHERE d.id = ? FOR UPDATE", [$id]);
            if (!$directive || !in_array($directive['status'], $allowedFrom, true)) {
                $this->rollBack();
                return false;
            }

            $this->execute(
                "UPDATE kapolda_directives SET status = ?, response_notes = COALESCE(NULLIF(?, ''), response_notes), responded_at = NOW() WHERE id = ?",
                [$newStatus, $notes, $id]
            );

            $label = $newStatus === 'ACKNOWLEDGED' ? 'diterima' : 'diselesaikan';
            $this->execute(
                "INSERT INTO report_timeline (report_id, actor_id, status_from, status_to, title, internal_notes)
                 VALUES (?, ?, ?, ?, 'Tanggapan Atensi Kapolri', ?)",
                [
                    $directive['report_id'],
                    $actorId,
                    $directive['report_status'],
                    $directive['report_status'],
                    "Instruksi Atensi Kapolri {$label}." . ($notes !== '' ? " Tanggapan: {$notes}" : '')
                ]
            );

            $this->commit();
            return true;
        } catch (\Throwable $e) {
            $this->rollBack();
            return false;
        }
    }
}
