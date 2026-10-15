<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$attention = function (Http $kapolda, int $reportId, string $text = 'Instruksi uji otomatis') {
    $kapolda->post('/executive/giveAttention', ['_csrf_token' => $kapolda->get('/executive')->csrf(), 'report_id' => $reportId, 'directive_text' => $text]);
    return $kapolda->flash();
};

$newReport = function (string $suffix, array $overrides = []): int {
    [, $ticket] = Db::createReport(['title' => Db::PREFIX . $suffix] + $overrides);
    return (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);
};

$dispatch = function (int $reportId, int $satkerId): void {
    $spkt = Http::as('spkt');
    $spkt->post('/spkt/dispatch', ['_csrf_token' => $spkt->get('/spkt')->csrf(), 'report_id' => $reportId, 'satker_id' => $satkerId, 'dispatch_notes' => 'uji']);
};

$respond = function (Http $client, int $directiveId, string $action, string $notes = '') {
    $client->post('/satker/directive', ['_csrf_token' => $client->get('/satker')->csrf(), 'directive_id' => $directiveId, 'action' => $action, 'response_notes' => $notes]);
    return $client->flash();
};

return [
    'atensi tidak dapat diberikan pada perkara selesai' => function () use ($attention) {
        $closed = (int)Db::value("SELECT id FROM reports WHERE status = 'CLOSED' LIMIT 1");
        $before = (int)Db::value("SELECT COUNT(*) FROM kapolda_directives WHERE report_id = ?", [$closed]);
        T::contains('hanya dapat diberikan pada perkara yang masih aktif', $attention(Http::as('kapolri'), $closed));
        T::same($before, (int)Db::value("SELECT COUNT(*) FROM kapolda_directives WHERE report_id = ?", [$closed]));
    },

    'atensi mempertahankan EMERGENCY & tidak dobel selama instruksi terbuka' => function () use ($attention, $newReport) {
        $id = $newReport('atensi darurat', ['urgency_level' => 'EMERGENCY']);
        $kapolda = Http::as('kapolri');
        T::contains('berhasil diterbitkan', $attention($kapolda, $id));
        T::same('EMERGENCY', Db::value("SELECT urgency_level FROM reports WHERE id = ?", [$id]));
        T::contains('belum diselesaikan', $attention($kapolda, $id), 'atensi kedua');
        T::same(1, (int)Db::value("SELECT COUNT(*) FROM kapolda_directives WHERE report_id = ?", [$id]));
    },

    'target instruksi mengikuti disposisi (tanpa Satker default)' => function () use ($attention, $newReport, $dispatch) {
        $id = $newReport('atensi sebelum disposisi');
        $attention(Http::as('kapolri'), $id);
        T::same(null, Db::value("SELECT target_satker_id FROM kapolda_directives WHERE report_id = ?", [$id]), 'belum didisposisikan');
        $dispatch($id, 3);
        T::same(3, (int)Db::value("SELECT target_satker_id FROM kapolda_directives WHERE report_id = ?", [$id]), 'setelah disposisi');
    },

    'Satker menerima & menyelesaikan instruksi, terlihat di dashboard Kapolri' => function () use ($attention, $newReport, $dispatch, $respond) {
        $id = $newReport('atensi ditanggapi');
        $dispatch($id, 2);
        $attention(Http::as('kapolri'), $id, 'Tuntaskan dalam 1x24 jam (uji)');
        $directiveId = (int)Db::value("SELECT id FROM kapolda_directives WHERE report_id = ?", [$id]);

        $kanit = Http::as('kanit');
        T::contains('Tuntaskan dalam 1x24 jam (uji)', $kanit->get('/satker')->body, 'tampil di inbox Satker');
        T::contains('telah diterima', $respond($kanit, $directiveId, 'acknowledge'));
        T::contains('wajib diisi', $respond($kanit, $directiveId, 'resolve', ''), 'penyelesaian tanpa laporan');
        T::contains('dilaporkan selesai', $respond($kanit, $directiveId, 'resolve', 'Pelaku diamankan (uji)'));

        $row = Db::row("SELECT status, response_notes FROM kapolda_directives WHERE id = ?", [$directiveId]);
        T::same(['RESOLVED', 'Pelaku diamankan (uji)'], [$row['status'], $row['response_notes']]);
        T::same(2, (int)Db::value("SELECT COUNT(*) FROM report_timeline WHERE report_id = ? AND title = 'Tanggapan Atensi Kapolri'", [$id]), 'jejak timeline');
        T::contains('Pelaku diamankan (uji)', Http::as('kapolri')->get('/executive')->body, 'tanggapan terlihat Kapolri');
    },

    'Satker lain tidak dapat menanggapi instruksi' => function () use ($attention, $newReport, $dispatch, $respond) {
        $id = $newReport('atensi satker lain');
        $dispatch($id, 3);
        $attention(Http::as('kapolri'), $id);
        $directiveId = (int)Db::value("SELECT id FROM kapolda_directives WHERE report_id = ?", [$id]);
        T::contains('bukan untuk Satker Anda', $respond(Http::as('kanit'), $directiveId, 'acknowledge'));
        T::same('PENDING', Db::value("SELECT status FROM kapolda_directives WHERE id = ?", [$directiveId]));
    },

    'penutupan perkara menyelesaikan instruksi yang masih terbuka' => function () use ($attention, $newReport, $dispatch) {
        $id = $newReport('atensi ditutup');
        $dispatch($id, 2);
        $attention(Http::as('kapolri'), $id);
        $penyidikRian = (int)Db::value("SELECT id FROM users WHERE email = 'penyidik.rian@sigap.polri.go.id'");
        $kanit = Http::as('kanit');
        $kanit->post('/satker/assign', ['_csrf_token' => $kanit->get('/satker')->csrf(), 'report_id' => $id, 'investigator_id' => $penyidikRian]);
        $penyidik = Http::as('penyidik');
        $penyidik->post('/investigator/closeCase', ['_csrf_token' => $penyidik->get("/investigator/detail/{$id}")->csrf(), 'report_id' => $id, 'resolution_notes' => 'selesai']);
        T::same('CLOSED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]));
        T::same('RESOLVED', Db::value("SELECT status FROM kapolda_directives WHERE report_id = ?", [$id]));
    },
];
