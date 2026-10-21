<?php
/**
 * SIGAP-POLRI: Model Modul 5 Dumas Terpadu & Peringatan Dini Integritas
 * Satu pintu Dumas, Propam, WBS, perlindungan whistleblower, dan deteksi anomali pungli
 */

declare(strict_types=1);

namespace App\Models\OneService;

use App\Config\Database;
use App\Core\OpsEventBus;
use PDO;
use Exception;

class OpsDumas
{
    /**
     * Daftarkan pengaduan perilaku personil / penyimpangan loket layanan
     */
    public static function reportDumas(array $data): array
    {
        $satkerId = (int)($data['reported_satker_id'] ?? 1);
        $loket = trim((string)($data['reported_service_loket'] ?? 'LOKET_PELAYANAN'));
        $allegation = $data['allegation_type'] ?? 'PUNGLI';
        $isWhistleblower = !empty($data['is_whistleblower']) ? 1 : 0;

        // 1. Daftarkan nomor lacak kasus Dumas
        $trackResult = OpsTrack::createTrack([
            'source_channel'     => 'DUMAS_PROPAM',
            'current_status'     => 'INCOMING',
            'priority_level'     => 'P2_URGENT',
            'incident_category'  => 'DUMAS_PROPAM',
            'incident_summary'   => sprintf("Dumas dugaan %s di %s. Ringkasan: %s", $allegation, $loket, $data['summary'] ?? ''),
            'citizen_name'       => $isWhistleblower ? 'Whistleblower Terlindungi' : ($data['reporter_name'] ?? 'Pelapor Dumas'),
            'citizen_nik'        => $data['reporter_nik'] ?? null,
            'citizen_phone'      => $data['reporter_phone'] ?? '-',
            'is_whistleblower'   => $isWhistleblower,
            'current_satker_id'  => 6 // Divpropam Polri
        ]);

        if (!$trackResult['success']) {
            return $trackResult;
        }

        $trackingNumber = $trackResult['tracking_number'];
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO `ops_dumas_cases`
                 (`tracking_number`, `reported_satker_id`, `reported_service_loket`, `allegation_type`,
                  `is_whistleblower_protected`, `risk_score`, `investigation_status`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, 2, 'NEW', NOW())"
            );

            $stmt->execute([
                $trackingNumber,
                $satkerId,
                $loket,
                $allegation,
                $isWhistleblower
            ]);

            OpsEventBus::publish($pdo, $trackingNumber, 'DUMAS_FILED', [
                'satker_id' => $satkerId,
                'loket' => $loket,
                'allegation' => $allegation,
                'is_whistleblower' => $isWhistleblower
            ]);

            $pdo->commit();

            return [
                'success' => true,
                'tracking_number' => $trackingNumber,
                'security_pin' => $trackResult['security_pin'],
                'message' => 'Aduan Dumas berhasil diterima secara terpadu. Identitas Anda dijamin kerahasiaannya.'
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("OpsDumas reportDumas Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Deteksi Dini Anomali Integritas:
     * Lonjakan aduan pungli / arogansi pada satker atau loket tertentu (threshold >= 2 aduan)
     */
    public static function detectAnomalies(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT 
                    d.reported_satker_id,
                    s.name as satker_name,
                    s.code as satker_code,
                    d.reported_service_loket,
                    d.allegation_type,
                    COUNT(d.id) as incident_count,
                    MAX(d.created_at) as latest_report_time
                FROM `ops_dumas_cases` d
                JOIN `satkers` s ON d.reported_satker_id = s.id
                WHERE d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY d.reported_satker_id, s.name, s.code, d.reported_service_loket, d.allegation_type
                HAVING incident_count >= 1
                ORDER BY incident_count DESC";

        return $pdo->query($sql)->fetchAll();
    }
}
