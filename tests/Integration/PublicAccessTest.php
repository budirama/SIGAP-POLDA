<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

// Laporan seeder #1 (INVESTIGATING) & #3 (CLOSED)
$ticket1 = (string)Db::value("SELECT ticket_number FROM reports WHERE id = 1");
$pin1 = (string)Db::value("SELECT security_pin FROM reports WHERE id = 1");
$ticket3 = (string)Db::value("SELECT ticket_number FROM reports WHERE id = 3");
$wrongPin = $pin1 === '000000' ? '111111' : '000000';

return [
    'tracking tanpa PIN tidak menampilkan isi laporan' => function () use ($ticket1) {
        $res = (new Http(TEST_BASE_URL))->get("/report/track?ticket={$ticket1}");
        T::contains('Masukkan PIN Keamanan', $res->body);
        T::notContains('Cetak / Unduh SP2HP', $res->body);
    },

    'tracking dengan PIN salah ditolak' => function () use ($ticket1, $wrongPin) {
        $res = (new Http(TEST_BASE_URL))->get("/report/track?ticket={$ticket1}&pin={$wrongPin}");
        T::contains('PIN Keamanan tidak cocok', $res->body);
        T::notContains('Cetak / Unduh SP2HP', $res->body);
    },

    'PIN benar membuka laporan & dokumen SP2HP di sesi yang sama' => function () use ($ticket1, $pin1) {
        $guest = new Http(TEST_BASE_URL);
        T::same(302, $guest->get("/report/sp2hpPrint?ticket={$ticket1}")->status, 'SP2HP sebelum PIN');
        T::contains('Cetak / Unduh SP2HP', $guest->get("/report/track?ticket={$ticket1}&pin={$pin1}")->body);
        T::same(200, $guest->get("/report/sp2hpPrint?ticket={$ticket1}")->status, 'SP2HP setelah PIN');
        T::contains('Cetak / Unduh SP2HP', $guest->get("/report/track?ticket={$ticket1}")->body, 'akses tersimpan di sesi');
    },

    'halaman sukses (berisi PIN) tidak dapat dibuka sesi lain' => function () use ($ticket1) {
        $res = (new Http(TEST_BASE_URL))->get("/report/success?ticket={$ticket1}");
        T::same(302, $res->status);
        T::notContains('PIN Keamanan', $res->body);
    },

    'pesan klarifikasi & SKM ke tiket orang lain ditolak' => function () use ($ticket3) {
        $before = (int)Db::value("SELECT COUNT(*) FROM case_messages WHERE report_id = 3");
        $feedback = Db::row("SELECT rating_stars, comments FROM report_feedbacks WHERE report_id = 3");

        $guest = new Http(TEST_BASE_URL);
        $csrf = $guest->get('/report/create')->csrf();
        $guest->post('/report/sendMessage', ['_csrf_token' => $csrf, 'ticket_number' => $ticket3, 'message_text' => 'spam']);
        T::contains('PIN Keamanan belum diverifikasi', $guest->flash());
        $guest->post('/report/submitFeedback', ['_csrf_token' => $csrf, 'ticket_number' => $ticket3, 'rating_stars' => '1', 'comments' => 'sabotase']);
        T::contains('PIN Keamanan belum diverifikasi', $guest->flash());

        T::same($before, (int)Db::value("SELECT COUNT(*) FROM case_messages WHERE report_id = 3"), 'jumlah pesan');
        T::same($feedback, Db::row("SELECT rating_stars, comments FROM report_feedbacks WHERE report_id = 3"), 'feedback');
    },

    'pesan klarifikasi tanpa token CSRF ditolak' => function () use ($ticket1, $pin1) {
        $guest = new Http(TEST_BASE_URL);
        $guest->get("/report/track?ticket={$ticket1}&pin={$pin1}");
        $guest->post('/report/sendMessage', ['ticket_number' => $ticket1, 'message_text' => 'tanpa csrf']);
        T::contains('CSRF tidak valid', $guest->flash());
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM case_messages WHERE message_text = 'tanpa csrf'"));
    },

    'tracking terkunci setelah 5 PIN salah, PIN benar pun ditolak' => function () use ($ticket1, $pin1, $wrongPin) {
        Db::exec("DELETE FROM throttle_events");
        $guest = new Http(TEST_BASE_URL);
        for ($i = 1; $i <= 5; $i++) {
            T::contains('tidak cocok', $guest->get("/report/track?ticket={$ticket1}&pin={$wrongPin}")->body, "percobaan {$i}");
        }
        $res = $guest->get("/report/track?ticket={$ticket1}&pin={$pin1}");
        T::contains('Terlalu banyak percobaan PIN', $res->body);
        T::notContains('Cetak / Unduh SP2HP', $res->body);
        Db::exec("DELETE FROM throttle_events");
    },
];
