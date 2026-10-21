<?php
/**
 * SIGAP-POLRI: Drill-Down Data "Satu Nomor, Satu Jejak" untuk Pimpinan & Personil
 *
 * Ringkasan per satker → daftar perkara terfilter → detail satu nomor lacak
 * (riwayat SP2HP, event stream, data 110/siber/Dumas/Bhabin terkait, log akses data pribadi).
 * Data pribadi pelapor selalu disamarkan; pembukaan NIK lewat endpoint beraudit.
 */

declare(strict_types=1);

namespace App\Models\OneService;

use App\Config\Database;
use App\Core\OpsPdp;
use PDO;

class OpsDrill
{
    public const STATUSES = [
        'INCOMING'      => 'Masuk',
        'DISPATCHED'    => 'Didisposisikan',
        'VERIFIED_SPKT' => 'Terverifikasi SPKT',
        'INVESTIGATION' => 'Penyelidikan',
        'RESOLVED'      => 'Selesai',
        'CLOSED'        => 'Ditutup',
        'REJECTED'      => 'Ditolak',
    ];

    public const CHANNELS = [
        '110_CALL'     => 'Panggilan 110',
        'SPKT_LOKET'   => 'Loket SPKT',
        'WEB_PORTAL'   => 'Portal Web',
        'SUPERAPP'     => 'Super App',
        'DUMAS_PROPAM' => 'Dumas Propam',
        'CYBER_CRIME'  => 'Aduan Siber',
    ];

    public const PRIORITIES = [
        'P1_EMERGENCY' => 'P1 Darurat',
        'P2_URGENT'    => 'P2 Mendesak',
        'P3_ROUTINE'   => 'P3 Rutin',
    ];

    /**
     * Filter yang dikenali dari query string (nilai tidak dikenal diabaikan)
     */
    public static function filtersFrom(array $query): array
    {
        $status = strtoupper((string)($query['status'] ?? ''));
        $channel = strtoupper((string)($query['channel'] ?? ''));
        $priority = strtoupper((string)($query['priority'] ?? ''));

        return [
            'status'     => ($status === 'TUNTAS' || isset(self::STATUSES[$status])) ? $status : '',
            'channel'    => isset(self::CHANNELS[$channel]) ? $channel : '',
            'priority'   => isset(self::PRIORITIES[$priority]) ? $priority : '',
            'satker'     => max(0, (int)($query['satker'] ?? 0)) ?: '',
            'stalled'    => !empty($query['stalled']) ? '1' : '',
            'vulnerable' => !empty($query['vulnerable']) ? '1' : '',
            'q'          => mb_substr(trim((string)($query['q'] ?? '')), 0, 100),
        ];
    }

