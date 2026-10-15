<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$lastAudit = function (string $action): ?array {
    return Db::row("SELECT * FROM audit_logs WHERE action = ? ORDER BY id DESC LIMIT 1", [$action]);
};

return [
    'login gagal & berhasil tercatat' => function () use ($lastAudit) {
        Db::exec("DELETE FROM throttle_events");
        $client = new Http(TEST_BASE_URL);
        $login = fn(string $password) => $client->post('/auth/authenticate', [
            '_csrf_token' => $client->get('/auth/login')->csrf(), 'identifier' => 'spkt@sigap.polda.go.id', 'password' => $password,
        ]);

        $login('salah-audit');
        $failed = $lastAudit('auth.login_failed');
        T::true($failed !== null && $failed['user_id'] === null, 'login gagal tanpa user');
        T::contains('spkt@sigap.polda.go.id', (string)$failed['detail']);

        $login('password123');
        $ok = $lastAudit('auth.login');
        T::same((int)Db::value("SELECT id FROM users WHERE email = 'spkt@sigap.polda.go.id'"), (int)$ok['user_id']);
        T::same('ROLE_SPKT_OPERATOR', $ok['user_role']);
        Db::exec("DELETE FROM throttle_events");
    },

    'akses berkas whistleblower & unduhan bukti tercatat' => function () use ($lastAudit) {
        $png = sys_get_temp_dir() . '/sigap_test_bukti.png';
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'audit whistleblower', 'is_anonymous' => '1'], [new CURLFile($png, 'image/png', 'bukti.png')]);
        $id = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);
        $spkt = Http::as('spkt');
        $spkt->post('/spkt/dispatch', ['_csrf_token' => $spkt->get('/spkt')->csrf(), 'report_id' => $id, 'satker_id' => 2, 'dispatch_notes' => 'uji']);
        T::same($id, (int)$lastAudit('report.dispatch')['report_id'], 'disposisi tercatat');

        $kanit = Http::as('kanit');
        $kanit->get("/investigator/detail/{$id}");
        $view = $lastAudit('case.view');
        T::same($id, (int)$view['report_id']);
        T::same((int)Db::value("SELECT id FROM users WHERE email = 'kanit.krimum@sigap.polda.go.id'"), (int)$view['user_id']);
        T::contains('whistleblower', (string)$view['detail']);

        $attId = (int)Db::value("SELECT id FROM report_attachments WHERE report_id = ?", [$id]);
        $kanit->get("/file/attachment/{$attId}");
        T::same($id, (int)$lastAudit('file.download')['report_id'], 'unduhan tercatat');
    },

    'halaman audit hanya untuk Superadmin & Itwasda, dengan filter' => function () {
        foreach (['kapolda', 'spkt', 'kanit', 'penyidik'] as $role) {
            T::same(302, Http::as($role)->get('/audit')->status, $role);
        }
        $itwasda = Http::as('itwasda');
        $html = $itwasda->get('/audit?action=auth.quick_login')->body;
        T::contains('Jejak Audit', $html);
        T::contains('auth.quick_login', $html);
        T::notContains('whitespace-nowrap">case.view</td>', $html, 'filter aksi diterapkan');
        T::same(200, Http::as('superadmin')->get('/audit?q=' . urlencode('LP-POLDA'))->status);
    },
];
