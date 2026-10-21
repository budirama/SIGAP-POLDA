<?php
/**
 * SIGAP-POLRI: Model Triase & Dispatch 110 (Computer-Aided Dispatch)
 * Mengukur waktu respons: Time to Dispatch & Time to Arrive on Scene
 */

declare(strict_types=1);

namespace App\Models\OneService;

use App\Config\Database;
use App\Core\OpsAdapter;
use App\Core\OpsEventBus;
use PDO;
use Exception;

class OpsDispatch
{
    /**
     * Catat panggilan 110 baru dan lakukan triase urgensi
     */
    public static function createIncomingCall(array $data): array
    {
        // 1. Buat record induk global track terlebih dahulu
        $trackResult = OpsTrack::createTrack([
            'source_channel'     => '110_CALL',
            'current_status'     => 'INCOMING',
            'priority_level'     => $data['urgency_level'] ?? 'P1_EMERGENCY',
            'incident_category'  => $data['incident_category'] ?? 'DARURAT_110',
            'incident_summary'   => $data['call_summary'] ?? 'Panggilan darurat warga via 110',
            'incident_lat'       => $data['lat'] ?? null,
            'incident_lng'       => $data['lng'] ?? null,
            'incident_address'   => $data['address'] ?? 'Lokasi panggilan 110',
            'citizen_name'       => $data['caller_name'] ?? 'Penelpon 110',
            'citizen_phone'      => $data['caller_phone'] ?? '-',
            'current_satker_id'  => (int)($data['satker_id'] ?? 1)
        ]);

        if (!$trackResult['success']) {
            return $trackResult;
        }

        $trackingNumber = $trackResult['tracking_number'];
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare(
            "INSERT INTO `ops_dispatch_110`
             (`tracking_number`, `caller_phone`, `caller_name`, `urgency_level`, `call_received_at`, `status`)
             VALUES (?, ?, ?, ?, NOW(), 'CALL_RECEIVED')"
        );

        $stmt->execute([
            $trackingNumber,
            $data['caller_phone'] ?? '-',
            $data['caller_name'] ?? 'Penelpon 110',
            $data['urgency_level'] ?? 'P1_EMERGENCY'
        ]);

        return [
            'success' => true,
            'tracking_number' => $trackingNumber,
            'security_pin' => $trackResult['security_pin'],
            'status' => 'CALL_RECEIVED'
        ];
    }

    /**
     * Dispatch penugasan ke unit patroli terdekat (CAD)
     */
    public static function dispatchUnit(
        string $trackingNumber,
        string $unitCallsign,
        string $vehicleType = 'SAMAPTA_CAR',
        ?string $officerNrp = null
    ): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Panggil adapter CAD (Sandbox atau Live)
            $cadResponse = OpsAdapter::dispatch110($trackingNumber, [
                'unit_callsign' => $unitCallsign,
                'vehicle_type'  => $vehicleType,
                'officer_nrp'   => $officerNrp
            ]);

            $stmt = $pdo->prepare(
                "UPDATE `ops_dispatch_110`
                 SET `unit_callsign` = ?,
                     `patrol_vehicle_type` = ?,
                     `officer_nrp` = ?,
                     `dispatched_at` = NOW(),
                     `status` = 'DISPATCHED'
                 WHERE `tracking_number` = ?"
            );
            $stmt->execute([$unitCallsign, $vehicleType, $officerNrp, $trackingNumber]);

            // Update status global track
            $stmtTrack = $pdo->prepare(
                "UPDATE `ops_global_tracks` SET `current_status` = 'DISPATCHED' WHERE `tracking_number` = ?"
            );
            $stmtTrack->execute([$trackingNumber]);

            OpsEventBus::publish($pdo, $trackingNumber, '110_DISPATCHED', [
                'unit_callsign' => $unitCallsign,
                'vehicle_type'  => $vehicleType,
                'cad_ref_id'    => $cadResponse['cad_ref_id'] ?? null
            ], $officerNrp);

            $pdo->commit();

            return [
                'success' => true,
                'tracking_number' => $trackingNumber,
                'cad_response' => $cadResponse
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("OpsDispatch dispatchUnit Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Catat konfirmasi tiba di TKP dan hitung waktu respons otomatis
     */
    public static function markArrivedOnScene(string $trackingNumber, ?string $sceneNotes = null): array
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Ambil waktu terima panggilan
            $stmtGet = $pdo->prepare("SELECT `call_received_at` FROM `ops_dispatch_110` WHERE `tracking_number` = ? LIMIT 1");
            $stmtGet->execute([$trackingNumber]);
            $row = $stmtGet->fetch();

            if (!$row) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Data dispatch 110 tidak ditemukan'];
            }

            $receivedTime = strtotime($row['call_received_at']);
            $arrivedTime = time();
            $responseSeconds = max(0, $arrivedTime - $receivedTime);

            $stmt = $pdo->prepare(
                "UPDATE `ops_dispatch_110`
                 SET `arrived_at` = NOW(),
                     `response_time_seconds` = ?,
                     `status` = 'ON_SCENE',
                     `scene_notes` = ?
                 WHERE `tracking_number` = ?"
            );
            $stmt->execute([$responseSeconds, $sceneNotes, $trackingNumber]);

            OpsEventBus::publish($pdo, $trackingNumber, '110_ARRIVED_ON_SCENE', [
                'response_time_seconds' => $responseSeconds,
                'response_time_minutes' => round($responseSeconds / 60, 1),
                'scene_notes' => $sceneNotes
            ]);

            $pdo->commit();

            return [
                'success' => true,
                'response_time_seconds' => $responseSeconds,
                'response_time_minutes' => round($responseSeconds / 60, 1)
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("OpsDispatch markArrivedOnScene Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ambil statistik waktu respons 110
     */
    public static function getResponseStatistics(?int $satkerId = null): array
    {
        $pdo = Database::getConnection();
        $whereSatker = $satkerId ? "WHERE t.current_satker_id = " . (int)$satkerId : "";

        $sql = "SELECT 
                    COUNT(d.id) as total_calls,
                    SUM(CASE WHEN d.status = 'ON_SCENE' OR d.status = 'RESOLVED_ONSITE' THEN 1 ELSE 0 END) as total_arrived,
                    AVG(CASE WHEN d.response_time_seconds IS NOT NULL THEN d.response_time_seconds ELSE NULL END) as avg_response_seconds,
                    MIN(d.response_time_seconds) as min_response_seconds,
                    MAX(d.response_time_seconds) as max_response_seconds
                FROM `ops_dispatch_110` d
                JOIN `ops_global_tracks` t ON d.tracking_number = t.tracking_number
                {$whereSatker}";

        $res = $pdo->query($sql)->fetch();
        $avgSec = (float)($res['avg_response_seconds'] ?? 0);

        return [
            'total_calls' => (int)($res['total_calls'] ?? 0),
            'total_arrived' => (int)($res['total_arrived'] ?? 0),
            'avg_response_seconds' => round($avgSec),
            'avg_response_minutes' => round($avgSec / 60, 1),
            'sla_compliance_rate' => $res['total_arrived'] > 0 ? 
                round((($pdo->query("SELECT COUNT(*) FROM `ops_dispatch_110` WHERE `response_time_seconds` <= 900")->fetchColumn() / $res['total_arrived']) * 100), 1) : 100.0
        ];
    }
}
