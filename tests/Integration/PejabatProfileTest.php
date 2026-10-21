<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$kapolri = fn() => Db::row("SELECT id, email, nrp, phone, role, name, `rank`, position FROM users WHERE email = 'kapolri@sigap.polri.go.id'");
$save = function (Http $admin, array $fields) {
    $csrf = $admin->get('/pejabat?edit=' . (int)$fields['id'])->csrf(); // token ada di formulir ubah
    return $admin->post('/pejabat/save', ['_csrf_token' => $csrf] + $fields);
};

return [
    'superadmin mengubah nama, pangkat & jabatan; identitas login & role tetap' => function () use ($kapolri, $save) {
        $before = $kapolri();
        $admin = Http::as('superadmin');
        T::contains('kapolri@sigap.polri.go.id', $admin->get('/pejabat?edit=' . $before['id'])->body);

        try {
            // Field login/role yang disisipkan ke formulir diabaikan
            $save($admin, [
                'id' => $before['id'], 'rank' => 'Jenderal Pol', 'name' => 'ZZTEST Nama Pejabat Uji', 'position' => 'Kapolri (uji profil)',
                'email' => 'diganti@contoh.id', 'nrp' => '11111111', 'role' => 'ROLE_SUPERADMIN', 'phone' => '0800',
            ]);
            $after = $kapolri();
            T::same('ZZTEST Nama Pejabat Uji', $after['name']);
            T::same('Kapolri (uji profil)', $after['position']);
            T::same([$before['email'], $before['nrp'], $before['phone'], $before['role']],
                    [$after['email'], $after['nrp'], $after['phone'], $after['role']], 'identitas login & role tidak berubah');

            // Login dengan akun yang sama tetap berhasil & sidebar memakai nama baru
            T::contains('ZZTEST Nama Pejabat Uji', Http::as('kapolri')->get('/oneservice/daftar')->body);
            T::true((int)Db::value("SELECT COUNT(*) FROM audit_logs WHERE action = 'user.profile' AND detail LIKE ?", ['%ZZTEST Nama Pejabat Uji%']) > 0, 'tercatat di jejak audit');

            $save($admin, ['id' => $before['id'], 'rank' => 'Laksamana', 'name' => 'ZZTEST Pangkat Salah', 'position' => 'x']);
            T::same('ZZTEST Nama Pejabat Uji', $kapolri()['name'], 'pangkat di luar daftar Polri ditolak');
        } finally {
            Db::exec("UPDATE users SET name = ?, `rank` = ?, position = ? WHERE id = ?", [$before['name'], $before['rank'], $before['position'], $before['id']]);
        }
    },

    'hanya superadmin yang dapat membuka & menyimpan profil pejabat; akun warga tidak dapat diubah' => function () use ($kapolri, $save) {
        $before = $kapolri();
        foreach (['kapolri', 'spkt', 'itwasum'] as $role) {
            $c = Http::as($role);
            $res = $c->get('/pejabat');
            T::true($res->status === 302 && !str_contains($res->location(), '/pejabat'), "{$role} ditolak");
            $c->post('/pejabat/save', ['_csrf_token' => $c->get('/report/create')->csrf(), 'id' => $before['id'], 'name' => 'ZZTEST bobol', 'rank' => '', 'position' => '']);
        }
        T::same($before['name'], $kapolri()['name'], 'tidak berubah oleh non-superadmin');

        // Token sah (dari formulir akun personil) tetap tidak bisa dipakai mengubah akun warga
        $citizen = Db::row("SELECT id, name FROM users WHERE role = 'ROLE_CITIZEN' LIMIT 1");
        $admin = Http::as('superadmin');
        $csrf = $admin->get('/pejabat?edit=' . $before['id'])->csrf();
        T::true($csrf !== '', 'token formulir tersedia');
        $admin->post('/pejabat/save', ['_csrf_token' => $csrf, 'id' => $citizen['id'], 'name' => 'ZZTEST warga diubah', 'rank' => '', 'position' => '']);
        T::same($citizen['name'], Db::value("SELECT name FROM users WHERE id = ?", [$citizen['id']]), 'akun warga di luar cakupan');
        T::contains('Akun personil tidak ditemukan', $admin->get('/pejabat')->body);
    },
];