    /**
     * Daftar perkara terfilter + paginasi: ['items', 'total', 'page', 'pages', 'perPage']
     */
    public static function paginate(array $filters, int $page, int $perPage = 25): array
    {
        $pdo = Database::getConnection();
        [$where, $params] = self::where($filters);

        $count = $pdo->prepare("SELECT COUNT(*) FROM `ops_global_tracks` t WHERE 1=1 {$where}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT t.tracking_number, t.source_channel, t.current_status, t.priority_level, t.incident_category,
                    t.incident_summary, t.incident_address, t.citizen_name, t.is_whistleblower, t.is_vulnerable_group,
                    t.is_stalled, t.sla_deadline, t.external_reference_no, t.created_at, s.name AS satker_name,
                    (SELECT COUNT(*) FROM `ops_case_milestones` m WHERE m.tracking_number = t.tracking_number) AS milestone_count
             FROM `ops_global_tracks` t
             LEFT JOIN `satkers` s ON s.id = t.current_satker_id
             WHERE 1=1 {$where}
             ORDER BY t.is_stalled DESC, t.created_at DESC, t.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    private static function where(array $f): array
    {
        $sql = '';
        $params = [];
        if ($f['status'] === 'TUNTAS') {
            $sql .= " AND t.current_status IN ('RESOLVED', 'CLOSED')";
        } elseif ($f['status'] !== '') {
            $sql .= ' AND t.current_status = ?';
            $params[] = $f['status'];
        }
        foreach (['channel' => 'source_channel', 'priority' => 'priority_level'] as $key => $column) {
            if ($f[$key] !== '') {
                $sql .= " AND t.{$column} = ?";
                $params[] = $f[$key];
            }
        }
        if ($f['satker'] !== '') {
            $sql .= ' AND t.current_satker_id = ?';
            $params[] = (int)$f['satker'];
        }
        if ($f['stalled'] !== '') {
            $sql .= ' AND t.is_stalled = 1';
        }
        if ($f['vulnerable'] !== '') {
            $sql .= ' AND t.is_vulnerable_group = 1';
        }
        if ($f['q'] !== '') {
            $sql .= ' AND (t.tracking_number = ? OR t.external_reference_no = ? OR t.citizen_name LIKE ? OR t.incident_summary LIKE ? OR t.incident_category LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, strtoupper($f['q']), strtoupper($f['q']), $like, $like, $like);
        }
        return [$sql, $params];
    }

    /**
     * Ringkasan per satker penanggung jawab (untuk drill-down dari tingkat satker)
     */
    public static function satkerSummary(): array
    {
        return Database::getConnection()->query(
            "SELECT s.id, s.name,
                    COUNT(*) AS total,
                    SUM(t.current_status IN ('RESOLVED', 'CLOSED')) AS tuntas,
                    SUM(t.is_stalled = 1) AS stalled,
                    SUM(t.priority_level = 'P1_EMERGENCY') AS darurat,
                    SUM(t.is_vulnerable_group = 1) AS rentan
             FROM `ops_global_tracks` t
             JOIN `satkers` s ON s.id = t.current_satker_id
             GROUP BY s.id, s.name
             ORDER BY stalled DESC, total DESC, s.name"
        )->fetchAll();
    }

    /**
     * Detail lengkap satu nomor lacak, atau null bila tidak ada.
     * NIK & nomor HP pelapor hanya dikirim dalam bentuk tersamar.
     */
    public static function detail(string $trackingNumber): ?array
    {
        $track = OpsTrack::findByTrackingNumber($trackingNumber);
        if (!$track) {
            return null;
        }
        // Jangan bawa data pribadi utuh ke tampilan; pembukaan NIK lewat apiRevealPii (beraudit)
        unset($track['citizen_nik_decrypted'], $track['citizen_nik_encrypted'], $track['security_pin_hash']);
        $track['citizen_phone'] = OpsPdp::maskPhone($track['citizen_phone']);

        $pdo = Database::getConnection();
        $rows = function (string $sql) use ($pdo, $trackingNumber): array {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trackingNumber]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        };

        $legacyId = null;
        if (!empty($track['external_reference_no'])) {
            $stmt = $pdo->prepare("SELECT id FROM `reports` WHERE ticket_number = ? LIMIT 1");
            $stmt->execute([$track['external_reference_no']]);
            $legacyId = $stmt->fetchColumn() ?: null;
        }

        return [
            'track'      => $track,
            'milestones' => $rows("SELECT * FROM `ops_case_milestones` WHERE tracking_number = ? ORDER BY issued_at ASC, id ASC"),
            'events'     => $rows("SELECT event_name, payload_data, actor_nrp_or_system, actor_role, created_at FROM `ops_event_streams` WHERE tracking_number = ? ORDER BY created_at ASC, id ASC"),
            'dispatch'   => $rows("SELECT caller_name, urgency_level, unit_callsign, patrol_vehicle_type, officer_nrp, call_received_at, dispatched_at, arrived_at, response_time_seconds, status, scene_notes FROM `ops_dispatch_110` WHERE tracking_number = ? ORDER BY id"),
            'cyber'      => $rows("SELECT bank_name, account_number, account_holder_name, fraud_scheme, loss_amount, freeze_status, freeze_requested_at, freeze_confirmed_at, cluster_tag FROM `ops_cyber_fraud` WHERE tracking_number = ? ORDER BY id"),
            'dumas'      => $rows("SELECT d.reported_service_loket, d.allegation_type, d.is_whistleblower_protected, d.risk_score, d.investigation_status, s.name AS reported_satker FROM `ops_dumas_cases` d LEFT JOIN `satkers` s ON s.id = d.reported_satker_id WHERE d.tracking_number = ? ORDER BY d.id"),
            'bhabin'     => $rows("SELECT bhabin_nrp, village_name, conflict_category, parties_involved, mediation_outcome, is_peacefully_resolved, is_ceremonial_rejected, recorded_at FROM `ops_bhabin_resolutions` WHERE tracking_number = ? ORDER BY id"),
            'pdpLogs'    => $rows("SELECT l.viewer_nrp, u.name AS viewer_name, l.accessed_data, l.justification, l.is_out_of_jurisdiction, l.accessed_at FROM `ops_audit_pdp_logs` l LEFT JOIN `users` u ON u.nrp = l.viewer_nrp WHERE l.tracking_number = ? ORDER BY l.accessed_at DESC LIMIT 50"),
            'legacyId'   => $legacyId ? (int)$legacyId : null,
        ];
    }
}
