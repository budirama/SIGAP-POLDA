<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

// Gubeng (1005) & Benowo (1000) → Polrestabes Surabaya (113) → Polda Jatim (10) → Mabes (1)
const P4_SBY = 113;
const P4_JATIM = 10;

$csrfOf = fn(Http $c) => $c->get('/posko/password')->csrf();
$flash = function (Http $c): string {
    preg_match_all('/<span>([^<]*)<\/span>\s*<\/div>/', $c->get('/posko')->body, $m);
    return html_entity_decode(implode(' | ', $m[1]));
};
$act = fn(Http $c, string $action, int $id, string $notes = '') => $c->post("/posko-laporan/{$action}", ['_csrf_token' => $c->get('/posko/password')->csrf(), 'id' => $id, 'notes' => $notes]);

/** Laporan harian sumber yang dikirim (dan disetujui induk bila $approve) */
$daily = function (string $role, string $date, array $nums, bool $approve = true) use ($csrfOf, $act): int {
    $op = Http::as($role);
    $csrf = $csrfOf($op);
    $op->post('/posko-laporan/save', [
        '_csrf_token' => $csrf, 'id' => 0, 'report_type_id' => 1, 'period_date' => $date,
        'region' => Db::PREFIX . $role, 'priority' => 'NORMAL', 'situation_summary' => 'Aman', 'description' => 'Patroli',
        'values' => ['situasi_kamtibmas' => 'Aman terkendali'] + $nums, 'next' => 'draft',
    ]);
    $id = (int)Db::value("SELECT MAX(id) FROM posko_reports WHERE region = ?", [Db::PREFIX . $role]);
    $op->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id]);
    if ($approve) {
        $act(Http::as('poskoOpSby'), 'approve', $id);
        T::same('APPROVED', Db::value("SELECT status FROM posko_reports WHERE id = ?", [$id]), "laporan {$role} disetujui");
    }
    return $id;
};
/** Susun rekap harian unit $role untuk tanggal $date (narasi lengkap) */
$recap = function (string $role, string $date) use ($csrfOf): int {
    $c = Http::as($role);
    $c->post('/posko-rekap/store', [
        '_csrf_token' => $csrfOf($c), 'report_type_id' => 1, 'period_start' => $date, 'priority' => 'NORMAL',
        'region' => Db::PREFIX . 'rekap ' . $role, 'situation_summary' => 'Wilayah aman', 'description' => 'Analisis rekap uji',
    ]);
    return (int)Db::value("SELECT MAX(id) FROM posko_reports WHERE is_recap = 1 AND report_type_id = 1 AND period_key = ?", [$date]);
};
$num = fn(int $id, string $key) => Db::value("SELECT value_number FROM posko_report_values WHERE report_id = ? AND field_key = ?", [$id, $key]);

