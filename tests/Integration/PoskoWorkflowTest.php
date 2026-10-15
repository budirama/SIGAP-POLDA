<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

// Unit demo: Gubeng (1005) → Polrestabes Surabaya (113) → Polda Jatim (10) → Mabes (1)
const P3_GUBENG = 1005;
const P3_SBY = 113;
const P3_JATIM = 10;
const P3_PUSAT = 1;

$csrfOf = fn(Http $c) => $c->get('/posko-laporan/create?type=1')->csrf() ?: $c->get('/posko/password')->csrf();
$flash = function (Http $c): string {
    preg_match_all('/<span>([^<]*)<\/span>\s*<\/div>/', $c->get('/posko')->body, $m);
    return html_entity_decode(implode(' | ', $m[1]));
};
$values = ['situasi_kamtibmas' => 'Aman terkendali', 'jumlah_kejadian' => '1', 'jumlah_laka_lantas' => '0', 'personel_siaga' => '10'];

/** Buat & kirim laporan harian Gubeng untuk N hari lalu; kembalikan [id, klien operator Gubeng, csrf] */
$submitted = function (int $daysAgo, int $typeId = 1) use ($csrfOf, $values): array {
    $op = Http::as('poskoOpGubeng');
    $csrf = $csrfOf($op);
    $op->post('/posko-laporan/save', [
        '_csrf_token' => $csrf, 'id' => 0, 'report_type_id' => $typeId, 'period_date' => date('Y-m-d', strtotime("-{$daysAgo} days")),
        'region' => Db::PREFIX . 'Gubeng', 'priority' => 'NORMAL', 'situation_summary' => 'Aman', 'description' => 'Patroli',
        'values' => $values, 'next' => 'draft',
    ]);
    $id = (int)Db::value("SELECT MAX(id) FROM posko_reports WHERE region = ?", [Db::PREFIX . 'Gubeng']);
    $op->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id]);
    T::same('SUBMITTED', Db::value("SELECT status FROM posko_reports WHERE id = ?", [$id]), 'laporan uji terkirim');
    return [$id, $op, $csrf];
};
$act = fn(Http $c, string $action, int $id, string $notes = '') => $c->post("/posko-laporan/{$action}", ['_csrf_token' => $csrfOf($c), 'id' => $id, 'notes' => $notes]);
$state = fn(int $id) => Db::row("SELECT status, holder_satker_id, version FROM posko_reports WHERE id = ?", [$id]);
$actions = fn(int $id) => array_column(Db::pdo()->query("SELECT action FROM posko_report_history WHERE report_id = {$id} ORDER BY id")->fetchAll(), 'action');

