<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

return [
    'header keamanan dikirim & X-Powered-By disembunyikan' => function () {
        $res = (new Http(TEST_BASE_URL))->get('/');
        T::same('DENY', $res->header('x-frame-options'));
        T::same('nosniff', $res->header('x-content-type-options'));
        T::same('strict-origin-when-cross-origin', $res->header('referrer-policy'));
        $csp = (string)$res->header('content-security-policy');
        foreach (["default-src 'self'", "frame-ancestors 'none'", "object-src 'none'", "base-uri 'self'", "form-action 'self'"] as $directive) {
            T::contains($directive, $csp);
        }
        T::same(null, $res->header('x-powered-by'));
    },

    'laporan publik tercatat oleh sistem, bukan oleh superadmin' => function () {
        [$pelapor, $ticket] = Db::createReport(['title' => Db::PREFIX . 'aktor sistem']);
        $id = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);
        T::same(null, Db::value("SELECT actor_id FROM report_timeline WHERE report_id = ? ORDER BY id LIMIT 1", [$id]));
        $html = $pelapor->get('/report/track?ticket=' . urlencode($ticket))->body;
        T::contains('Dicatat oleh: <b>Sistem SIGAP-POLRI</b>', $html);
        $superadmin = (string)Db::value("SELECT name FROM users WHERE role = 'ROLE_SUPERADMIN' LIMIT 1");
        T::notContains($superadmin, $html, 'nama superadmin tidak muncul');
    },

    'nomor SP2HP terlalu panjang ditolak dengan pesan jelas' => function () {
        $case = (int)Db::value("SELECT id FROM reports WHERE status = 'INVESTIGATING' AND assigned_investigator_id = (SELECT id FROM users WHERE email = 'penyidik.rian@sigap.polri.go.id') LIMIT 1");
        $before = (int)Db::value("SELECT COUNT(*) FROM report_timeline WHERE report_id = ?", [$case]);
        $penyidik = Http::as('penyidik');
        $penyidik->post('/investigator/updateSp2hp', [
            '_csrf_token' => $penyidik->get("/investigator/detail/{$case}")->csrf(), 'report_id' => $case,
            'sp2hp_number' => str_repeat('X', 101), 'public_notes' => 'uji', 'status' => 'INVESTIGATING',
        ]);
        T::contains('maksimal 100 karakter', $penyidik->flash());
        T::same($before, (int)Db::value("SELECT COUNT(*) FROM report_timeline WHERE report_id = ?", [$case]));
    },

    'konversi postingan dengan nama akun panjang tetap berhasil (mode strict)' => function () {
        $username = str_repeat('a', 60);
        Db::exec(
            "INSERT INTO social_feeds (platform, post_id_str, author_username, author_name, post_content, post_url, sentiment, status)
             VALUES ('INSTAGRAM', ?, ?, NULL, 'uji panjang', 'https://instagram.com/p/uji', 'NEGATIVE_URGENT', 'NEW')",
            ['zztest_' . bin2hex(random_bytes(6)), $username]
        );
        $feedId = (int)Db::pdo()->lastInsertId();
        $humas = Http::as('humas');
        $humas->post('/social/convert', ['_csrf_token' => $humas->get('/social')->csrf(), 'feed_id' => $feedId, 'category_id' => 1, 'title' => Db::PREFIX . 'medsos panjang', 'incident_location' => 'uji']);
        T::contains('dikonversi menjadi Tiket Resmi', $humas->flash());
        T::same(25, mb_strlen((string)Db::value("SELECT reporter_phone FROM reports WHERE social_feed_id = ?", [$feedId])), 'nomor kontak dipotong sesuai kolom');
    },
];
