<?php

use App\Models\SocialFeed;
use Tests\Db;
use Tests\Http;
use Tests\T;

$newFeed = function (string $url = 'https://x.com/uji/status/1'): int {
    Db::exec(
        "INSERT INTO social_feeds (platform, post_id_str, author_username, author_name, post_content, post_url, sentiment, status)
         VALUES ('TWITTER_X', ?, 'akun_uji', 'Akun Uji', 'Postingan uji otomatis', ?, 'NEGATIVE_URGENT', 'NEW')",
        ['zztest_' . bin2hex(random_bytes(6)), $url]
    );
    return (int)Db::pdo()->lastInsertId();
};

$convert = function (Http $client, int $feedId, int $categoryId = 1) {
    return $client->post('/social/convert', [
        '_csrf_token' => $client->get('/social')->csrf(), 'feed_id' => $feedId, 'category_id' => $categoryId,
        'title' => Db::PREFIX . 'konversi medsos', 'incident_location' => 'uji',
    ]);
};

return [
    'postingan hanya dapat dikonversi sekali & petugas tercatat' => function () use ($newFeed, $convert) {
        $feedId = $newFeed();
        $humas = Http::as('humas');
        $humasId = (int)Db::value("SELECT id FROM users WHERE email = 'humas@sigap.polda.go.id'");

        $convert($humas, $feedId);
        T::contains('dikonversi menjadi Tiket Resmi', $humas->flash());
        $convert($humas, $feedId);
        T::contains('sudah diproses sebelumnya', $humas->flash());

        T::same(1, (int)Db::value("SELECT COUNT(*) FROM reports WHERE social_feed_id = ?", [$feedId]), 'tepat satu tiket');
        $feed = Db::row("SELECT status, handled_by_user_id, converted_report_id FROM social_feeds WHERE id = ?", [$feedId]);
        T::same('CONVERTED_TO_TICKET', $feed['status']);
        T::same($humasId, (int)$feed['handled_by_user_id'], 'petugas konversi');
        T::same($humasId, (int)Db::value("SELECT actor_id FROM report_timeline WHERE report_id = ? ORDER BY id LIMIT 1", [$feed['converted_report_id']]), 'aktor timeline');
    },

    'postingan terkonversi tidak dapat ditandai spam' => function () use ($newFeed, $convert) {
        $feedId = $newFeed();
        $humas = Http::as('humas');
        $convert($humas, $feedId);
        $humas->flash();
        $humas->post('/social/dismiss', ['_csrf_token' => $humas->get('/social')->csrf(), 'feed_id' => $feedId]);
        T::contains('sudah diproses sebelumnya', $humas->flash());
        T::same('CONVERTED_TO_TICKET', Db::value("SELECT status FROM social_feeds WHERE id = ?", [$feedId]));
    },

    'kategori perkara tidak valid ditolak' => function () use ($newFeed, $convert) {
        $feedId = $newFeed();
        $humas = Http::as('humas');
        $convert($humas, $feedId, 9999);
        T::contains('Kategori perkara tidak valid', $humas->flash());
        T::same('NEW', Db::value("SELECT status FROM social_feeds WHERE id = ?", [$feedId]));
    },

    'URL postingan non-http(s) ditolak & tidak dirender sebagai tautan' => function () use ($newFeed) {
        T::same(true, SocialFeed::isSafeUrl('https://x.com/a/status/1'));
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'data:text/html,x', 'x.com/a', ''] as $url) {
            T::same(false, SocialFeed::isSafeUrl($url), $url);
        }

        $humas = Http::as('humas');
        $before = (int)Db::value("SELECT COUNT(*) FROM social_feeds");
        $humas->post('/social/simulate', ['_csrf_token' => $humas->get('/social')->csrf(), 'platform' => 'TWITTER_X', 'username' => 'zz', 'content' => 'x', 'post_url' => 'javascript:alert(1)']);
        T::contains('URL postingan harus diawali', $humas->flash());
        T::same($before, (int)Db::value("SELECT COUNT(*) FROM social_feeds"), 'simulasi ditolak');

        $newFeed('javascript:alert(document.cookie)');
        T::notContains('href="javascript:', Http::as('humas')->get('/social')->body, 'tautan berbahaya tidak dirender');
    },
];
