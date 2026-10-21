<?php
/**
 * SIGAP-POLRI: Model Induk Pelacakan Kasus Global (One Police Service)
 * Mengelola siklus hidup kasus dari panggilan awal hingga putusan inkracht
 */

declare(strict_types=1);

namespace App\Models\OneService;

use App\Config\Database;
use App\Core\OpsTracker;
use App\Core\OpsPdp;
use App\Core\OpsEventBus;
use PDO;
use Exception;

class OpsTrack
{
    /**
     * Daftarkan kasus baru ke ekosistem Satu Nomor, Satu Jejak
     */
    public static function createTrack(array $data): array
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $channel = $data['source_channel'] ?? 'WEB_PORTAL';
            $trackingNumber = OpsTracker::generateNumber($channel);
            $pin = OpsTracker::generatePin();
            $pinHash = password_hash($pin, PASSWORD_BCRYPT);

            // Enkripsi NIK sesuai UU PDP
            $nikRaw = trim((string)($data['citizen_nik'] ?? ''));
            $nikEncrypted = $nikRaw !== '' ? OpsPdp::encrypt($nikRaw) : null;

            $status = $data['current_status'] ?? 'INCOMING';
            $priority = $data['priority_level'] ?? 'P3_ROUTINE';
            $category = $data['incident_category'] ?? 'UMUM';
            $summary = $data['incident_summary'] ?? '';
            $lat = !empty($data['incident_lat']) ? (float)$data['incident_lat'] : null;
            $lng = !empty($data['incident_lng']) ? (float)$data['incident_lng'] : null;
            $address = $data['incident_address'] ?? null;
            $citizenName = $data['citizen_name'] ?? 'Warga Anonim';
            $citizenPhone = $data['citizen_phone'] ?? '-';
            $isWhistleblower = !empty($data['is_whistleblower']) ? 1 : 0;
            $isVulnerable = !empty($data['is_vulnerable_group']) ? 1 : 0;
            $satkerId = (int)($data['current_satker_id'] ?? 1);
            $officerNrp = $data['assigned_officer_nrp'] ?? null;
            $extRef = $data['external_reference_no'] ?? null;

