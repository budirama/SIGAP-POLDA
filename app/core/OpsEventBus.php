<?php
/**
 * SIGAP-POLRI: Asynchronous Event Bus (Transactional Outbox)
 * Setiap mutasi status dan disposisi menerbitkan event persisten ke ops_event_streams
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use Exception;

class OpsEventBus
{
    /**
     * Terbitkan event ke ops_event_streams di dalam koneksi PDO transaksional yang aktif
     */
    public static function publish(
        PDO $pdo,
        string $trackingNumber,
        string $eventName,
        array $payload = [],
        ?string $actorNrp = null,
        ?string $actorRole = null
    ): bool {
        if ($actorNrp === null || $actorRole === null) {
            $user = Auth::user();
            $actorNrp = $actorNrp ?? ($user['nrp'] ?? 'SYSTEM');
            $actorRole = $actorRole ?? ($user['role'] ?? 'SYSTEM_WORKER');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmt = $pdo->prepare(
            "INSERT INTO `ops_event_streams`
             (`tracking_number`, `event_name`, `payload_data`, `actor_nrp_or_system`, `actor_role`, `ip_address`, `created_at`)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        return $stmt->execute([
            $trackingNumber,
            $eventName,
            $payloadJson,
            $actorNrp,
            $actorRole,
            $ip
        ]);
    }
}
