<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$ticket1 = (string)Db::value("SELECT ticket_number FROM reports WHERE id = 1");
$pin1 = (string)Db::value("SELECT security_pin FROM reports WHERE id = 1");

return [
    'akun yang dinonaktifkan langsung kehilangan sesi yang sedang berjalan' => function () {
        $penyidik = Http::as('penyidik');
        T::same(200, $penyidik->get('/investigator')->status, 'sebelum dinonaktifkan');
        Db::exec("UPDATE users SET is_active = 0 WHERE email = 'penyidik.rian@sigap.polda.go.id'");
        try {
            $res = $penyidik->get('/investigator');
            T::same(302, $res->status);
            T::true(str_ends_with($res->location(), '/auth/login'), 'diarahkan ke login: ' . $res->location());
        } finally {
            Db::exec("UPDATE users SET is_active = 1 WHERE email = 'penyidik.rian@sigap.polda.go.id'");
        }
        T::same(302, $penyidik->get('/investigator')->status, 'sesi lama tidak pulih walau akun diaktifkan kembali');
    },

    'mutasi Satker langsung mengubah hak akses sesi berjalan' => function () {
        $kanit = Http::as('kanit');
        $caseId = (int)Db::value("SELECT id FROM reports WHERE current_satker_id = 2 LIMIT 1");
        T::same(200, $kanit->get("/investigator/detail/{$caseId}")->status, 'kasus Satker sendiri');
        Db::exec("UPDATE users SET satker_id = 3 WHERE email = 'kanit.krimum@sigap.polda.go.id'");
        try {
            T::same(302, $kanit->get("/investigator/detail/{$caseId}")->status, 'setelah mutasi ke Satker lain');
        } finally {
            Db::exec("UPDATE users SET satker_id = 2 WHERE email = 'kanit.krimum@sigap.polda.go.id'");
        }
    },

    'ID sesi buatan klien ditolak (strict mode)' => function () {
        $ch = curl_init(TEST_BASE_URL . '/report/create');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIE => 'PHPSESSID=sesibuatanpenyerang1234567890']);
        $raw = (string)curl_exec($ch);
        preg_match('/Set-Cookie: PHPSESSID=([^;]+)/i', $raw, $m);
        T::true(isset($m[1]), 'server menerbitkan ID sesi baru');
        T::true($m[1] !== 'sesibuatanpenyerang1234567890', 'ID buatan penyerang tidak dipakai');
    },

    'verifikasi PIN menerbitkan ID sesi baru' => function () use ($ticket1, $pin1) {
        Db::exec("DELETE FROM throttle_events");
        $guest = new Http(TEST_BASE_URL);
        $guest->get('/report/create');
        $res = $guest->get("/report/track?ticket={$ticket1}&pin={$pin1}");
        T::contains('Cetak / Unduh SP2HP', $res->body);
        T::contains('PHPSESSID=', (string)$res->header('set-cookie'), 'cookie sesi diganti');
        T::contains('Cetak / Unduh SP2HP', $guest->get("/report/track?ticket={$ticket1}")->body, 'akses tetap ada setelah ID diganti');
    },

    'cookie sesi HttpOnly & SameSite=Strict' => function () {
        $res = (new Http(TEST_BASE_URL))->get('/report/create');
        $cookie = (string)$res->header('set-cookie');
        T::contains('HttpOnly', $cookie);
        T::contains('SameSite=Strict', $cookie);
    },
];
