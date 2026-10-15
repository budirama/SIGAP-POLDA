<?php
/**
 * SIGAP-POLRI: Antrean email notifikasi posko (tabel posko_notification_outbox)
 *
 * Pengiriman dicoba ulang dengan jeda bertambah (5, 10, 20, 40 menit); setelah MAX_ATTEMPTS gagal
 * baris ditandai FAILED dan tidak dicoba lagi. Satu kegagalan tidak menghentikan baris lain.
 */

namespace App\Models;

use App\Core\Model;

class PoskoOutbox extends Model
{
    public const MAX_ATTEMPTS = 5;

    /** Baris PENDING yang sudah waktunya dicoba */
    public function due(int $limit = 50, ?string $now = null): array
    {
        $limit = max(1, min(500, $limit));
        return $this->fetchAll(
            "SELECT id, to_email, subject, body, attempts FROM posko_notification_outbox
             WHERE status = 'PENDING' AND next_attempt_at <= ? ORDER BY id ASC LIMIT {$limit}",
            [$now ?? date('Y-m-d H:i:s')]
        );
    }

    public function markSent(int $id): void
    {
        $this->execute(
            "UPDATE posko_notification_outbox SET status = 'SENT', sent_at = NOW(), attempts = attempts + 1, last_error = NULL WHERE id = ?",
            [$id]
        );
    }

    /**
     * Catat kegagalan: tambah percobaan, jadwalkan ulang dengan backoff, atau tandai FAILED bila batas habis
     */
    public function markFailed(int $id, string $error, ?string $now = null): void
    {
        $row = $this->fetch("SELECT attempts FROM posko_notification_outbox WHERE id = ?", [$id]);
        if (!$row) {
            return;
        }
        $attempts = (int)$row['attempts'] + 1;
        $status = $attempts >= self::MAX_ATTEMPTS ? 'FAILED' : 'PENDING';
        $next = date('Y-m-d H:i:s', strtotime(($now ?? date('Y-m-d H:i:s')) . ' +' . (5 * (2 ** ($attempts - 1))) . ' minutes'));
        $this->execute(
            "UPDATE posko_notification_outbox SET status = ?, attempts = ?, last_error = ?, next_attempt_at = ? WHERE id = ?",
            [$status, $attempts, mb_substr($error, 0, 255), $next, $id]
        );
    }
}
