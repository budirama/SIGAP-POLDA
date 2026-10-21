<?php

use App\Models\Disposition;
use Tests\AssertionFailed;
use Tests\Db;
use Tests\Http;
use Tests\T;

$unit = fn(string $code): int => (int)Db::value("SELECT id FROM satkers WHERE code = ?", [$code]);

/** Login personil dengan sandi demo (akun satuan fungsi tidak ada di switcher 1-klik) */
$login = function (string $email): Http {
    Db::exec("DELETE FROM throttle_events WHERE bucket IN ('login_id', 'login_ip')");
    $client = new Http(TEST_BASE_URL);
    $res = $client->post('/auth/authenticate', [
        '_csrf_token' => $client->get('/auth/login')->csrf(),
        'identifier'  => $email,
        'password'    => 'password123',
    ]);
    if (str_contains($res->location(), '/auth/login')) {
        throw new AssertionFailed("login {$email} gagal (jalankan database/seeders_tiket_satker.sql)");
    }
    return $client;
};

$newReport = function (array $overrides = []): int {
    [, $ticket] = Db::createReport($overrides + ['title' => Db::PREFIX . 'tiket satker']);
    return (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);
};

$post = function (Http $client, string $path, array $fields): void {
    $client->post($path, ['_csrf_token' => $client->get(str_starts_with($path, '/spkt') ? '/spkt' : '/satker')->csrf()] + $fields);
};

$latest = fn(int $reportId) => Db::row("SELECT * FROM report_dispositions WHERE report_id = ? ORDER BY id DESC LIMIT 1", [$reportId]);

