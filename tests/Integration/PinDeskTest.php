<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

/** Laporan web baru (jembatan Satu Nomor ikut menerbitkan nomor POL-... dengan PIN yang sama) */
$newReport = function (): array {
    [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'layanan pin', 'reporter_phone' => '081234567891']);
    return Db::row("SELECT id, ticket_number, security_pin FROM reports WHERE ticket_number = ?", [$ticket]);
};
$desk = function (Http $c, string $action, string $ticket, array $extra = []) {
    $csrf = $c->get('/pin?ticket=' . urlencode($ticket))->csrf();
    return $c->post('/pin/' . $action, ['_csrf_token' => $csrf, 'ticket' => $ticket] + $extra);
};
$verified = ['identity_verified' => '1', 'reason' => 'ZZTEST pelapor datang membawa KTP'];
$pinOnPage = function (string $html): ?string {
    preg_match('/tracking-\[0\.4em\] text-slate-50">(\d{6})</', $html, $m);
    return $m[1] ?? null;
};

return [
    'cari tiket: data pelapor tersamar untuk verifikasi identitas' => function () use ($newReport) {
        Db::exec("DELETE FROM throttle_events WHERE bucket = 'pin_desk_user'");
        $r = $newReport();
        $page = Http::as('spkt')->get('/pin?ticket=' . $r['ticket_number']);
        T::same(200, $page->status);
        T::contains($r['ticket_number'], $page->body);
        T::notContains('081234567891', $page->body, 'nomor HP utuh tidak tampil');
        T::notContains('3171000000000001', $page->body, 'NIK utuh tidak tampil');
        T::notContains($r['security_pin'], $page->body, 'PIN tidak tampil sebelum diminta');
        T::contains('tidak ditemukan', Http::as('spkt')->get('/pin?ticket=LP-POLRI-209901-9999')->body);
    },

    'lihat PIN: wajib verifikasi identitas & alasan, tercatat di audit' => function () use ($newReport, $desk, $verified, $pinOnPage) {
        Db::exec("DELETE FROM throttle_events WHERE bucket = 'pin_desk_user'");
        $r = $newReport();
        $spkt = Http::as('spkt');

        $res = $desk($spkt, 'reveal', $r['ticket_number'], ['reason' => 'ZZTEST tanpa centang verifikasi']);
        T::same(null, $pinOnPage($res->body), 'tanpa centang verifikasi: PIN tidak tampil');
        T::contains('identitas pelapor sudah diverifikasi', $res->body);

        $res = $desk($spkt, 'reveal', $r['ticket_number'], ['identity_verified' => '1', 'reason' => 'x']);
        T::same(null, $pinOnPage($res->body), 'alasan terlalu pendek ditolak');

        $res = $desk($spkt, 'reveal', $r['ticket_number'], $verified);
        T::same($r['security_pin'], $pinOnPage($res->body), 'PIN yang tersimpan');
        T::contains('no-store', (string)$res->header('cache-control'), 'tidak disimpan cache browser');
        T::same(1, (int)Db::value("SELECT COUNT(*) FROM audit_logs WHERE action = 'pin.reveal' AND report_id = ?", [$r['id']]));
    },

    'reset PIN: PIN lama tidak berlaku, tiket Satu Nomor pasangannya ikut diperbarui, kunci salah-PIN dibuka' => function () use ($newReport, $desk, $verified, $pinOnPage) {
        Db::exec("DELETE FROM throttle_events WHERE bucket = 'pin_desk_user'");
        $r = $newReport();
        $pol = (string)Db::value("SELECT tracking_number FROM ops_global_tracks WHERE external_reference_no = ?", [$r['ticket_number']]);
        T::true($pol !== '', 'laporan punya pasangan Satu Nomor');

        // Warga sempat salah PIN berkali-kali sehingga tiket terkunci
        for ($i = 0; $i < 6; $i++) {
            Db::exec("INSERT INTO throttle_events (bucket, throttle_key) VALUES ('pin_ticket_ip', ?)", [$r['ticket_number'] . '|127.0.0.1']);
        }

        $res = $desk(Http::as('spkt'), 'reset', $r['ticket_number'], $verified);
        $newPin = $pinOnPage($res->body);
        T::true($newPin !== null && $newPin !== $r['security_pin'], 'PIN baru ditampilkan');
        T::same($newPin, Db::value("SELECT security_pin FROM reports WHERE id = ?", [$r['id']]));
        T::true(password_verify($newPin, (string)Db::value("SELECT security_pin_hash FROM ops_global_tracks WHERE tracking_number = ?", [$pol])), 'POL pasangan ikut PIN baru');
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM throttle_events WHERE bucket = 'pin_ticket_ip' AND throttle_key LIKE ?", [$r['ticket_number'] . '|%']), 'kunci dibuka');
        T::same(1, (int)Db::value("SELECT COUNT(*) FROM audit_logs WHERE action = 'pin.reset' AND report_id = ?", [$r['id']]));

        // "Ruang Klarifikasi" hanya tampil bila tiket terbuka untuk pelapor
        T::notContains('Ruang Klarifikasi', (new Http(TEST_BASE_URL))->trackWithPin($r['ticket_number'], $r['security_pin'])->body, 'PIN lama ditolak');
        T::contains('Ruang Klarifikasi', (new Http(TEST_BASE_URL))->trackWithPin($r['ticket_number'], $newPin)->body, 'PIN baru membuka tiket');
    },

    'tiket Kehilangan dan Temuan: hanya dapat di-reset (PIN tersimpan sebagai hash)' => function () use ($desk, $verified, $pinOnPage) {
        Db::exec("DELETE FROM throttle_events WHERE bucket = 'pin_desk_user'");
        Db::exec(
            "INSERT INTO lost_found_items (security_pin_hash, report_type, item_category, title, description, event_date, event_location, reporter_name, reporter_phone)
             VALUES (?, 'LOST', 'DOKUMEN', ?, 'Dompet berisi KTP hilang di angkot', CURDATE(), 'Jl. Uji', 'Warga Uji', '081200000000')",
            [password_hash('111111', PASSWORD_BCRYPT), Db::PREFIX . 'pin ht']
        );
        $id = (int)Db::value("SELECT id FROM lost_found_items WHERE title = ?", [Db::PREFIX . 'pin ht']);
        $code = sprintf('HT-%s-%05d', date('Ym'), $id);
        Db::exec("UPDATE lost_found_items SET code = ? WHERE id = ?", [$code, $id]);

        $spkt = Http::as('spkt');
        $page = $spkt->get('/pin?ticket=' . $code)->body;
        T::notContains('/pin/reveal', $page, 'tidak ada tombol Lihat PIN');
        T::same(null, $pinOnPage($desk($spkt, 'reveal', $code, $verified)->body), 'lihat PIN ditolak');

        $newPin = $pinOnPage($desk($spkt, 'reset', $code, $verified)->body);
        T::true($newPin !== null, 'PIN baru dibuat');
        $hash = (string)Db::value("SELECT security_pin_hash FROM lost_found_items WHERE id = ?", [$id]);
        T::true(password_verify($newPin, $hash) && !password_verify('111111', $hash), 'hash diperbarui, PIN lama tidak berlaku');
    },

    'hanya Operator SPKT & Superadmin; batas layanan per petugas' => function () use ($newReport, $desk, $verified, $pinOnPage) {
        $r = $newReport();
        foreach (['kapolri', 'kanit', 'penyidik', 'itwasum', 'warga'] as $role) {
            $res = Http::as($role)->get('/pin?ticket=' . $r['ticket_number']);
            T::true($res->status === 302 && !str_contains($res->location(), '/pin'), "{$role} ditolak");
        }
        T::same(200, Http::as('superadmin')->get('/pin')->status);

        $spktId = (int)Db::value("SELECT id FROM users WHERE email = 'spkt@sigap.polri.go.id'");
        Db::exec("DELETE FROM throttle_events WHERE bucket = 'pin_desk_user'");
        for ($i = 0; $i < 30; $i++) {
            Db::exec("INSERT INTO throttle_events (bucket, throttle_key) VALUES ('pin_desk_user', ?)", [(string)$spktId]);
        }
        $res = $desk(Http::as('spkt'), 'reveal', $r['ticket_number'], $verified);
        T::same(null, $pinOnPage($res->body));
        T::contains('Batas layanan PIN', $res->body);
        Db::exec("DELETE FROM throttle_events WHERE bucket = 'pin_desk_user'");
    },
];