return [
    'rekap berjenjang: agregat otomatis, dapat ditelusuri ke sumber, tanpa hitung ganda di tingkat atas' => function () use ($daily, $recap, $act, $num, $flash) {
        $date = date('Y-m-d', strtotime('-30 days'));
        $g = $daily('poskoOpGubeng', $date, ['jumlah_kejadian' => '2', 'jumlah_laka_lantas' => '1', 'personel_siaga' => '15', 'kegiatan_patroli' => '3']);
        $b = $daily('poskoOpBenowo', $date, ['jumlah_kejadian' => '4', 'jumlah_laka_lantas' => '0', 'personel_siaga' => '20']);

        T::contains('/posko-laporan', Http::as('poskoOpGubeng')->get('/posko-rekap/create')->location(), 'Polsek tidak menyusun rekap');

        $preview = Http::as('poskoOpSby')->get('/posko-rekap/create?type=1&period_date=' . $date)->body;
        T::contains('Laporan Sumber (2)', $preview);

        $sby = $recap('poskoOpSby', $date);
        T::true($sby > 0, 'draft rekap tersimpan');
        T::same(['6.00', '1.00', '35.00', '3.00'], [$num($sby, 'jumlah_kejadian'), $num($sby, 'jumlah_laka_lantas'), $num($sby, 'personel_siaga'), $num($sby, 'kegiatan_patroli')], 'SUM sesuai sumber');
        $sources = array_map('intval', array_column(Db::pdo()->query("SELECT source_report_id FROM posko_recap_sources WHERE recap_report_id = {$sby} ORDER BY source_report_id")->fetchAll(), 'source_report_id'));
        T::same([min($g, $b), max($g, $b)], $sources, 'jejak sumber tersimpan');
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM posko_report_values WHERE report_id = ? AND field_key = 'situasi_kamtibmas'", [$sby]), 'isian pilihan tidak diagregasi');

        $c = Http::as('poskoOpSby');
        T::contains('/posko-rekap/edit/', $c->get("/posko-laporan/edit/{$sby}")->location(), 'nilai agregat tidak diedit manual');
        $c->post('/posko-rekap/store', ['_csrf_token' => $c->get('/posko/password')->csrf(), 'report_type_id' => 1, 'period_start' => $date, 'situation_summary' => 'x', 'description' => 'x']);
        T::contains('sudah ada', $flash($c), 'satu rekap per periode');

        $c->post('/posko-laporan/submit', ['_csrf_token' => $c->get('/posko/password')->csrf(), 'id' => $sby]);
        T::same(['SUBMITTED', P4_JATIM], array_values(Db::row("SELECT status, holder_satker_id FROM posko_reports WHERE id = ?", [$sby])), 'rekap dikirim ke Polda');

        $act(Http::as('poskoOpJatim'), 'approve', $sby);
        $jatim = $recap('poskoOpJatim', $date);
        T::same([$sby], array_map('intval', array_column(Db::pdo()->query("SELECT source_report_id FROM posko_recap_sources WHERE recap_report_id = {$jatim}")->fetchAll(), 'source_report_id')), 'Polda memakai rekap Polres, bukan laporan Polsek');
        T::same('35.00', $num($jatim, 'personel_siaga'), 'tanpa hitung ganda');
    },

    'revisi laporan sumber menandai rekap Polres & Polda untuk ditinjau ulang' => function () use ($act, $recap) {
        $date = date('Y-m-d', strtotime('-30 days'));
        $g = (int)Db::value("SELECT id FROM posko_reports WHERE satker_id = 1005 AND report_type_id = 1 AND period_key = ? AND is_recap = 0", [$date]);
        $sbyRecap = (int)Db::value("SELECT id FROM posko_reports WHERE satker_id = ? AND is_recap = 1 AND period_key = ?", [P4_SBY, $date]);
        $jatimRecap = (int)Db::value("SELECT id FROM posko_reports WHERE satker_id = ? AND is_recap = 1 AND period_key = ?", [P4_JATIM, $date]);
        T::true($g && $sbyRecap && $jatimRecap, 'data dari test sebelumnya tersedia');

        $act(Http::as('poskoOpGubeng'), 'revise', $g, 'Koreksi jumlah personel.');
        T::same([1, 1], [(int)Db::value("SELECT needs_review FROM posko_reports WHERE id = ?", [$sbyRecap]), (int)Db::value("SELECT needs_review FROM posko_reports WHERE id = ?", [$jatimRecap])], 'ditandai berantai');
        T::contains('Rekap perlu ditinjau ulang', Http::as('poskoExecJatim')->get("/posko-laporan/show/{$sbyRecap}")->body);
    },

    'nihil terkonfirmasi: alasan wajib, hanya jenis yang mengizinkan, dikirim ke induk' => function () use ($csrfOf, $flash) {
        $op = Http::as('poskoOpBenowo');
        $csrf = $csrfOf($op);
        $date = date('Y-m-d', strtotime('-31 days'));

        $op->post('/posko-laporan/nil', ['_csrf_token' => $csrf, 'report_type_id' => 1, 'period_date' => $date, 'nil_reason' => '']);
        T::contains('wajib diisi', $flash($op));
        $op->post('/posko-laporan/nil', ['_csrf_token' => $csrf, 'report_type_id' => 3, 'period_date' => $date, 'nil_reason' => 'x']);
        T::contains('tidak dapat dinyatakan nihil', $flash($op), 'kejadian khusus tidak boleh nihil');

        $op->post('/posko-laporan/nil', ['_csrf_token' => $csrf, 'report_type_id' => 1, 'period_date' => $date, 'nil_reason' => 'Tidak ada kejadian menonjol.']);
        $row = Db::row("SELECT id, is_nil, status, holder_satker_id FROM posko_reports WHERE satker_id = 1000 AND report_type_id = 1 AND period_key = ? AND is_recap = 0", [$date]);
        T::same([1, 'SUBMITTED', P4_SBY], [(int)$row['is_nil'], $row['status'], (int)$row['holder_satker_id']]);
        Db::exec("UPDATE posko_reports SET region = ? WHERE id = ?", [Db::PREFIX . 'nihil', $row['id']]); // dibersihkan setelah suite
    },

    'kepatuhan: belum masuk ≠ nihil, drill-down & batas wilayah' => function () {
        $sby = Http::as('poskoOpSby');
        $html = $sby->get('/posko-kepatuhan?type=1')->body;
        T::contains('Polsek Gubeng', $html);
        T::contains('Polsek Benowo', $html);
        T::contains('Tidak masuk', $html, 'Polsek tanpa laporan lewat tenggat');
        T::contains('Disetujui', $html, 'laporan demo Gubeng 2 hari lalu');

        $jatim = Http::as('poskoOpJatim')->get('/posko-kepatuhan?type=1')->body;
        T::contains('posko-kepatuhan?unit=' . P4_SBY, $jatim, 'drill-down ke Polres yang memiliki Polsek');

        T::contains('/posko-kepatuhan', $sby->get('/posko-kepatuhan?unit=116')->location(), 'Polres lain ditolak');
        T::same(200, Http::as('poskoExecSby')->get('/posko-kepatuhan')->status, 'executive memantau kepatuhan');
    },
];
