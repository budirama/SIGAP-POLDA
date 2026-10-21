<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$track = fn() => (string)Db::value("SELECT tracking_number FROM ops_global_tracks ORDER BY id LIMIT 1");

return [
    'Kapolri: menu Satu Nomor di bilah atas & sidebar, ringkasan hingga detail dapat dibuka' => function () use ($track) {
        $logMark = (int)Db::value("SELECT COALESCE(MAX(id), 0) FROM ops_audit_pdp_logs");
        $kapolri = Http::as('kapolri');
        $exec = $kapolri->get('/executive')->body;
        T::contains('/oneservice/dashboard', $exec, 'tombol Satu Nomor di bilah atas Kapolri');
        T::contains('/oneservice/daftar', $exec, 'tombol detail perkara');

        $dashboard = $kapolri->get('/oneservice/dashboard')->body;
        T::contains('/oneservice/daftar?stalled=1', $dashboard, 'angka perkara macet dapat ditelusuri');
        T::contains('/oneservice/daftar?status=TUNTAS', $dashboard, 'angka perkara tuntas dapat ditelusuri');
        T::contains('SATU NOMOR, SATU JEJAK', $dashboard, 'menu sidebar');

        $list = $kapolri->get('/oneservice/daftar?stalled=1');
        T::same(200, $list->status);
        T::contains('Ringkasan per Satker', $list->body);
        T::contains('Mode pantau pimpinan', $list->body);
        $stalled = (int)Db::value("SELECT COUNT(*) FROM ops_global_tracks WHERE is_stalled = 1");
        T::contains("dari <b class=\"text-slate-200\">{$stalled}</b>", $list->body, 'jumlah = data perkara macet');

        $no = $track();
        $detail = $kapolri->get('/oneservice/detail/' . $no);
        T::same(200, $detail->status);
        T::contains($no, $detail->body);
        T::contains('Riwayat SP2HP', $detail->body);
        T::true((int)Db::value("SELECT COUNT(*) FROM ops_audit_pdp_logs WHERE tracking_number = ? AND accessed_data = 'CASE_DETAIL'", [$no]) > 0, 'pembukaan detail tercatat');
        T::same(302, $kapolri->get('/oneservice/detail/BUKAN-NOMOR')->status, 'nomor tidak sah kembali ke daftar');
        Db::exec("DELETE FROM ops_audit_pdp_logs WHERE id > ?", [$logMark]); // log uji saja
    },

    'detail menyamarkan NIK; buka data pribadi wajib CSRF & tercatat di audit' => function () use ($track) {
        $no = $track();
        $nikRaw = \App\Core\OpsPdp::decrypt(Db::value("SELECT citizen_nik_encrypted FROM ops_global_tracks WHERE tracking_number = ?", [$no]));
        $logMark = (int)Db::value("SELECT COALESCE(MAX(id), 0) FROM ops_audit_pdp_logs");
        $kapolri = Http::as('kapolri');
        $page = $kapolri->get('/oneservice/detail/' . $no)->body;
        if ($nikRaw) {
            T::notContains($nikRaw, $page, 'NIK utuh tidak ada di halaman');
        }

        T::same(403, $kapolri->post('/oneservice/apiRevealPii', ['tracking_number' => $no, 'justification' => 'uji'])->status, 'tanpa CSRF ditolak');
        preg_match("/fd.append\('_csrf_token', \"([a-f0-9]+)\"\)/", $page, $m);
        $before = (int)Db::value("SELECT COUNT(*) FROM ops_audit_pdp_logs WHERE tracking_number = ? AND accessed_data = 'NIK_REVEAL'", [$no]);
        $res = json_decode($kapolri->post('/oneservice/apiRevealPii', ['_csrf_token' => $m[1] ?? '', 'tracking_number' => $no, 'justification' => 'ZZTEST audit pimpinan'])->body, true);
        T::same(true, $res['success'] ?? null, 'pimpinan dapat membuka data pribadi');
        T::same($before + 1, (int)Db::value("SELECT COUNT(*) FROM ops_audit_pdp_logs WHERE tracking_number = ? AND accessed_data = 'NIK_REVEAL'", [$no]), 'tercatat');
        Db::exec("DELETE FROM ops_audit_pdp_logs WHERE id > ?", [$logMark]); // log uji saja
    },

    'pimpinan hanya memantau: aksi perubahan ditolak, SP2HP tidak terbit' => function () use ($track) {
        $no = $track();
        $kapolri = Http::as('kapolri');
        $before = (int)Db::value("SELECT COUNT(*) FROM ops_case_milestones WHERE tracking_number = ?", [$no]);
        $csrf = $kapolri->get('/oneservice/cases')->csrf();
        $res = $kapolri->post('/oneservice/cases', ['_csrf_token' => $csrf, 'action' => 'issue_sp2hp', 'tracking_number' => $no,
                                                  'stage_code' => 'A2', 'title' => 'ZZTEST', 'public_notes' => 'x', 'internal_notes' => 'x']);
        T::same(302, $res->status, 'dialihkan');
        T::same($before, (int)Db::value("SELECT COUNT(*) FROM ops_case_milestones WHERE tracking_number = ?", [$no]), 'tidak ada SP2HP baru');
        T::contains('Mode pantau pimpinan', $kapolri->get('/oneservice/cases')->body . $kapolri->get('/oneservice/daftar')->body);
    },

    'warga & operator Posko tidak dapat membuka backoffice OneService; CSRF wajib untuk aksi' => function () use ($track) {
        $no = $track();
        foreach (['warga', 'poskoOpSby'] as $role) {
            $c = Http::as($role);
            foreach (['/oneservice', '/oneservice/daftar', '/oneservice/cases', '/oneservice/detail/' . $no] as $path) {
                $res = $c->get($path);
                T::true($res->status === 302 && !str_contains($res->location(), '/oneservice'), "{$role} {$path} ditolak");
            }
            T::same(403, $c->post('/oneservice/apiRevealPii', ['tracking_number' => $no])->status, "{$role} tidak dapat membuka NIK");
        }

        $spkt = Http::as('spkt');
        $before = (int)Db::value("SELECT COUNT(*) FROM ops_case_milestones WHERE tracking_number = ?", [$no]);
        $spkt->post('/oneservice/cases', ['action' => 'issue_sp2hp', 'tracking_number' => $no, 'stage_code' => 'A2', 'title' => 'ZZTEST', 'public_notes' => 'x']);
        T::same($before, (int)Db::value("SELECT COUNT(*) FROM ops_case_milestones WHERE tracking_number = ?", [$no]), 'tanpa token CSRF ditolak');
    },

    'halaman siber publik tidak menampilkan rekening terlapor; personil tetap melihat' => function () {
        $account = (string)Db::value("SELECT account_number FROM ops_cyber_fraud ORDER BY id LIMIT 1");
        T::true($account !== '', 'ada data aduan siber demo');
        T::notContains($account, (new Http(TEST_BASE_URL))->get('/oneservice/cyber')->body, 'publik');
        T::contains($account, Http::as('humas')->get('/oneservice/cyber')->body, 'personil');
    },
];
