<?php
/**
 * SIGAP-POLRI: Model Modul 1 & 4 Case Management & SP2HP Elektronik
 * Manajemen timeline perkara, penerbitan SP2HP A1-A5, JPU sync, dan digital chain of custody
 */

declare(strict_types=1);

namespace App\Models\OneService;

use App\Config\Database;
use App\Core\OpsAdapter;
use App\Core\OpsEventBus;
use PDO;
use Exception;

class OpsCase
{
    /**
     * Terbitkan SP2HP Elektronik dan sinkronisasi ke e-MP
     */
    public static function issueSp2hp(
        string $trackingNumber,
        string $stageCode,
        string $title,
        string $publicNotes,
        ?string $internalNotes,
        string $officerNrp,
        ?string $jpuStatus = null,
        array $evidenceHashes = []
    ): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Ambil track record
            $track = OpsTrack::findByTrackingNumber($trackingNumber);
            if (!$track) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Kasus tidak ditemukan'];
            }

            // Hasilkan QR Verification hash (SHA-256)
            $qrHash = hash('sha256', $trackingNumber . $stageCode . microtime() . $officerNrp);

            $stmt = $pdo->prepare(
                "INSERT INTO `ops_case_milestones`
                 (`tracking_number`, `stage_code`, `title`, `description_public`, `description_internal`,
                  `officer_nrp`, `jpu_coordination_status`, `evidence_hashes`, `qr_verification_hash`, `issued_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $stmt->execute([
                $trackingNumber,
                $stageCode,
                $title,
                $publicNotes,
                $internalNotes,
                $officerNrp,
                $jpuStatus,
                !empty($evidenceHashes) ? json_encode($evidenceHashes) : null,
                $qrHash
            ]);

            // Sinkronisasi e-MP (Sandbox atau Live)
            $empResult = OpsAdapter::syncEmp($trackingNumber, [
                'stage_code' => $stageCode,
                'title' => $title,
                'officer_nrp' => $officerNrp,
                'jpu_status' => $jpuStatus
            ]);

            // Tentukan status perkara baru
            $newStatus = match($stageCode) {
                'A1' => 'VERIFIED_SPKT',
                'A2', 'A3', 'A4' => 'INVESTIGATION',
                'A5' => 'RESOLVED',
                default => 'INVESTIGATION'
            };

            // Hitung SLA perpanjangan jika masih aktif
            $slaHours = match($stageCode) {
                'A1' => 72,
                'A2' => 336, // 14 hari
                'A3' => 720, // 30 hari
                default => 168
            };
            $newSlaDeadline = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));

            $stmtUp = $pdo->prepare(
                "UPDATE `ops_global_tracks`
                 SET `current_status` = ?,
                     `external_reference_no` = COALESCE(`external_reference_no`, ?),
                     `sla_deadline` = ?,
                     `is_stalled` = 0
                 WHERE `tracking_number` = ?"
            );
            $stmtUp->execute([
                $newStatus,
                $empResult['emp_register_no'] ?? null,
                $newSlaDeadline,
                $trackingNumber
            ]);

            // Terbitkan event ke Event Bus
            OpsEventBus::publish($pdo, $trackingNumber, 'SP2HP_ISSUED', [
                'stage_code' => $stageCode,
                'title' => $title,
                'qr_hash' => $qrHash,
                'emp_register' => $empResult['emp_register_no'] ?? null
            ], $officerNrp);

            // Kirim notifikasi SMS/WA resmi ke pelapor
            $smsText = sprintf(
                "SP2HP Elektronik Polri: Perkembangan laporan Anda [%s] tahap %s telah terbit. Cek status di https://polri.go.id/lacak",
                $trackingNumber,
                $stageCode
            );
            OpsAdapter::sendCitizenNotice($track['citizen_phone'], $smsText);

            $pdo->commit();

            return [
                'success' => true,
                'tracking_number' => $trackingNumber,
                'stage_code' => $stageCode,
                'qr_verification_hash' => $qrHash,
                'emp_register_no' => $empResult['emp_register_no'] ?? null
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("OpsCase issueSp2hp Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Dapatkan daftar milestones SP2HP untuk suatu kasus
     */
    public static function getMilestones(string $trackingNumber): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT m.*, u.name as officer_name, u.rank as officer_rank
             FROM `ops_case_milestones` m
             LEFT JOIN `users` u ON m.officer_nrp = u.nrp
             WHERE m.tracking_number = ? ORDER BY m.issued_at ASC"
        );
        $stmt->execute([$trackingNumber]);
        return $stmt->fetchAll();
    }
}
