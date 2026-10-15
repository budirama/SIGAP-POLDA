<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

// Unit & jenis demo (database/seeders.sql bagian 10–13)
const P2_GUBENG = 1005;
const P2_TYPE_DAILY = 1;      // LAP-HARIAN (DAY, tenggat 23.59)
const P2_TYPE_SPECIAL = 3;    // LAP-KEJADIAN-KHUSUS (NONE, +6 jam)

$token = function (Http $c, string $path): string {
    return $c->get($path)->csrf();
};
$flash = function (Http $c): string {
    preg_match_all('/<span>([^<]*)<\/span>\s*<\/div>/', $c->get('/posko')->body, $m);
    return html_entity_decode(implode(' | ', $m[1]));
};
// Draft harian Polsek Gubeng untuk tanggal tertentu (wilayah berawalan ZZTEST_ agar dibersihkan)
$daily = function (string $date, array $values = [], array $extra = []) {
    return array_merge([
        'id' => 0, 'report_type_id' => P2_TYPE_DAILY, 'period_date' => $date,
        'region' => Db::PREFIX . 'Kecamatan Gubeng', 'priority' => 'NORMAL',
        'situation_summary' => 'Situasi aman', 'description' => 'Patroli rutin',
        'values' => $values, 'next' => 'draft',
    ], $extra);
};
$latestId = fn() => (int)Db::value("SELECT MAX(id) FROM posko_reports WHERE region LIKE ?", [Db::PREFIX . '%']);
$completeValues = ['situasi_kamtibmas' => 'Aman terkendali', 'jumlah_kejadian' => '2', 'jumlah_laka_lantas' => '0', 'personel_siaga' => '15'];

