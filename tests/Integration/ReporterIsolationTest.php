<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$ticket3 = (string)Db::value("SELECT ticket_number FROM reports WHERE id = 3");
$pin3 = (string)Db::value("SELECT security_pin FROM reports WHERE id = 3");

return [
    'personil tidak dapat melihat PIN di halaman sukses' => function () use ($ticket3, $pin3) {
        foreach (['humas', 'spkt', 'penyidik', 'superadmin'] as $role) {
            $res = Http::as($role)->get("/report/success?ticket={$ticket3}");
            T::same(302, $res->status, $role);
            T::notContains($pin3, $res->body, $role);
        }
    },

    'personil tanpa kewenangan tidak dapat membuka tracking tanpa PIN' => function () use ($ticket3) {
        // Humas tidak menangani laporan; penyidik Rian bukan penangan laporan baru
        [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'isolasi']);
        foreach (['humas', 'penyidik'] as $role) {
            T::notContains('Cetak / Unduh SP2HP', Http::as($role)->get("/report/track?ticket={$ticket}")->body, $role);
        }
        T::contains('Cetak / Unduh SP2HP', Http::as('spkt')->get("/report/track?ticket={$ticket}")->body, 'SPKT berwenang');
    },

    'personil tidak dapat mengirim pesan atas nama pelapor atau mengisi SKM' => function () use ($ticket3) {
        $before = (int)Db::value("SELECT COUNT(*) FROM case_messages WHERE report_id = 3");
        $feedback = Db::row("SELECT rating_stars, comments FROM report_feedbacks WHERE report_id = 3");

        $spkt = Http::as('spkt');
        $page = $spkt->get("/report/track?ticket={$ticket3}")->body;
        T::notContains('/report/sendMessage', $page, 'form pesan warga disembunyikan');
        T::notContains('/report/submitFeedback', $page, 'form SKM disembunyikan');

        $csrf = $spkt->get('/spkt')->csrf();
        $spkt->post('/report/sendMessage', ['_csrf_token' => $csrf, 'ticket_number' => $ticket3, 'message_text' => 'menyamar']);
        $spkt->post('/report/submitFeedback', ['_csrf_token' => $csrf, 'ticket_number' => $ticket3, 'rating_stars' => '1', 'comments' => 'menyamar']);

        T::same($before, (int)Db::value("SELECT COUNT(*) FROM case_messages WHERE report_id = 3"), 'pesan');
        T::same($feedback, Db::row("SELECT rating_stars, comments FROM report_feedbacks WHERE report_id = 3"), 'SKM');
    },

    'source HTML SPKT & Satker tidak memuat PIN atau NIK pelapor' => function () {
        $pins = Db::pdo()->query("SELECT security_pin FROM reports")->fetchAll(PDO::FETCH_COLUMN);
        $niks = array_filter(Db::pdo()->query("SELECT reporter_nik_raw FROM reports")->fetchAll(PDO::FETCH_COLUMN));
        foreach (['spkt' => '/spkt', 'kanit' => '/satker', 'superadmin' => '/satker'] as $role => $path) {
            $html = Http::as($role)->get($path)->body;
            T::notContains('security_pin', $html, "{$role} {$path}");
            T::notContains('reporter_nik_raw', $html, "{$role} {$path}");
            foreach ([...$pins, ...$niks] as $secret) {
                T::notContains((string)$secret, $html, "{$role} {$path}");
            }
        }
    },
];
