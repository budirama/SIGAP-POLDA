<?php

use App\Models\Satker;
use Tests\Db;
use Tests\Http;
use Tests\T;

// Unit demo (database/seeders.sql bagian 10–11)
const SBY = 113;        // Polrestabes Surabaya (Polda Jatim)
const SIDOARJO = 116;   // Polresta Sidoarjo (Polda Jatim)
const JAKSEL = 102;     // Polres Metro Jakarta Selatan (Polda Metro Jaya)
const BENOWO = 1000;    // Polsek Benowo (Polrestabes Surabaya)
const GUBENG = 1005;    // Polsek Gubeng (Polrestabes Surabaya)
const JATIM = 10;

$csrf = fn(Http $c, string $path) => $c->get($path)->csrf();
$flashAdmin = function (Http $c, string $path): string {
    // Pesan flash layout admin dibaca dari halaman berikutnya
    preg_match_all('/<span>([^<]*)<\/span>\s*<\/div>/', $c->get($path)->body, $m);
    return html_entity_decode(implode(' | ', $m[1]));
};

return [
    'hierarki 4 tingkat: path, turunan, leluhur & validasi induk' => function () {
        $s = new Satker();
        T::same('/1/' . JATIM . '/' . SBY . '/' . GUBENG . '/', $s->findById(GUBENG)['path']);
        T::same([1, 2, 3, 4], array_map('intval', [$s->findById(GUBENG)['level'], $s->findById(SBY)['level'], $s->findById(JATIM)['level'], $s->findById(1)['level']]));

        $polsekSby = $s->descendantIds(SBY);
        $polsekOnly = array_filter($polsekSby, fn($id) => $s->findById($id)['type'] === 'POLSEK');
        T::same(24, count($polsekOnly), 'Polsek di bawah Polrestabes Surabaya (di luar satuan fungsi)');
        T::true(!in_array(SIDOARJO, $polsekSby, true), 'Polres lain tidak termasuk');
        T::true(in_array(GUBENG, $s->descendantIds(JATIM), true), 'Polsek termasuk wilayah Polda');
        T::same(['Mabes Polri (Command Center SPKT)', 'Polda Jawa Timur', 'Polrestabes Surabaya'], array_column($s->ancestors(GUBENG), 'name'));

        T::same(null, $s->validateParent('POLSEK', SBY), 'Polsek di bawah Polres sah');
        T::true($s->validateParent('POLSEK', JATIM) !== null, 'Polsek langsung di bawah Polda ditolak');
        T::true($s->validateParent('POLRES', 1) !== null, 'Polres langsung di bawah Mabes ditolak');
        T::true($s->isWithin(GUBENG, JATIM) && !$s->isWithin(GUBENG, SIDOARJO), 'isWithin mengikuti path');
    },

    'akun posko masuk ke beranda posko sesuai tingkatnya' => function () {
        $op = Http::as('poskoOpSby');
        $page = $op->get('/posko')->body;
        T::contains('Polrestabes Surabaya', $page);
        T::contains('Operator Posko Polres', $page);
        T::contains('Polsek Gubeng', $page, 'unit di bawahnya tampil');
        T::contains('/posko/unit/' . GUBENG, $page);

        T::same(302, Http::as('spkt')->get('/posko')->status, 'role SIGAP non-posko ditolak');
        T::same(200, Http::as('superadmin')->get('/posko')->status, 'System Administrator boleh');
        T::same(302, Http::as('poskoOpSby')->get('/spkt')->status, 'akun posko tidak membuka modul aduan');
    },

    'akses lintas organisasi ditolak di setiap tingkat' => function () {
        $cases = [
            // [akun, unit, boleh?]
            ['poskoOpSby', GUBENG, true], ['poskoOpSby', SIDOARJO, false], ['poskoOpSby', JATIM, false],
            ['poskoOpBenowo', BENOWO, true], ['poskoOpBenowo', GUBENG, false],
            ['poskoExecJatim', GUBENG, true], ['poskoExecJatim', SIDOARJO, true], ['poskoExecJatim', JAKSEL, false],
            ['poskoOpMetro', JAKSEL, true], ['poskoOpMetro', SBY, false],
            ['poskoOpPusat', JAKSEL, true], ['poskoOpPusat', GUBENG, true],
        ];
        foreach ($cases as [$role, $unit, $allowed]) {
            $res = Http::as($role)->get("/posko/unit/{$unit}");
            T::same($allowed ? 200 : 302, $res->status, "{$role} → unit #{$unit}");
        }
    },

    'admin posko membuat akun hanya di wilayahnya; akun baru wajib ganti sandi' => function () use ($csrf, $flashAdmin) {
        $admin = Http::as('poskoAdminSby');
        $token = $csrf($admin, '/posko/users');
        $base = ['_csrf_token' => $token, 'name' => Db::PREFIX . 'operator gubeng', 'rank' => 'Bripda',
                 'role' => 'ROLE_POSKO_OPERATOR', 'position' => 'Operator'];
        $phone = fn(string $suffix) => '0899' . $suffix;

        $admin->post('/posko/userStore', $base + ['nrp' => '99000001', 'phone' => $phone('0000001'), 'email' => 'zztest.sidoarjo@sigap.polri.go.id', 'satker_id' => SIDOARJO]);
        T::contains('di luar wilayah', $flashAdmin($admin, '/posko'), 'unit Polres lain ditolak');
        $admin->post('/posko/userStore', $base + ['nrp' => '99000002', 'phone' => $phone('0000002'), 'email' => 'zztest.jatim@sigap.polri.go.id', 'satker_id' => JATIM]);
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM users WHERE email LIKE 'zztest.%jatim%' OR email LIKE 'zztest.sidoarjo%'"), 'tingkat di atasnya ditolak');

        $admin->post('/posko/userStore', $base + ['nrp' => '99000003', 'phone' => $phone('0000003'), 'email' => 'zztest.gubeng@sigap.polri.go.id', 'satker_id' => GUBENG]);
        $page = $admin->get('/posko/users')->body;
        T::true((bool)preg_match('/select-all">([A-Za-z0-9]{12})</', $page, $m), 'kata sandi sementara tampil sekali');
        $temp = $m[1];
        T::true(!str_contains($admin->get('/posko/users')->body, $temp), 'tidak tampil lagi setelah dimuat ulang');
        T::same(1, (int)Db::value("SELECT must_change_password FROM users WHERE email = 'zztest.gubeng@sigap.polri.go.id'"));

        $admin->post('/posko/userStore', $base + ['nrp' => '99000003', 'phone' => $phone('0000004'), 'email' => 'zztest.lagi@sigap.polri.go.id', 'satker_id' => GUBENG]);
        T::contains('sudah dipakai', $flashAdmin($admin, '/posko'), 'NRP ganda ditolak');

        // Login dengan sandi sementara → dipaksa ganti sandi
        $user = new Http(TEST_BASE_URL);
        $login = $user->post('/auth/authenticate', ['_csrf_token' => $user->get('/auth/login')->csrf(),
                                             'identifier' => 'zztest.gubeng@sigap.polri.go.id', 'password' => $temp]);
        T::contains('/posko', $login->location());
        T::contains('/posko/password', $user->get('/posko')->location(), 'dialihkan ke ganti sandi');
        T::contains('/posko/password', $user->get('/posko/unit/' . GUBENG)->location());

        $pwToken = $csrf($user, '/posko/password');
        $user->post('/posko/password', ['_csrf_token' => $pwToken, 'current_password' => $temp, 'new_password' => 'pendek1', 'confirm_password' => 'pendek1']);
        T::same(1, (int)Db::value("SELECT must_change_password FROM users WHERE email = 'zztest.gubeng@sigap.polri.go.id'"), 'sandi lemah ditolak');
        $user->post('/posko/password', ['_csrf_token' => $pwToken, 'current_password' => $temp, 'new_password' => 'SandiBaru2026', 'confirm_password' => 'SandiBaru2026']);
        T::same(0, (int)Db::value("SELECT must_change_password FROM users WHERE email = 'zztest.gubeng@sigap.polri.go.id'"));
        T::same(200, $user->get('/posko')->status, 'akses normal setelah ganti sandi');
        T::contains('Polsek Gubeng', $user->get('/posko')->body);
    },

    'admin hanya mengelola akun di wilayahnya; operator tidak dapat mengelola akun' => function () use ($csrf) {
        $admin = Http::as('poskoAdminSby');
        $jatimAdminId = (int)Db::value("SELECT id FROM users WHERE email = 'posko.admin.jatim@sigap.polri.go.id'");
        $sidoarjoId = (int)Db::value("SELECT id FROM users WHERE email = 'posko.op.sidoarjo@sigap.polri.go.id'");
        $token = $csrf($admin, '/posko/users');

        $admin->post('/posko/userToggle', ['_csrf_token' => $token, 'user_id' => $jatimAdminId]);
        $admin->post('/posko/userReset', ['_csrf_token' => $token, 'user_id' => $sidoarjoId]);
        T::same(1, (int)Db::value("SELECT is_active FROM users WHERE id = ?", [$jatimAdminId]), 'akun tingkat atas tidak tersentuh');
        T::same(0, (int)Db::value("SELECT must_change_password FROM users WHERE id = ?", [$sidoarjoId]), 'akun Polres lain tidak direset');

        $list = $admin->get('/posko/users')->body;
        T::contains('posko.op.benowo@', $list, 'akun Polsek di bawahnya tampil');
        T::notContains('posko.op.sidoarjo@', $list, 'akun Polres lain tidak tampil');

        T::contains('/posko', Http::as('poskoOpSby')->get('/posko/users')->location(), 'operator dialihkan');
    },

    'manajemen organisasi: hanya Pusat; tambah, pindah & nonaktifkan unit' => function () use ($csrf, $flashAdmin) {
        T::contains('/posko', Http::as('poskoAdminJatim')->get('/posko/organizations')->location(), 'admin Polda ditolak');

        $admin = Http::as('poskoAdminPusat');
        $token = $csrf($admin, '/posko/organizations/' . SBY);

        $admin->post('/posko/orgStore', ['_csrf_token' => $token, 'type' => 'POLSEK', 'parent_id' => JATIM, 'code' => 'ZZTEST-SALAH', 'name' => 'Polsek Salah Induk']);
        T::contains('Induk Polsek harus berupa Polres', $flashAdmin($admin, '/posko'));

        $admin->post('/posko/orgStore', ['_csrf_token' => $token, 'type' => 'POLSEK', 'parent_id' => SBY, 'code' => 'ZZTEST-POLSEK-UJI', 'name' => Db::PREFIX . 'Polsek Uji']);
        $id = (int)Db::value("SELECT id FROM satkers WHERE code = 'ZZTEST-POLSEK-UJI'");
        T::true($id > 0, 'Polsek dibuat');
        T::same('/1/' . JATIM . '/' . SBY . "/{$id}/", Db::value("SELECT path FROM satkers WHERE id = ?", [$id]));
        T::same(1, (int)Db::value("SELECT level FROM satkers WHERE id = ?", [$id]));

        $admin->post('/posko/orgStore', ['_csrf_token' => $token, 'type' => 'POLSEK', 'parent_id' => SBY, 'code' => 'ZZTEST-POLSEK-UJI', 'name' => 'Duplikat']);
        T::contains('sudah dipakai', $flashAdmin($admin, '/posko'), 'kode unik');

        $admin->post('/posko/orgMove', ['_csrf_token' => $token, 'satker_id' => $id, 'parent_id' => SIDOARJO]);
        T::same('/1/' . JATIM . '/' . SIDOARJO . "/{$id}/", Db::value("SELECT path FROM satkers WHERE id = ?", [$id]), 'path ikut berpindah');
        T::same(302, Http::as('poskoOpSby')->get("/posko/unit/{$id}")->status, 'tidak lagi di wilayah Surabaya');
        T::same(200, Http::as('poskoOpSidoarjo')->get("/posko/unit/{$id}")->status, 'kini di wilayah Sidoarjo');

        $admin->post('/posko/orgToggle', ['_csrf_token' => $token, 'satker_id' => $id]);
        T::same(0, (int)Db::value("SELECT is_active FROM satkers WHERE id = ?", [$id]), 'nonaktif, tidak dihapus');

        $admin->post('/posko/orgUpdate', ['_csrf_token' => $token, 'satker_id' => 2, 'code' => 'DITTIPIDUM', 'name' => 'ubah']);
        T::same('Dittipidum Bareskrim Polri (Tindak Pidana Umum)', Db::value("SELECT name FROM satkers WHERE id = 2"), 'satker direktorat tidak diubah dari menu posko');
    },

    'pindah induk Polres memperbarui path seluruh Polsek di bawahnya' => function () use ($csrf) {
        $admin = Http::as('poskoAdminPusat');
        $token = $csrf($admin, '/posko/organizations/' . JATIM);
        try {
            $admin->post('/posko/orgMove', ['_csrf_token' => $token, 'satker_id' => SBY, 'parent_id' => 7]);
            T::same('/1/7/' . SBY . '/' . GUBENG . '/', Db::value("SELECT path FROM satkers WHERE id = ?", [GUBENG]));
            T::same(302, Http::as('poskoExecJatim')->get('/posko/unit/' . GUBENG)->status, 'keluar dari wilayah Jatim');
        } finally {
            $admin->post('/posko/orgMove', ['_csrf_token' => $token, 'satker_id' => SBY, 'parent_id' => JATIM]);
        }
        T::same('/1/' . JATIM . '/' . SBY . '/' . GUBENG . '/', Db::value("SELECT path FROM satkers WHERE id = ?", [GUBENG]), 'dikembalikan');
    },

    'dashboard Kapolri tidak dibanjiri Polres tanpa perkara' => function () {
        $html = Http::as('kapolri')->get('/executive')->body;
        T::notContains('Polres Batu', $html);
        T::contains('Polda Jawa Timur', $html, 'Polda yang menangani perkara tetap tampil');
    },
];
