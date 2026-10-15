<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$case1 = (int)Db::value("SELECT id FROM reports WHERE current_satker_id = 2 AND status = 'INVESTIGATING' LIMIT 1");
$ticket1 = (string)Db::value("SELECT ticket_number FROM reports WHERE id = ?", [$case1]);

return [
    'pimpinan & pengawas membuka workspace kasus dalam mode baca' => function () use ($case1) {
        foreach (['kapolri', 'kasatker', 'itwasum'] as $role) {
            $res = Http::as($role)->get("/investigator/detail/{$case1}");
            T::same(200, $res->status, $role);
            T::contains('Mode baca', $res->body, $role);
            T::notContains('/investigator/updateSp2hp', $res->body, "{$role}: form SP2HP");
            T::notContains('/investigator/closeCase', $res->body, "{$role}: form tutup kasus");
            T::notContains('/investigator/sendMessage', $res->body, "{$role}: form pesan");
        }
    },

    'penangan kasus tetap melihat form aksi' => function () use ($case1) {
        foreach (['penyidik', 'kanit', 'superadmin'] as $role) {
            $body = Http::as($role)->get("/investigator/detail/{$case1}")->body;
            T::contains('/investigator/updateSp2hp', $body, $role);
            T::notContains('Mode baca', $body, $role);
        }
    },

    'pembaca tidak dapat menjalankan aksi penyidikan' => function () use ($case1) {
        $before = (int)Db::value("SELECT COUNT(*) FROM report_timeline WHERE report_id = ?", [$case1]);
        $kapolda = Http::as('kapolri');
        $kapolda->post('/investigator/updateSp2hp', ['_csrf_token' => $kapolda->get("/executive")->csrf(), 'report_id' => $case1, 'public_notes' => 'x', 'status' => 'INVESTIGATING']);
        T::contains('bukan dalam kewenangan Anda', $kapolda->flash());
        T::same($before, (int)Db::value("SELECT COUNT(*) FROM report_timeline WHERE report_id = ?", [$case1]));
    },

    'Operator SPKT & Kasatker Satker lain tidak dapat membuka berkas perkara' => function () use ($case1) {
        T::same(302, Http::as('spkt')->get("/investigator/detail/{$case1}")->status, 'SPKT');
        $other = (int)Db::value("SELECT id FROM reports WHERE current_satker_id <> 2 LIMIT 1");
        T::same(302, Http::as('kasatker')->get("/investigator/detail/{$other}")->status, 'Kasatker Satker lain');
    },

    'navigasi Kapolri tidak menautkan halaman yang tidak dapat diaksesnya' => function () {
        $html = Http::as('kapolri')->get('/executive')->body;
        T::notContains('/spkt"', $html, 'Meja SPKT');
        T::notContains('/social"', $html, 'Radar Medsos');
    },

    'Kanit melihat seluruh kasus Satker-nya, bukan hanya yang ditugaskan kepadanya' => function () use ($ticket1) {
        $kanitId = (int)Db::value("SELECT id FROM users WHERE email = 'kanit.tipidum@sigap.polri.go.id'");
        T::true((int)Db::value("SELECT assigned_investigator_id FROM reports WHERE ticket_number = ?", [$ticket1]) !== $kanitId, 'prasyarat: kasus milik penyidik lain');
        T::contains($ticket1, Http::as('kanit')->get('/investigator')->body);
    },

    'pilihan penyidik superadmin ditandai Satker-nya untuk difilter per laporan' => function () {
        $html = Http::as('superadmin')->get('/satker')->body;
        $total = (int)Db::value("SELECT COUNT(*) FROM users WHERE role IN ('ROLE_INVESTIGATOR','ROLE_SATKER_ADMIN') AND is_active = 1 AND satker_id IS NOT NULL");
        T::same($total, preg_match_all('/<option value="\d+" data-satker="\d+">/', $html), 'semua penyidik aktif dengan data-satker');
    },
];