return [
    'alur berjenjang penuh: Polsek → Polres verifikasi → Polda → Pusat, tanpa melompati tingkat' => function () use ($submitted, $act, $state, $actions, $flash) {
        [$id] = $submitted(15);
        T::same(P3_SBY, (int)$state($id)['holder_satker_id'], 'dipegang induk langsung (Polres)');

        // Tingkat selain induk langsung tidak dapat memverifikasi
        $jatim = Http::as('poskoOpJatim');
        $act($jatim, 'approve', $id);
        T::contains('Tindakan tidak tersedia', $flash($jatim), 'Polda tidak melompati Polres');
        $act(Http::as('poskoOpBenowo'), 'approve', $id);
        T::same('SUBMITTED', $state($id)['status'], 'Polsek lain tidak dapat menyetujui');
        $act(Http::as('poskoExecSby'), 'approve', $id);
        T::same('SUBMITTED', $state($id)['status'], 'executive hanya melihat');

        $sby = Http::as('poskoOpSby');
        $act($sby, 'start-review', $id);
        T::same('IN_REVIEW', $state($id)['status']);
        $act($sby, 'forward', $id);
        T::same('IN_REVIEW', $state($id)['status'], 'jenis wajib disetujui sebelum diteruskan');
        $act($sby, 'approve', $id, 'Sesuai');
        T::same('APPROVED', $state($id)['status']);
        $act($sby, 'forward', $id);
        T::same(['FORWARDED', P3_JATIM], [$state($id)['status'], (int)$state($id)['holder_satker_id']], 'diteruskan ke Polda (bukan Pusat)');

        $act($jatim, 'acknowledge', $id);
        $act($jatim, 'acknowledge', $id); // penerimaan dicatat sekali
        $act($jatim, 'forward', $id);
        T::same(P3_PUSAT, (int)$state($id)['holder_satker_id'], 'Polda meneruskan ke Pusat');

        $pusat = Http::as('poskoOpPusat');
        T::notContains('posko-laporan/forward', $pusat->get("/posko-laporan/show/{$id}")->body, 'Pusat tingkat tertinggi');
        $act($pusat, 'acknowledge', $id);

        T::same(['create', 'submit', 'start_review', 'approve', 'forward', 'acknowledge', 'forward', 'acknowledge'], $actions($id), 'riwayat lengkap & berurutan');
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM posko_report_history WHERE report_id = ? AND (actor_id IS NULL OR actor_satker_id IS NULL)", [$id]), 'pelaku & unit tercatat');
    },

    'kembalikan untuk perbaikan: catatan wajib, kembali ke pembuat, kirim ulang ke induk' => function () use ($submitted, $act, $state, $actions, $flash) {
        [$id, $op, $csrf] = $submitted(16);
        $sby = Http::as('poskoOpSby');

        $act($sby, 'return-report', $id, '');
        T::contains('wajib diisi', $flash($sby));
        T::same('SUBMITTED', $state($id)['status']);

        $act($sby, 'return-report', $id, 'Lengkapi uraian patroli.');
        T::same(['NEEDS_REVISION', null], [$state($id)['status'], $state($id)['holder_satker_id']]);
        T::same(302, $sby->get("/posko-laporan/edit/{$id}")->status, 'induk tidak dapat mengubah isi');
        T::contains('Lengkapi uraian patroli.', $op->get("/posko-laporan/edit/{$id}")->body, 'catatan koreksi tampil ke pembuat');

        $op->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id]);
        T::same(['SUBMITTED', P3_SBY], [$state($id)['status'], (int)$state($id)['holder_satker_id']], 'kirim ulang ke induk langsung');
        T::same(['create', 'submit', 'return', 'resubmit'], $actions($id));

        // Tingkat lebih atas juga dapat mengembalikan laporan yang sudah diteruskan
        $act($sby, 'approve', $id);
        $act($sby, 'forward', $id);
        $act(Http::as('poskoOpJatim'), 'return-report', $id, 'Periode tidak sesuai.');
        T::same('NEEDS_REVISION', $state($id)['status'], 'Polda mengembalikan');
    },

    'revisi laporan yang sudah disetujui membuat versi baru yang diverifikasi ulang' => function () use ($submitted, $act, $state, $values) {
        [$id, $op, $csrf] = $submitted(17);
        $sby = Http::as('poskoOpSby');
        $act($sby, 'approve', $id);

        $act($op, 'revise', $id, 'Jumlah personel siaga salah input.');
        T::same(['NEEDS_REVISION', 2], [$state($id)['status'], (int)$state($id)['version']]);

        $op->post('/posko-laporan/save', [
            '_csrf_token' => $csrf, 'id' => $id, 'report_type_id' => 1, 'period_date' => date('Y-m-d', strtotime('-17 days')),
            'region' => Db::PREFIX . 'Gubeng', 'priority' => 'NORMAL', 'situation_summary' => 'Aman', 'description' => 'Patroli',
            'values' => ['personel_siaga' => '25'] + $values, 'next' => 'draft',
        ]);
        $op->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id]);
        T::same(['SUBMITTED', P3_SBY], [$state($id)['status'], (int)$state($id)['holder_satker_id']], 'diverifikasi ulang oleh induk');

        $versions = Db::pdo()->query("SELECT version, reason, snapshot_json FROM posko_report_versions WHERE report_id = {$id} ORDER BY version")->fetchAll();
        T::same([1, 2], array_map('intval', array_column($versions, 'version')), 'dua versi tersimpan');
        T::same('Jumlah personel siaga salah input.', $versions[1]['reason']);
        T::same('10.00', json_decode($versions[0]['snapshot_json'], true)['values']['personel_siaga'], 'versi lama utuh');
        T::same('25.00', json_decode($versions[1]['snapshot_json'], true)['values']['personel_siaga']);

        $page = $sby->get("/posko-laporan/versions/{$id}")->body;
        T::contains('Versi 2', $page);
        T::contains('Jumlah personel siaga salah input.', $page);
        T::contains('bg-amber-500/10', $page, 'perubahan ditandai');
    },

    'pembatalan: alasan wajib, tidak dihapus, periode dilepas & tidak terlihat induk bila belum pernah dikirim' => function () use ($csrfOf, $act, $state, $values) {
        $op = Http::as('poskoOpGubeng');
        $csrf = $csrfOf($op);
        $draft = fn() => $op->post('/posko-laporan/save', [
            '_csrf_token' => $csrf, 'id' => 0, 'report_type_id' => 1, 'period_date' => date('Y-m-d', strtotime('-18 days')),
            'region' => Db::PREFIX . 'Gubeng', 'priority' => 'NORMAL', 'situation_summary' => 'Aman', 'values' => $values, 'next' => 'draft',
        ]);
        $draft();
        $id = (int)Db::value("SELECT MAX(id) FROM posko_reports WHERE region = ?", [Db::PREFIX . 'Gubeng']);

        $act($op, 'cancel', $id, 'Salah pilih tanggal.');
        T::same('CANCELLED', $state($id)['status']);
        T::same(null, Db::value("SELECT period_key FROM posko_reports WHERE id = ?", [$id]), 'kunci periode dilepas');
        T::same(302, Http::as('poskoOpSby')->get("/posko-laporan/show/{$id}")->status, 'draft batal tidak terlihat induk');

        $draft();
        T::true((int)Db::value("SELECT MAX(id) FROM posko_reports WHERE region = ?", [Db::PREFIX . 'Gubeng']) > $id, 'periode yang sama dapat dibuat lagi');
    },

    'transisi atomik: persetujuan ganda hanya tercatat sekali' => function () use ($submitted, $act, $actions) {
        [$id] = $submitted(19);
        $a = Http::as('poskoOpSby');
        $b = Http::as('poskoAdminSby');
        $act($a, 'approve', $id);
        $act($b, 'approve', $id);
        T::same(1, count(array_filter($actions($id), fn($x) => $x === 'approve')));
    },

    'jenis tanpa persetujuan dapat langsung diteruskan oleh induk' => function () use ($submitted, $act, $state) {
        Db::exec("INSERT INTO posko_report_types (code, name, kind, period_unit, deadline_offset_days, deadline_time, requires_approval, allows_nil, applies_to_levels, is_active)
                  VALUES ('ZZTEST-TANPA-SETUJU', 'Uji Tanpa Persetujuan', 'ROUTINE', 'DAY', 0, '23:59:00', 0, 0, '1', 1)");
        $typeId = (int)Db::value("SELECT id FROM posko_report_types WHERE code = 'ZZTEST-TANPA-SETUJU'");
        [$id] = $submitted(20, $typeId);
        $act(Http::as('poskoOpSby'), 'forward', $id);
        T::same(['FORWARDED', P3_JATIM], [$state($id)['status'], (int)$state($id)['holder_satker_id']]);
    },

    'laporan mendesak di atas antrean induk & tetap berjenjang' => function () {
        $html = Http::as('poskoOpSby')->get('/posko-laporan/inbox')->body;
        $urgent = strpos($html, 'PSK/POLSEK-SBY-GUBENG/' . date('Ym') . '/9001');
        $normal = strpos($html, 'PSK/POLSEK-SBY-BENOWO/');
        T::true($urgent !== false && $normal !== false && $urgent < $normal, 'prioritas tinggi lebih dulu');
        T::contains('LAPORAN MENDESAK', Http::as('poskoOpSby')->get('/posko-laporan/show/3')->body);
        T::notContains('PSK/POLSEK-SBY-GUBENG/' . date('Ym') . '/9001', Http::as('poskoOpJatim')->get('/posko-laporan/inbox')->body, 'belum sampai Polda sebelum diteruskan Polres');
    },

    'System Administrator tidak memiliki tindakan operasional' => function () use ($act, $state) {
        $admin = Http::as('superadmin');
        $act($admin, 'approve', 1);
        T::same('SUBMITTED', $state(1)['status']);
        T::notContains('Tindakan Anda', $admin->get('/posko-laporan/show/1')->body);
    },
];
