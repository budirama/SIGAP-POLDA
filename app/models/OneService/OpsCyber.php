<?php
/**
 * SIGAP-POLRI: Model Modul 6 Aduan Penipuan Daring & Blokir Cepat
 * Integrasi CekRekening.id, Konsorsium Perbankan/OJK, dan Clustering Sindikat
 */

declare(strict_types=1);

namespace App\Models\OneService;

use App\Config\Database;
use App\Core\OpsAdapter;
use App\Core\OpsEventBus;
use PDO;
use Exception;

class OpsCyber
{
    /**
     * Daftarkan laporan penipuan daring dan picu permintaan blokir rekening cepat
     */
    public static function reportFraud(array $data): array
    {
        $accountNo = preg_replace('/[^0-9]/', '', (string)($data['account_number'] ?? ''));
        $bankName = strtoupper(trim((string)($data['bank_name'] ?? 'BANK_LAIN')));
        $accountHolder = trim((string)($data['account_holder_name'] ?? 'N/A'));
        $lossAmount = (float)($data['loss_amount'] ?? 0.0);
        $scheme = $data['fraud_scheme'] ?? 'ONLINE_SHOP';

        // 1. Daftarkan ke Global Case Tracker
        $trackResult = OpsTrack::createTrack([
            'source_channel'     => 'CYBER_CRIME',
            'current_status'     => 'INCOMING',
            'priority_level'     => 'P2_URGENT',
            'incident_category'  => 'PENIPUAN_DARING',
            'incident_summary'   => sprintf(
                "Penipuan daring modus %s. Kerugian Rp %s. Rekening pelaku %s %s a.n %s",
                $scheme,
                number_format($lossAmount, 0, ',', '.'),
                $bankName,
                $accountNo,
                $accountHolder
            ),
            'citizen_name'       => $data['citizen_name'] ?? 'Korban Penipuan Daring',
            'citizen_nik'        => $data['citizen_nik'] ?? null,
            'citizen_phone'      => $data['citizen_phone'] ?? '-',
            'current_satker_id'  => 3 // Dittipidsiber Bareskrim Polri
        ]);

        if (!$trackResult['success']) {
            return $trackResult;
        }

        $trackingNumber = $trackResult['tracking_number'];
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Deteksi cluster sindikat rekening yang sama di laporan sebelumnya
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `ops_cyber_fraud` WHERE `account_number` = ?");
            $stmtCount->execute([$accountNo]);
            $existingCount = (int)$stmtCount->fetchColumn();

            $clusterTag = $existingCount > 0 ? "CLUSTER-REK-" . substr($accountNo, -4) : null;

            // Picu freeze request via OpsAdapter
            $freezeResponse = OpsAdapter::requestBankFreeze($trackingNumber, [
                'bank_name' => $bankName,
                'account_number' => $accountNo,
                'account_holder' => $accountHolder,
                'loss_amount' => $lossAmount
            ]);

            $freezeStatus = $freezeResponse['freeze_status'] ?? 'REQUESTED_TO_BANK';

            $stmt = $pdo->prepare(
                "INSERT INTO `ops_cyber_fraud`
                 (`tracking_number`, `bank_name`, `account_number`, `account_holder_name`, `fraud_scheme`,
                  `loss_amount`, `freeze_status`, `freeze_requested_at`, `freeze_confirmed_at`, `cluster_tag`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, NOW())"
            );

            $stmt->execute([
                $trackingNumber,
                $bankName,
                $accountNo,
                $accountHolder,
                $scheme,
                $lossAmount,
                $freezeStatus,
                $clusterTag
            ]);

            OpsEventBus::publish($pdo, $trackingNumber, 'CYBER_FREEZE_REQUESTED', [
                'bank' => $bankName,
                'account' => $accountNo,
                'freeze_status' => $freezeStatus,
                'cluster_tag' => $clusterTag
            ]);

            $pdo->commit();

            return [
                'success' => true,
                'tracking_number' => $trackingNumber,
                'security_pin' => $trackResult['security_pin'],
                'freeze_status' => $freezeStatus,
                'cluster_tag' => $clusterTag
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("OpsCyber reportFraud Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Dapatkan daftar clustering sindikat rekening terhubung
     */
    public static function getSyndicateClusters(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT 
                    f.bank_name,
                    f.account_number,
                    f.account_holder_name,
                    COUNT(f.id) as victim_count,
                    SUM(f.loss_amount) as total_loss,
                    MAX(f.freeze_status) as latest_freeze_status,
                    MAX(f.created_at) as last_reported_at
                FROM `ops_cyber_fraud` f
                GROUP BY f.bank_name, f.account_number, f.account_holder_name
                HAVING victim_count >= 1
                ORDER BY victim_count DESC, total_loss DESC
                LIMIT 20";

        return $pdo->query($sql)->fetchAll();
    }
}