return [
    'pilihan jenis sesuai tingkat; hanya operator/admin yang membuat laporan' => function () {
        $page = Http::as('poskoOpGubeng')->get('/posko-laporan/create')->body;
        foreach (['Laporan Harian Posko', 'Laporan Periodik Mingguan', 'Laporan Kejadian Khusus', 'Laporan Insidentil'] as $name) {
            T::contains($name, $page, $name);
        }
        T::contains('/posko-laporan', Http::as('poskoExecSby')->get('/posko-laporan/create')->location(), 'executive tidak membuat laporan');
        T::contains('/posko-laporan', Http::as('superadmin')->get('/posko-laporan/create')->location(), 'System Administrator bukan operasional');
    },

    'draft boleh belum lengkap tetapi isian harus valid; periode unik & tidak di masa depan' => function () use ($token, $flash, $daily, $latestId) {
        $op = Http::as('poskoOpGubeng');
        $csrf = $token($op, '/posko-laporan/create?type=' . P2_TYPE_DAILY);
        $date = date('Y-m-d', strtotime('-10 days'));

        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily($date, ['jumlah_kejadian' => '-3']));
        T::contains('angka tidak negatif', $flash($op));
        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily($date, ['situasi_kamtibmas' => 'Kacau']));
        T::contains('tidak valid', $flash($op), 'pilihan di luar opsi');
        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily(date('Y-m-d', strtotime('+2 days'))));
        T::contains('belum dimulai', $flash($op), 'periode masa depan');

        $res = $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily($date, ['jumlah_kejadian' => '2.5']));
        $id = $latestId();
        T::true($id > 0, 'draft tersimpan');
        T::contains("/posko-laporan/edit/{$id}", $res->location());
        $row = Db::row("SELECT status, report_number, period_start, deadline_at FROM posko_reports WHERE id = ?", [$id]);
        T::same(['DRAFT', null, $date, "{$date} 23:59:00"], array_values($row), 'status, nomor, periode, tenggat');
        T::same('2.50', Db::value("SELECT value_number FROM posko_report_values WHERE report_id = ? AND field_key = 'jumlah_kejadian'", [$id]));

        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily($date));
        T::contains('sudah memiliki laporan', $flash($op), 'periode yang sama ditolak');
    },

    'draft hanya terlihat oleh unit pembuat' => function () use ($token, $daily, $latestId) {
        $op = Http::as('poskoOpGubeng');
        $op->post('/posko-laporan/save', ['_csrf_token' => $token($op, '/posko-laporan/create?type=' . P2_TYPE_DAILY)] + $daily(date('Y-m-d', strtotime('-11 days'))));
        $id = $latestId();

        T::same(200, $op->get("/posko-laporan/show/{$id}")->status, 'unit pembuat');
        T::contains('/posko-laporan', Http::as('poskoOpSby')->get("/posko-laporan/show/{$id}")->location(), 'induk belum melihat draft');
        T::notContains("show/{$id}\"", Http::as('poskoOpSby')->get('/posko-laporan')->body, 'tidak muncul di daftar induk');
    },

    'kirim: wajib lengkap, nomor unik, terkunci, lalu terlihat oleh tingkat atas saja' => function () use ($token, $flash, $daily, $latestId, $completeValues) {
        $op = Http::as('poskoOpGubeng');
        $csrf = $token($op, '/posko-laporan/create?type=' . P2_TYPE_DAILY);
        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily(date('Y-m-d', strtotime('-12 days')), ['jumlah_kejadian' => '1'], ['description' => '']));
        $id = $latestId();

        $op->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id]);
        $msg = $flash($op);
        T::contains('belum lengkap', $msg);
        T::contains('uraian kegiatan', $msg);
        T::contains('situasi kamtibmas', $msg);
        T::same('DRAFT', Db::value("SELECT status FROM posko_reports WHERE id = ?", [$id]));

        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily(date('Y-m-d', strtotime('-12 days')), $completeValues, ['id' => $id]));
        $op->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id]);
        $row = Db::row("SELECT status, report_number, submitted_at FROM posko_reports WHERE id = ?", [$id]);
        T::same('SUBMITTED', $row['status']);
        T::true((bool)preg_match('#^PSK/POLSEK-SBY-GUBENG/\d{6}/\d{4}$#', (string)$row['report_number']), 'nomor: ' . $row['report_number']);
        T::true($row['submitted_at'] !== null, 'waktu kirim tercatat');

        // Terkunci setelah dikirim
        T::contains("/posko-laporan/show/{$id}", $op->get("/posko-laporan/edit/{$id}")->location(), 'tidak bisa dibuka untuk diubah');
        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily(date('Y-m-d', strtotime('-12 days')), $completeValues, ['id' => $id, 'situation_summary' => 'diubah diam-diam']));
        T::same('Situasi aman', Db::value("SELECT situation_summary FROM posko_reports WHERE id = ?", [$id]), 'isi tidak berubah');

        // Laporan kedua dari unit sama → nomor berbeda
        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily(date('Y-m-d', strtotime('-13 days')), $completeValues));
        $id2 = $latestId();
        $op->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id2]);
        T::true(Db::value("SELECT report_number FROM posko_reports WHERE id = ?", [$id2]) !== $row['report_number'], 'nomor unik');

        foreach ([['poskoOpSby', true], ['poskoExecJatim', true], ['poskoOpPusat', true], ['poskoOpBenowo', false], ['poskoOpSidoarjo', false], ['poskoOpMetro', false]] as [$role, $ok]) {
            $res = Http::as($role)->get("/posko-laporan/show/{$id}");
            T::same($ok ? 200 : 302, $res->status, "{$role} melihat laporan Gubeng");
        }
        T::contains($row['report_number'], Http::as('poskoOpSby')->get('/posko-laporan')->body, 'muncul di daftar Polres induk');
    },

    'lampiran: privat, hanya pihak berwenang & hanya selama dapat diubah' => function () use ($token, $daily, $latestId, $completeValues) {
        $png = sys_get_temp_dir() . '/sigap_posko_test.png';
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        $op = Http::as('poskoOpGubeng');
        $csrf = $token($op, '/posko-laporan/create?type=' . P2_TYPE_DAILY);
        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $daily(date('Y-m-d', strtotime('-14 days')), $completeValues));
        $id = $latestId();

        $op->post('/posko-laporan/upload', ['_csrf_token' => $csrf, 'id' => $id, 'files[0]' => new CURLFile($png, 'image/png', 'foto-patroli.png')]);
        $att = Db::row("SELECT id, file_path FROM posko_report_attachments WHERE report_id = ?", [$id]);
        T::true($att !== null && (bool)preg_match('#^uploads/[0-9a-f]{32}\.png$#', $att['file_path']), 'tersimpan dengan nama acak');
        T::same(404, Http::as('poskoOpSby')->get("/posko-laporan/attachment/{$att['id']}")->status, 'induk belum boleh (masih draft)');

        $op->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id]);
        T::same(200, Http::as('poskoOpSby')->get("/posko-laporan/attachment/{$att['id']}")->status, 'induk setelah dikirim');
        T::same(404, Http::as('poskoOpSidoarjo')->get("/posko-laporan/attachment/{$att['id']}")->status, 'Polres lain');
        $anon = (new Http(TEST_BASE_URL))->get("/posko-laporan/attachment/{$att['id']}");
        T::true($anon->status === 302 && str_contains($anon->location(), '/auth/login'), 'anonim dialihkan ke login');

        $op->post('/posko-laporan/delete-attachment', ['_csrf_token' => $csrf, 'attachment_id' => $att['id']]);
        T::same(1, (int)Db::value("SELECT COUNT(*) FROM posko_report_attachments WHERE report_id = ?", [$id]), 'tidak dapat dihapus setelah dikirim');
        $op->post('/posko-laporan/upload', ['_csrf_token' => $csrf, 'id' => $id, 'files[0]' => new CURLFile($png, 'image/png', 'tambahan.png')]);
        T::same(1, (int)Db::value("SELECT COUNT(*) FROM posko_report_attachments WHERE report_id = ?", [$id]), 'tidak dapat menambah setelah dikirim');
    },

    'laporan kejadian khusus: waktu kejadian wajib, tidak di masa depan, tenggat +6 jam' => function () use ($token, $flash, $latestId) {
        $op = Http::as('poskoOpGubeng');
        $csrf = $token($op, '/posko-laporan/create?type=' . P2_TYPE_SPECIAL);
        $base = ['id' => 0, 'report_type_id' => P2_TYPE_SPECIAL, 'region' => Db::PREFIX . 'Jl. Raya Gubeng', 'priority' => 'HIGH',
                 'situation_summary' => 'Kebakaran', 'description' => 'Kebakaran gudang', 'next' => 'draft',
                 'values' => ['korban_meninggal' => '0', 'korban_luka' => '2', 'status_situasi' => 'Terkendali']];

        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $base + ['event_time' => date('Y-m-d\TH:i', strtotime('+1 day'))]);
        T::contains('masa depan', $flash($op));

        $event = date('Y-m-d H:i', strtotime('-2 hours'));
        $op->post('/posko-laporan/save', ['_csrf_token' => $csrf] + $base + ['event_time' => str_replace(' ', 'T', $event)]);
        $id = $latestId();
        T::same(date('Y-m-d H:i:00', strtotime($event . ' +6 hours')), Db::value("SELECT deadline_at FROM posko_reports WHERE id = ?", [$id]));
        $op->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id]);
        T::same('SUBMITTED', Db::value("SELECT status FROM posko_reports WHERE id = ?", [$id]));
    },

    'konfigurasi jenis & isian hanya Pusat; kunci isian tetap' => function () use ($token, $flash) {
        T::contains('/posko', Http::as('poskoAdminJatim')->get('/posko-jenis')->location(), 'admin Polda ditolak');
        $admin = Http::as('poskoAdminPusat');
        $csrf = $token($admin, '/posko-jenis');

        $admin->post('/posko-jenis/save-type', ['_csrf_token' => $csrf, 'code' => 'ZZTEST-BULANAN', 'name' => 'Uji Bulanan', 'kind' => 'PERIODIC',
            'period_unit' => 'MONTH', 'deadline_offset_days' => '5', 'deadline_time' => '15:00', 'levels' => ['2'], 'is_active' => '1', 'requires_approval' => '1']);
        $typeId = (int)Db::value("SELECT id FROM posko_report_types WHERE code = 'ZZTEST-BULANAN'");
        T::true($typeId > 0, 'jenis dibuat');
        T::same('2', Db::value("SELECT applies_to_levels FROM posko_report_types WHERE id = ?", [$typeId]));

        $admin->post('/posko-jenis/save-type', ['_csrf_token' => $csrf, 'code' => 'ZZTEST-INSIDEN', 'name' => 'Uji', 'kind' => 'INCIDENTAL', 'period_unit' => 'NONE', 'levels' => ['1']]);
        T::contains('tenggat 1–720 jam', $flash($admin), 'jenis tanpa periode wajib tenggat jam');

        $admin->post('/posko-jenis/save-field', ['_csrf_token' => $csrf, 'report_type_id' => $typeId, 'field_key' => 'catatan', 'label' => 'Catatan', 'type' => 'text', 'aggregate' => 'SUM', 'is_active' => '1']);
        T::contains('Hanya isian angka', $flash($admin));
        $admin->post('/posko-jenis/save-field', ['_csrf_token' => $csrf, 'report_type_id' => $typeId, 'field_key' => 'jumlah_giat', 'label' => 'Jumlah giat', 'type' => 'number', 'aggregate' => 'SUM', 'required' => '1', 'is_active' => '1']);
        $fieldId = (int)Db::value("SELECT id FROM posko_report_fields WHERE report_type_id = ? AND field_key = 'jumlah_giat'", [$typeId]);
        $admin->post('/posko-jenis/save-field', ['_csrf_token' => $csrf, 'report_type_id' => $typeId, 'id' => $fieldId, 'field_key' => 'diganti', 'label' => 'Jumlah kegiatan', 'type' => 'number', 'aggregate' => 'SUM', 'is_active' => '1']);
        T::same(['jumlah_giat', 'Jumlah kegiatan'], array_values(Db::row("SELECT field_key, label FROM posko_report_fields WHERE id = ?", [$fieldId])), 'kunci tetap, label berubah');

        // Jenis khusus Polres tidak tersedia untuk Polsek
        T::notContains('Uji Bulanan', Http::as('poskoOpGubeng')->get('/posko-laporan/create')->body);
        T::contains('Uji Bulanan', Http::as('poskoOpSby')->get('/posko-laporan/create')->body);
    },
];