return [
    'rekomendasi: kategori → fungsi, alamat TKP → wilayah, dengan fungsi pengganti' => function () use ($unit) {
        $d = new Disposition();
        $lantas = (int)Db::value("SELECT id FROM report_categories WHERE code = 'CAT-LANTAS'");
        $jatanras = (int)Db::value("SELECT id FROM report_categories WHERE code = 'CAT-JATANRAS'");
        $narkoba = (int)Db::value("SELECT id FROM report_categories WHERE code = 'CAT-NARKOBA'");
        $jakbar = 'Flyover Pesing, Daan Mogot, Grogol Petamburan, Jakarta Barat';

        T::same($unit('POLRES-METRO-JAKARTA-BARAT-SATLANTAS'), $d->recommend(['category_id' => $lantas, 'incident_location' => $jakbar])['satker_id']);
        T::same($unit('POLRES-METRO-JAKARTA-BARAT-SATRESKRIM'), $d->recommend(['category_id' => $jatanras, 'incident_location' => $jakbar])['satker_id'], 'Polres tanpa Jatanras → Satreskrim');

        $sby = $d->recommend(['category_id' => $narkoba, 'incident_location' => 'Lokasi tidak dikenal', 'latitude' => -7.29, 'longitude' => 112.75]);
        T::same($unit('POLDA-JATIM-DITRESNARKOBA'), $sby['satker_id'], 'Polda terdekat dari koordinat');
        T::same($unit('DITTIPIDNARKOBA'), $d->recommend(['category_id' => $narkoba, 'incident_location' => ''])['satker_id'], 'tanpa wilayah → direktorat Mabes');
        T::same($unit('POLDA-METRO-JATANRAS'), $d->functionalUnit($unit('POLDA-METRO'), 'JATANRAS')['id'], 'Subdit Jatanras di Polda');
    },

    'daftar satuan per wilayah: wilayah + satuan fungsinya, tanpa satuan Polres bawahan' => function () use ($unit) {
        $spkt = Http::as('spkt');
        $units = array_column(json_decode($spkt->get('/spkt/units/' . $unit('POLDA-METRO'))->body, true)['units'], 'id');
        T::same($unit('POLDA-METRO'), $units[0], 'pimpinan wilayah pertama');
        T::true(in_array($unit('POLDA-METRO-DITRESKRIMUM'), $units, true), 'Ditreskrimum');
        T::true(in_array($unit('POLDA-METRO-JATANRAS'), $units, true), 'Subdit Jatanras');
        T::true(!in_array($unit('POLRES-METRO-JAKARTA-BARAT-SATRESKRIM'), $units, true), 'Satreskrim Polres dipilih lewat wilayah Polres');
    },

    'alur tiket: kirim → teruskan ke jajaran → kembalikan → kirim ulang → terima → Sprintgas' => function () use ($unit, $login, $newReport, $post, $latest) {
        $id = $newReport(['urgency_level' => 'HIGH', 'incident_location' => 'Jl. Daan Mogot, Jakarta Barat']);
        $spkt = Http::as('spkt');
        T::contains('Rekomendasi', $spkt->get('/spkt?search=' . Db::PREFIX)->body);

        // SPKT → Ditreskrimum Polda Metro (prioritas HIGH: batas terima 6 jam)
        $post($spkt, '/spkt/dispatch', ['report_id' => $id, 'satker_id' => $unit('POLDA-METRO-DITRESKRIMUM'), 'dispatch_notes' => 'Tangani segera']);
        $t = $latest($id);
        T::same(['PENDING', null], [$t['status'], $t['from_satker_id']]);
        $hours = (strtotime($t['ack_deadline']) - strtotime($t['created_at'])) / 3600;
        T::true(abs($hours - 6) < 0.1, "batas terima 6 jam (dapat {$hours})");

        $dir = $login('dirreskrimum.metro@sigap.polri.go.id');
        $inbox = $dir->get('/satker')->body;
        T::contains('/satker/terima', $inbox);
        T::contains('Menunggu Diterima', $inbox);

        // Terusan ke luar jajaran ditolak, ke Satreskrim Polres jajaran diterima
        $post($dir, '/satker/teruskan', ['report_id' => $id, 'target_satker_id' => $unit('POLDA-JATIM-DITRESKRIMUM'), 'notes' => 'x']);
        T::same($unit('POLDA-METRO-DITRESKRIMUM'), (int)Db::value("SELECT current_satker_id FROM reports WHERE id = ?", [$id]), 'bukan jajaran');
        $post($dir, '/satker/teruskan', ['report_id' => $id, 'target_satker_id' => $unit('POLRES-METRO-JAKARTA-BARAT-SATRESKRIM'), 'notes' => 'Lokus di Jakbar']);
        T::same('FORWARDED', Db::value("SELECT status FROM report_dispositions WHERE report_id = ? ORDER BY id LIMIT 1", [$id]));
        $t = $latest($id);
        T::same([$unit('POLDA-METRO-DITRESKRIMUM'), 'PENDING'], [(int)$t['from_satker_id'], $t['status']]);

        // Satker lain tidak dapat menerima tiket ini
        $post($login('kasatlantas.jakbar@sigap.polri.go.id'), '/satker/terima', ['report_id' => $id]);
        T::same('PENDING', $latest($id)['status'], 'Satlantas bukan pemegang tiket');

        // Satreskrim mengembalikan ke SPKT (alasan wajib)
        $reskrim = $login('kasatreskrim.jakbar@sigap.polri.go.id');
        $post($reskrim, '/satker/kembalikan', ['report_id' => $id, 'notes' => 'x']);
        T::same('PENDING', $latest($id)['status'], 'alasan terlalu pendek');
        $post($reskrim, '/satker/kembalikan', ['report_id' => $id, 'notes' => 'Lokus sebenarnya di wilayah Tangerang']);
        T::same(['VERIFIED_SPKT', null], array_values(Db::row("SELECT status, current_satker_id FROM reports WHERE id = ?", [$id])));
        T::same('RETURNED', $latest($id)['status']);
        T::contains('Lokus sebenarnya di wilayah Tangerang', $spkt->get('/spkt')->body, 'panel dikembalikan di SPKT');

        // Kirim ulang langsung ke Satreskrim, lalu diterima & penyidik ditunjuk
        $post($spkt, '/spkt/dispatch', ['report_id' => $id, 'satker_id' => $unit('POLRES-METRO-JAKARTA-BARAT-SATRESKRIM'), 'dispatch_notes' => 'Kirim ulang']);
        $post($reskrim, '/satker/terima', ['report_id' => $id, 'notes' => 'Siap']);
        T::same('ACCEPTED', $latest($id)['status']);
        T::same(1, (int)Db::value("SELECT COUNT(*) FROM report_timeline WHERE report_id = ? AND title = 'Tiket Diterima Satker'", [$id]));

        $penyidik = (int)Db::value("SELECT id FROM users WHERE email = 'penyidik.reskrim.jakbar@sigap.polri.go.id'");
        $post($reskrim, '/satker/assign', ['report_id' => $id, 'investigator_id' => $penyidik]);
        T::same('ASSIGNED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]));
        $post($reskrim, '/satker/kembalikan', ['report_id' => $id, 'notes' => 'Terlambat dikembalikan setelah Sprintgas']);
        T::same('ASSIGNED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]), 'tidak bisa dikembalikan setelah penyidik ditunjuk');

        T::contains('Riwayat Tiket Disposisi', Http::as('superadmin')->get("/investigator/detail/{$id}")->body);
    },

    'menunjuk penyidik otomatis menerima tiket yang masih menunggu' => function () use ($unit, $login, $newReport, $post, $latest) {
        $id = $newReport();
        $post(Http::as('spkt'), '/spkt/dispatch', ['report_id' => $id, 'satker_id' => $unit('POLRES-METRO-JAKARTA-BARAT-SATRESKRIM'), 'dispatch_notes' => 'uji']);
        $penyidik = (int)Db::value("SELECT id FROM users WHERE email = 'penyidik.reskrim.jakbar@sigap.polri.go.id'");
        $post($login('kasatreskrim.jakbar@sigap.polri.go.id'), '/satker/assign', ['report_id' => $id, 'investigator_id' => $penyidik]);
        T::same('ACCEPTED', $latest($id)['status']);
    },

    'eskalasi: tiket lewat batas terima tampil di SPKT & satuan induk, dicatat sekali' => function () use ($unit, $login, $newReport, $post, $latest) {
        $id = $newReport();
        $post(Http::as('spkt'), '/spkt/dispatch', ['report_id' => $id, 'satker_id' => $unit('POLRES-METRO-JAKARTA-BARAT-SATRESKRIM'), 'dispatch_notes' => 'uji']);
        Db::exec("UPDATE report_dispositions SET ack_deadline = NOW() - INTERVAL 1 HOUR WHERE id = ?", [$latest($id)['id']]);

        $ticket = Db::value("SELECT ticket_number FROM reports WHERE id = ?", [$id]);
        T::contains($ticket, Http::as('spkt')->get('/spkt')->body, 'panel eskalasi SPKT');
        T::true($latest($id)['escalated_at'] !== null, 'ditandai eskalasi');
        T::contains($ticket, $login('kapolda.metro@sigap.polri.go.id')->get('/satker')->body, 'tampil di inbox Polda induk');

        $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 2) . '/scripts/disposition_watchdog.php'));
        T::contains('Eskalasi tiket disposisi', (string)$out);
        T::same(1, (int)Db::value("SELECT COUNT(*) FROM report_timeline WHERE report_id = ? AND title LIKE 'Eskalasi%'", [$id]), 'tidak dicatat ganda');
    },
];
