<?php
/**
 * SIGAP-POLDA: AuditLog Model (Jejak Audit Akses & Tindakan Personil)
 */

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    public function insert(?int $userId, ?string $userRole, string $action, ?int $reportId, ?string $detail, string $ip, string $userAgent): void
    {
        $this->execute(
            "INSERT INTO audit_logs (user_id, user_role, action, report_id, detail, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$userId, $userRole, $action, $reportId, $detail, $ip, $userAgent]
        );
    }

    /**
     * Jejak audit terbaru dengan filter aksi & kata kunci (nomor tiket / nama personil / detail)
     */
    public function search(string $action = '', string $keyword = '', int $limit = 200): array
    {
        $sql = "SELECT a.*, u.name AS user_name, u.rank AS user_rank, r.ticket_number
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                LEFT JOIN reports r ON r.id = a.report_id
                WHERE 1=1 ";
        $params = [];

        if ($action !== '') {
            $sql .= "AND a.action = ? ";
            $params[] = $action;
        }
        if ($keyword !== '') {
            $sql .= "AND (r.ticket_number LIKE ? OR u.name LIKE ? OR a.detail LIKE ?) ";
            $kw = '%' . $keyword . '%';
            array_push($params, $kw, $kw, $kw);
        }

        $limit = max(1, min(500, $limit));
        return $this->fetchAll($sql . "ORDER BY a.id DESC LIMIT {$limit}", $params);
    }

    public function actions(): array
    {
        return array_column($this->fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action"), 'action');
    }
}
