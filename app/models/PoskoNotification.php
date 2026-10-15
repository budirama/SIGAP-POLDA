<?php
/**
 * SIGAP-POLRI: Notifikasi Posko (BRD 4.6) — dalam aplikasi + antrean email
 *
 * Penerima selalu dibatasi pada akun posko aktif milik unit yang berwenang atas peristiwanya:
 *  - laporan baru / diteruskan  → operator & admin unit pemegang (+ pejabat bila prioritas tinggi)
 *  - dikembalikan / disetujui   → operator & admin unit pembuat laporan
 *  - pengingat tenggat          → operator & admin unit yang wajib lapor
 * Pelaku aksi tidak pernah menerima notifikasi atas aksinya sendiri.
 */

namespace App\Models;

use App\Config\Constants;
use App\Core\Model;

class PoskoNotification extends Model
{
    public const TYPE_LABELS = [
        'report_submitted' => 'Laporan baru',
        'report_returned'  => 'Dikembalikan',
        'report_approved'  => 'Disetujui',
        'report_forwarded' => 'Diteruskan',
        'high_priority'    => 'Prioritas tinggi',
        'deadline_soon'    => 'Tenggat mendekat',
        'deadline_late'    => 'Terlambat',
    ];

    private const OPERATIONAL_ROLES = [Constants::ROLE_POSKO_OPERATOR, Constants::ROLE_POSKO_ADMIN];

    // ------------------------------------------------------------------ Penerima

    /**
     * Id akun aktif pada unit tertentu menurut role
     *
     * @param string[] $roles
     * @return int[]
     */
    public function recipientsOf(int $satkerId, array $roles): array
    {
        $roles = array_values(array_intersect($roles, Constants::POSKO_ROLES));
        if (!$roles || $satkerId <= 0) {
            return [];
        }
        $rows = $this->fetchAll(
            "SELECT id FROM users WHERE satker_id = ? AND is_active = 1 AND role IN (" . implode(',', array_fill(0, count($roles), '?')) . ")",
            array_merge([$satkerId], $roles)
        );
        return array_map('intval', array_column($rows, 'id'));
    }

    public function operatorsOf(int $satkerId): array
    {
        return $this->recipientsOf($satkerId, self::OPERATIONAL_ROLES);
    }

    public function executivesOf(int $satkerId): array
    {
        return $this->recipientsOf($satkerId, [Constants::ROLE_POSKO_EXECUTIVE]);
    }

    // ------------------------------------------------------------------ Pemicu workflow

    /**
     * Hasilkan notifikasi untuk aksi workflow atas laporan ($report = hasil PoskoReport::find setelah aksi)
     */
    public function onAction(array $report, string $action, int $actorId): int
    {
        $id = (int)$report['id'];
        $label = trim(($report['report_number'] ?: 'Laporan') . ' · ' . $report['type_name']);
        $from = $report['satker_name'];
        $link = "/posko-laporan/show/{$id}";
        $urgent = in_array($report['priority'], ['HIGH', 'CRITICAL'], true);
        $holder = (int)($report['holder_satker_id'] ?? 0);
        $owner = (int)$report['satker_id'];

        $targets = []; // [type, title, body, userIds]
        switch ($action) {
            case 'submit':
            case 'resubmit':
            case 'nil':
                $targets[] = ['report_submitted', $report['is_nil'] ? 'Pernyataan nihil masuk' : 'Laporan baru dikirim',
                    "{$label} dari {$from} menunggu verifikasi.", $this->operatorsOf($holder)];
                break;
            case 'forward':
                $targets[] = ['report_forwarded', 'Laporan diteruskan ke unit Anda',
                    "{$label} dari {$from} diteruskan untuk ditindaklanjuti.", $this->operatorsOf($holder)];
                break;
            case 'return':
                $targets[] = ['report_returned', 'Laporan dikembalikan untuk perbaikan',
                    "{$label} dikembalikan. Periksa catatan koreksi lalu kirim ulang.", $this->operatorsOf($owner)];
                break;
            case 'approve':
                $targets[] = ['report_approved', 'Laporan disetujui',
                    "{$label} telah disetujui oleh unit induk.", $this->operatorsOf($owner)];
                break;
        }
        if ($urgent && $holder && in_array($action, ['submit', 'resubmit', 'forward'], true)) {
            $targets[] = ['high_priority', ($report['priority'] === 'CRITICAL' ? 'KRITIS' : 'Prioritas tinggi') . ': perlu perhatian pimpinan',
                "{$label} dari {$from} berstatus prioritas " . strtolower($report['priority']) . '.', $this->executivesOf($holder)];
        }

        $sent = 0;
        foreach ($targets as [$type, $title, $body, $users]) {
            $users = array_values(array_diff($users, [$actorId]));
            $sent += $this->notify($users, $type, $title, $body, $link);
        }
        return $sent;
    }

