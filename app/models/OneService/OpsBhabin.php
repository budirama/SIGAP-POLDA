<?php
/**
 * SIGAP-POLRI: Model Modul 7 Peta Kerawanan & Catatan Solusi Bhabinkamtibmas
 * Reorientasi kerja Bhabin: Problem Solving & Restorative Justice vs Seremonial
 */

declare(strict_types=1);

namespace App\Models\OneService;

use App\Config\Database;
use App\Core\OpsEventBus;
use PDO;
use Exception;

class OpsBhabin
{
    /**
     * Catat hasil mediasi/penyelesaian masalah warga (Alternative Dispute Resolution)
     */
    public static function recordResolution(array $data): array
    {
        $outcome = trim((string)($data['mediation_outcome'] ?? ''));
        $parties = trim((string)($data['parties_involved'] ?? ''));

        // VALIDASI ANTI-SEREMONIAL:
        // Tolak jika hanya deskripsi foto seremonial/pembagian brosur tanpa substansi penyelesaian masalah
        if (strlen($outcome) < 20 || stripos($outcome, 'foto bersama') !== false && strlen($outcome) < 35) {
            return [
                'success' => false,
                'error' => 'Sistem menolak laporan seremonial. Bhabinkamtibmas wajib mencatat masalah nyata warga dan hasil mufakat kesepakatan damai.'
            ];
        }

        // 1. Daftarkan nomor lacak kasus Bhabin
        $trackResult = OpsTrack::createTrack([
            'source_channel'     => 'SPKT_LOKET',
            'current_status'     => 'RESOLVED',
            'priority_level'     => 'P3_ROUTINE',
            'incident_category'  => 'PROBLEM_SOLVING_BHABIN',
            'incident_summary'   => sprintf("Problem solving di Desa/Kel. %s: %s", $data['village_name'] ?? '-', $outcome),
            'citizen_name'       => $parties,
            'citizen_phone'      => $data['citizen_phone'] ?? '-',
            'current_satker_id'  => (int)($data['satker_id'] ?? 1),
            'assigned_officer_nrp' => $data['bhabin_nrp'] ?? null
        ]);

        if (!$trackResult['success']) {
            return $trackResult;
        }

        $trackingNumber = $trackResult['tracking_number'];
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO `ops_bhabin_resolutions`
                 (`tracking_number`, `bhabin_nrp`, `village_name`, `satker_id`, `conflict_category`,
                  `parties_involved`, `mediation_outcome`, `is_peacefully_resolved`, `recorded_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())"
            );

            $stmt->execute([
                $trackingNumber,
                $data['bhabin_nrp'] ?? 'BHABIN-01',
                $data['village_name'] ?? 'Desa Presisi',
                (int)($data['satker_id'] ?? 1),
                $data['conflict_category'] ?? 'Sengketa Batas Warga',
                $parties,
                $outcome
            ]);

            OpsEventBus::publish($pdo, $trackingNumber, 'BHABIN_PROBLEM_SOLVED', [
                'village' => $data['village_name'] ?? 'Desa Presisi',
                'category' => $data['conflict_category'] ?? 'Sengketa Batas Warga'
            ], $data['bhabin_nrp'] ?? 'BHABIN-01');

            $pdo->commit();

            return [
                'success' => true,
                'tracking_number' => $trackingNumber,
                'message' => 'Penyelesaian masalah warga (Restorative Justice) berhasil dicatat.'
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("OpsBhabin recordResolution Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Dapatkan data titik kerawanan & resolusi untuk GIS Heatmap
     */
    public static function getKerawananHeatmap(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT 
                    t.tracking_number,
                    t.incident_category,
                    t.priority_level,
                    t.current_status,
                    t.incident_lat,
                    t.incident_lng,
                    t.incident_address,
                    t.created_at,
                    s.name as satker_name
                FROM `ops_global_tracks` t
                JOIN `satkers` s ON t.current_satker_id = s.id
                WHERE t.incident_lat IS NOT NULL AND t.incident_lng IS NOT NULL
                ORDER BY t.created_at DESC
                LIMIT 100";

        return $pdo->query($sql)->fetchAll();
    }
}