            // Hitung tenggat SLA awal (default: 48 jam untuk P3, 24 jam untuk P2, 6 jam untuk P1)
            $slaHours = match($priority) {
                'P1_EMERGENCY' => 6,
                'P2_URGENT'    => 24,
                default        => 48
            };
            $slaDeadline = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));

            $stmt = $pdo->prepare(
                "INSERT INTO `ops_global_tracks`
                 (`tracking_number`, `security_pin_hash`, `source_channel`, `current_status`, `priority_level`,
                  `incident_category`, `incident_summary`, `incident_lat`, `incident_lng`, `incident_address`,
                  `citizen_name`, `citizen_nik_encrypted`, `citizen_phone`, `is_whistleblower`, `is_vulnerable_group`,
                  `current_satker_id`, `assigned_officer_nrp`, `external_reference_no`, `sla_deadline`, `is_stalled`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())"
            );

            $stmt->execute([
                $trackingNumber,
                $pinHash,
                $channel,
                $status,
                $priority,
                $category,
                $summary,
                $lat,
                $lng,
                $address,
                $citizenName,
                $nikEncrypted,
                $citizenPhone,
                $isWhistleblower,
                $isVulnerable,
                $satkerId,
                $officerNrp,
                $extRef,
                $slaDeadline
            ]);

            // Terbitkan event registrasi kasus baru ke Event Bus
            OpsEventBus::publish($pdo, $trackingNumber, 'CASE_REGISTERED', [
                'channel' => $channel,
                'priority' => $priority,
                'category' => $category,
                'satker_id' => $satkerId
            ]);

            $pdo->commit();

            return [
                'success' => true,
                'tracking_number' => $trackingNumber,
                'security_pin' => $pin, // Ditampilkan hanya sekali ke pelapor/petugas
                'sla_deadline' => $slaDeadline
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("OpsTrack Create Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Cari berkas kasus berdasarkan nomor pelacakan unik
     */
    public static function findByTrackingNumber(string $trackingNumber): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT t.*, s.name as satker_name, s.code as satker_code, u.name as officer_name, u.rank as officer_rank
             FROM `ops_global_tracks` t
             LEFT JOIN `satkers` s ON t.current_satker_id = s.id
             LEFT JOIN `users` u ON t.assigned_officer_nrp = u.nrp
             WHERE t.tracking_number = ? LIMIT 1"
        );
        $stmt->execute([trim($trackingNumber)]);
        $track = $stmt->fetch();

        if (!$track) {
            return null;
        }

        // Dekripsi NIK
        $track['citizen_nik_decrypted'] = OpsPdp::decrypt($track['citizen_nik_encrypted']);
        $track['citizen_nik_masked'] = OpsPdp::maskNik($track['citizen_nik_decrypted']);
        $track['citizen_phone_masked'] = OpsPdp::maskPhone($track['citizen_phone']);

        return $track;
    }

    /**
     * Verifikasi PIN 6-digit pelapor warga
     */
    public static function verifyPin(string $trackingNumber, string $pin): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT `security_pin_hash` FROM `ops_global_tracks` WHERE `tracking_number` = ? LIMIT 1");
        $stmt->execute([trim($trackingNumber)]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        return password_verify(trim($pin), $row['security_pin_hash']);
    }

    /**
     * Mutasi status alur perkara secara transaksional
     */
    public static function updateStatus(
        string $trackingNumber,
        string $newStatus,
        array $eventPayload = [],
        ?string $officerNrp = null
    ): bool {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $extraSql = "";
            $params = [$newStatus];

            if ($newStatus === 'CLOSED' || $newStatus === 'RESOLVED') {
                $extraSql = ", `closed_at` = NOW()";
            }

            if ($officerNrp !== null) {
                $extraSql .= ", `assigned_officer_nrp` = ?";
                $params[] = $officerNrp;
            }

            $params[] = $trackingNumber;

            $stmt = $pdo->prepare("UPDATE `ops_global_tracks` SET `current_status` = ? {$extraSql} WHERE `tracking_number` = ?");
            $stmt->execute($params);

            // Terbitkan event status mutasi
            OpsEventBus::publish($pdo, $trackingNumber, 'STATUS_UPDATED', array_merge([
                'new_status' => $newStatus
            ], $eventPayload), $officerNrp);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("OpsTrack UpdateStatus Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil timeline lengkap perkara (gabungan milestones SP2HP dan event bus)
     */
    public static function getTimeline(string $trackingNumber): array
    {
        $pdo = Database::getConnection();

        // 1. Ambil Milestones
        $stmtM = $pdo->prepare(
            "SELECT 'MILESTONE' as item_type, stage_code, title, description_public, officer_nrp, qr_verification_hash, issued_at as event_time
             FROM `ops_case_milestones`
             WHERE tracking_number = ? ORDER BY issued_at ASC"
        );
        $stmtM->execute([$trackingNumber]);
        $milestones = $stmtM->fetchAll();

        // 2. Ambil Event Streams
        $stmtE = $pdo->prepare(
            "SELECT 'EVENT' as item_type, event_name as title, payload_data, actor_nrp_or_system as officer_nrp, created_at as event_time
             FROM `ops_event_streams`
             WHERE tracking_number = ? ORDER BY created_at ASC"
        );
        $stmtE->execute([$trackingNumber]);
        $events = $stmtE->fetchAll();

        $combined = array_merge($milestones, $events);
        usort($combined, function ($a, $b) {
            return strcmp($a['event_time'], $b['event_time']);
        });

        return $combined;
    }

    /**
     * Deteksi dan beri penanda kasus-kasus yang mangkrak (melewati batas waktu SLA)
     */
    public static function runSlaWatchdog(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE `ops_global_tracks`
             SET `is_stalled` = 1
             WHERE `is_stalled` = 0
               AND `current_status` NOT IN ('RESOLVED', 'CLOSED', 'REJECTED')
               AND `sla_deadline` IS NOT NULL
               AND `sla_deadline` < NOW()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
