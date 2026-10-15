<?php
/**
 * SIGAP-POLDA: SocialFeed Model (Omnichannel Medsos Ingestion)
 */

namespace App\Models;

use App\Core\Model;

class SocialFeed extends Model
{
    public function getPendingFeeds(): array
    {
        return $this->fetchAll(
            "SELECT sf.*, r.ticket_number AS converted_ticket
             FROM social_feeds sf
             LEFT JOIN reports r ON sf.converted_report_id = r.id
             WHERE sf.status = 'NEW'
             ORDER BY sf.created_at DESC LIMIT 50"
        );
    }

    public function getAllFeeds(): array
    {
        return $this->fetchAll(
            "SELECT sf.*, r.ticket_number AS converted_ticket, u.name AS handler_name
             FROM social_feeds sf
             LEFT JOIN reports r ON sf.converted_report_id = r.id
             LEFT JOIN users u ON sf.handled_by_user_id = u.id
             ORDER BY sf.created_at DESC LIMIT 100"
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetch("SELECT * FROM social_feeds WHERE id = ? LIMIT 1", [$id]);
    }

    /**
     * Tandai spam hanya bila postingan masih NEW (postingan terkonversi tidak dapat diabaikan)
     */
    public function dismissFeed(int $id, int $userId): bool
    {
        return $this->affected(
            "UPDATE social_feeds SET status = 'DISMISSED_SPAM', handled_by_user_id = ?, handled_at = NOW() WHERE id = ? AND status = 'NEW'",
            [$userId, $id]
        ) === 1;
    }

    /**
     * URL postingan hanya boleh berskema http(s) (cegah javascript:/data: di atribut href)
     */
    public static function isSafeUrl(?string $url): bool
    {
        return is_string($url)
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    public function createMockFeed(string $platform, string $username, string $name, string $content, string $postUrl, ?string $mediaUrl = null): int
    {
        $postIdStr = strtolower($platform) . '_' . time() . '_' . random_int(100, 999);
        $mediaJson = $mediaUrl ? json_encode([$mediaUrl]) : null;

        $this->execute(
            "INSERT INTO social_feeds (platform, post_id_str, author_username, author_name, post_content, post_url, media_urls, sentiment, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'NEGATIVE_URGENT', 'NEW')",
            [$platform, $postIdStr, $username, $name, $content, $postUrl, $mediaJson]
        );

        return $this->lastInsertId();
    }
}
