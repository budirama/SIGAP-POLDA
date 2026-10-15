<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$satkerKrimum = 2;  // Ditreskrimum (Kanit & Penyidik Rian di seeder)
$penyidikRian = (int)Db::value("SELECT id FROM users WHERE email = 'penyidik.rian@sigap.polda.go.id'");
$irwasda = (int)Db::value("SELECT id FROM users WHERE email = 'irwasda@sigap.polda.go.id'");

// State bersama antar langkah alur kerja
$state = new ArrayObject();

return [
    'penyidik hanya dapat membuka kasus yang ditugaskan kepadanya' => function () use ($penyidikRian) {
        $penyidik = Http::as('penyidik');
        $own = (int)Db::value("SELECT id FROM reports WHERE assigned_investigator_id = ? LIMIT 1", [$penyidikRian]);
        $other = (int)Db::value("SELECT id FROM reports WHERE assigned_investigator_id IS NULL OR assigned_investigator_id <> ? LIMIT 1", [$penyidikRian]);
        T::same(200, $penyidik->get("/investigator/detail/{$own}")->status, 'kasus sendiri');
        $res = $penyidik->get("/investigator/detail/{$other}");
        T::same(302, $res->status, 'kasus orang lain');
        T::contains('bukan dalam kewenangan Anda', $penyidik->flash());
    },

    'Kanit tidak dapat membuka kasus Satker lain' => function () use ($satkerKrimum) {
        $kanit = Http::as('kanit');
        $other = (int)Db::value("SELECT id FROM reports WHERE current_satker_id <> ? LIMIT 1", [$satkerKrimum]);
        T::same(302, $kanit->get("/investigator/detail/{$other}")->status);
        T::contains('bukan dalam kewenangan Anda', $kanit->flash());
    },

    'SPKT tidak dapat menolak laporan tanpa CSRF atau yang sudah ditutup' => function () {
        $spkt = Http::as('spkt');
        $closed = (int)Db::value("SELECT id FROM reports WHERE status = 'CLOSED' LIMIT 1");
        $spkt->post('/spkt/reject', ['report_id' => $closed, 'rejection_reason' => 'x']);
        T::contains('Token CSRF tidak valid', $spkt->flash());
        $spkt->post('/spkt/reject', ['_csrf_token' => $spkt->get('/spkt')->csrf(), 'report_id' => $closed, 'rejection_reason' => 'x']);
        T::contains('Hanya laporan yang menunggu verifikasi', $spkt->flash());
        T::same('CLOSED', Db::value("SELECT status FROM reports WHERE id = ?", [$closed]));
    },

    'alur penuh: lapor → disposisi → tugaskan → SP2HP → tutup → SKM' => function () use ($state, $satkerKrimum, $penyidikRian, $irwasda) {
        // 1. Warga melapor
        [$warga, $ticket] = Db::createReport(['title' => Db::PREFIX . 'alur penuh']);
        $id = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);
        T::same('SUBMITTED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]));

        // 2. SPKT mendisposisikan ke Ditreskrimum
        $spkt = Http::as('spkt');
        $spkt->post('/spkt/dispatch', ['_csrf_token' => $spkt->get('/spkt')->csrf(), 'report_id' => $id, 'satker_id' => $satkerKrimum, 'dispatch_notes' => 'uji']);
        T::same('DISPATCHED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]), 'setelah disposisi');

        // 3. Kanit: penyidik dari Satker lain ditolak, penyidik Satker sendiri diterima
        $kanit = Http::as('kanit');
        $csrf = $kanit->get('/satker')->csrf();
        $kanit->post('/satker/assign', ['_csrf_token' => $csrf, 'report_id' => $id, 'investigator_id' => $irwasda]);
        T::contains('bukan personil Satker pemilik laporan', $kanit->flash());
        $kanit->post('/satker/assign', ['_csrf_token' => $csrf, 'report_id' => $id, 'investigator_id' => $penyidikRian, 'sprintgas_number' => 'SP.Gas/UJI']);
        T::same('ASSIGNED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]), 'setelah penugasan');

        // 4. Penyidik: status SP2HP liar ditolak, SP2HP sah diterbitkan
        $penyidik = Http::as('penyidik');
        $csrf = $penyidik->get("/investigator/detail/{$id}")->csrf();
        $penyidik->post('/investigator/updateSp2hp', ['_csrf_token' => $csrf, 'report_id' => $id, 'public_notes' => 'uji', 'status' => 'REJECTED']);
        T::contains('Status perkara tidak valid', $penyidik->flash());
        $penyidik->post('/investigator/updateSp2hp', ['_csrf_token' => $csrf, 'report_id' => $id, 'sp2hp_number' => 'SP2HP/UJI', 'public_notes' => 'Catatan publik uji', 'internal_notes' => 'Catatan rahasia uji', 'status' => 'INVESTIGATING']);
        T::same('INVESTIGATING', Db::value("SELECT status FROM reports WHERE id = ?", [$id]), 'setelah SP2HP');
        T::same('ASSIGNED', Db::value("SELECT status_from FROM report_timeline WHERE report_id = ? AND is_sp2hp = 1 ORDER BY id DESC LIMIT 1", [$id]), 'status_from timeline');

        // 5. Pelapor melihat catatan publik, tidak melihat catatan rahasia
        $track = $warga->get("/report/track?ticket={$ticket}")->body;
        T::contains('Catatan publik uji', $track);
        T::notContains('Catatan rahasia uji', $track);

        // 6. SKM belum boleh sebelum ditutup
        $warga->post('/report/submitFeedback', ['_csrf_token' => $warga->get("/report/track?ticket={$ticket}")->csrf(), 'ticket_number' => $ticket, 'rating_stars' => '5']);
        T::contains('setelah perkara dinyatakan selesai', $warga->flash());

        // 7. Penyidik menutup kasus
        $penyidik->post('/investigator/closeCase', ['_csrf_token' => $csrf, 'report_id' => $id, 'resolution_notes' => 'selesai uji']);
        T::same('CLOSED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]), 'setelah ditutup');

        // 8. SKM: nilai di luar 1-5 dibatasi
        $warga->post('/report/submitFeedback', ['_csrf_token' => $warga->get("/report/track?ticket={$ticket}")->csrf(), 'ticket_number' => $ticket, 'rating_stars' => '99', 'speed_score' => '0', 'fairness_score' => '4', 'comments' => 'uji']);
        T::same(['rating_stars' => 5, 'speed_score' => 1, 'fairness_score' => 4], array_map('intval', Db::row("SELECT rating_stars, speed_score, fairness_score FROM report_feedbacks WHERE report_id = ?", [$id])));

        $state['closed_id'] = $id;
    },

    'SPKT dapat menolak laporan baru dengan alasan' => function () {
        [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'untuk ditolak']);
        $id = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);
        $spkt = Http::as('spkt');
        $spkt->post('/spkt/reject', ['_csrf_token' => $spkt->get('/spkt')->csrf(), 'report_id' => $id, 'rejection_reason' => 'Bukan tindak pidana (uji)']);
        T::same('REJECTED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]));
        T::same('SUBMITTED', Db::value("SELECT status_from FROM report_timeline WHERE report_id = ? AND status_to = 'REJECTED'", [$id]));
    },
];