    // ------------------------------------------------------------------ Kirim

    /**
     * Simpan notifikasi untuk tiap penerima + antrekan email. $dedupeKey (opsional) membuat
     * pemanggilan ulang yang sama tidak menghasilkan notifikasi/email ganda.
     *
     * @param int[] $userIds
     * @return int jumlah notifikasi baru
     */
    public function notify(array $userIds, string $type, string $title, ?string $body, ?string $link, ?string $dedupeKey = null): int
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $created = 0;
        foreach ($userIds as $uid) {
            $stmt = $this->query(
                "INSERT IGNORE INTO posko_notifications (user_id, type, title, body, link, dedupe_key) VALUES (?, ?, ?, ?, ?, ?)",
                [$uid, $type, mb_substr($title, 0, 150), $body !== null ? mb_substr($body, 0, 500) : null, $link, $dedupeKey]
            );
            if ($stmt->rowCount() === 0) {
                continue;
            }
            $created++;
            $user = $this->fetch("SELECT email FROM users WHERE id = ?", [$uid]);
            if ($user && $user['email']) {
                $this->queueEmail($uid, (string)$user['email'], $title, (string)$body . ($link ? "\n\nBuka: {$link}" : ''), $dedupeKey);
            }
        }
        return $created;
    }

    private function queueEmail(int $userId, string $email, string $subject, string $body, ?string $dedupeKey): void
    {
        $this->execute(
            "INSERT IGNORE INTO posko_notification_outbox (user_id, to_email, subject, body, dedupe_key) VALUES (?, ?, ?, ?, ?)",
            [$userId, $email, '[SIGAP Posko] ' . mb_substr($subject, 0, 180), $body, $dedupeKey]
        );
    }

    // ------------------------------------------------------------------ Baca

    public function unreadCount(int $userId): int
    {
        return (int)($this->fetch("SELECT COUNT(*) AS n FROM posko_notifications WHERE user_id = ? AND read_at IS NULL", [$userId])['n'] ?? 0);
    }

    public function latest(int $userId, int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        return $this->fetchAll(
            "SELECT id, type, title, body, link, read_at, created_at FROM posko_notifications
             WHERE user_id = ? ORDER BY id DESC LIMIT {$limit}",
            [$userId]
        );
    }

    public function paginate(int $userId, int $page, int $perPage = 20): array
    {
        $total = (int)($this->fetch("SELECT COUNT(*) AS n FROM posko_notifications WHERE user_id = ?", [$userId])['n'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $items = $this->fetchAll(
            "SELECT id, type, title, body, link, read_at, created_at FROM posko_notifications
             WHERE user_id = ? ORDER BY id DESC LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            [$userId]
        );
        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    /** Tandai satu notifikasi milik pengguna sebagai dibaca; kembalikan tautannya (null bila bukan miliknya) */
    public function markRead(int $id, int $userId): ?array
    {
        $row = $this->fetch("SELECT id, link FROM posko_notifications WHERE id = ? AND user_id = ?", [$id, $userId]);
        if (!$row) {
            return null;
        }
        $this->execute("UPDATE posko_notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = ?", [$id]);
        return $row;
    }

    public function markAllRead(int $userId): int
    {
        return $this->affected("UPDATE posko_notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL", [$userId]);
    }
}
