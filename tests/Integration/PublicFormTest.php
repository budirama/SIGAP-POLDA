<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$ticket1 = (string)Db::value("SELECT ticket_number FROM reports WHERE id = 1");
$pin1 = (string)Db::value("SELECT security_pin FROM reports WHERE id = 1");

$submit = function (Http $client, array $overrides = []) {
    return $client->post('/report/store', array_merge([
        '_csrf_token' => $client->get('/report/create')->csrf(),
        'reporter_nik' => '3171000000000001', 'reporter_name' => 'Penguji', 'reporter_phone' => '081234567890',
        'category_id' => '1', 'title' => Db::PREFIX . 'validasi', 'description' => 'uji', 'incident_location' => 'uji',
        'incident_date' => date('Y-m-d'), 'incident_time' => '10:00',
    ], $overrides));
};

return [
    'PIN di URL diabaikan; verifikasi hanya via formulir POST' => function () use ($ticket1, $pin1) {
        Db::exec("DELETE FROM throttle_events");
        $guest = new Http(TEST_BASE_URL);
        $res = $guest->get("/report/track?ticket={$ticket1}&pin={$pin1}");
        T::contains('Masukkan PIN Keamanan', $res->body);
        T::notContains('Cetak / Unduh SP2HP', $res->body);

        $post = $guest->unlock($ticket1, $pin1);
        T::same(302, $post->status);
        T::notContains('pin=', $post->location(), 'redirect tanpa PIN');
        T::contains('Cetak / Unduh SP2HP', $guest->get("/report/track?ticket={$ticket1}")->body);
    },

    'tiket tak dikenal & PIN salah menghasilkan pesan yang sama' => function () use ($ticket1, $pin1) {
        Db::exec("DELETE FROM throttle_events");
        $wrong = $pin1 === '000000' ? '111111' : '000000';
        $a = new Http(TEST_BASE_URL);
        $a->unlock($ticket1, $wrong);
        $b = new Http(TEST_BASE_URL);
        $b->unlock('LP-POLRI-000000-9999', $wrong);
        T::same($a->flash(), $b->flash());
        T::same(
            str_contains((new Http(TEST_BASE_URL))->get("/report/track?ticket={$ticket1}")->body, 'Masukkan PIN Keamanan'),
            str_contains((new Http(TEST_BASE_URL))->get('/report/track?ticket=LP-POLRI-000000-9999')->body, 'Masukkan PIN Keamanan'),
            'tampilan GET sama'
        );
        Db::exec("DELETE FROM throttle_events");
    },

    'halaman sukses tidak menaruh PIN di tautan maupun teks share' => function () {
        [$pelapor, $ticket] = Db::createReport(['title' => Db::PREFIX . 'sukses']);
        $pin = (string)Db::value("SELECT security_pin FROM reports WHERE ticket_number = ?", [$ticket]);
        $html = $pelapor->get('/report/success?ticket=' . urlencode($ticket))->body;
        T::contains($pin, $html, 'PIN tetap ditampilkan sekali untuk dicatat pelapor');
        T::notContains('pin=', $html);
        T::true((bool)preg_match('#api\.whatsapp\.com/send\?text=([^"]+)"#', $html, $m), 'tautan share ada');
        T::notContains($pin, urldecode($m[1]), 'PIN tidak ikut dibagikan');
    },

    'validasi server menolak isian tidak valid & mempertahankan input' => function () use ($submit) {
        $pairs = [
            [['reporter_nik' => '317100000000001'], 'NIK harus terdiri dari 16 digit'],
            [['reporter_phone' => 'abc'], 'Nomor WhatsApp tidak valid'],
            [['incident_date' => date('Y-m-d', strtotime('+2 days'))], 'Tanggal kejadian tidak valid'],
            [['incident_date' => '2026-02-30'], 'Tanggal kejadian tidak valid'],
            [['category_id' => '9999'], 'Kategori perkara tidak valid'],
        ];
        $before = (int)Db::value("SELECT COUNT(*) FROM reports");
        foreach ($pairs as [$override, $message]) {
            Db::exec("DELETE FROM throttle_events");
            $client = new Http(TEST_BASE_URL);
            $res = $submit($client, $override + ['title' => Db::PREFIX . 'judul tersimpan & utuh']);
            T::true(str_ends_with($res->location(), '/report/create'), $message);
            $form = $client->get('/report/create')->body;
            T::contains($message, html_entity_decode($form), $message);
            T::contains('value="' . htmlspecialchars(Db::PREFIX . 'judul tersimpan & utuh') . '"', $form, "{$message}: input dipertahankan");
        }
        T::same($before, (int)Db::value("SELECT COUNT(*) FROM reports"), 'tidak ada laporan tersimpan');
    },

    'lebih dari 5 file bukti ditolak' => function () use ($submit) {
        Db::exec("DELETE FROM throttle_events");
        $png = sys_get_temp_dir() . '/sigap_test_bukti.png';
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files["proof_files[{$i}]"] = new CURLFile($png, 'image/png', "bukti{$i}.png");
        }
        $client = new Http(TEST_BASE_URL);
        $res = $submit($client, $files);
        T::true(str_ends_with($res->location(), '/report/create'), 'kembali ke formulir');
        T::contains('Maksimal 5 file bukti', $client->flash());
    },

    'NIK warga terdaftar yang diketik orang lain tidak menautkan laporan ke akunnya' => function () {
        $citizen = Db::row("SELECT id, nik FROM users WHERE role = 'ROLE_CITIZEN' AND nik IS NOT NULL LIMIT 1");
        [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'nik orang lain', 'reporter_nik' => $citizen['nik']]);
        T::same(null, Db::value("SELECT reporter_id FROM reports WHERE ticket_number = ?", [$ticket]));
        T::same($citizen['nik'], Db::value("SELECT reporter_nik_raw FROM reports WHERE ticket_number = ?", [$ticket]), 'NIK tetap tercatat');
    },
];
