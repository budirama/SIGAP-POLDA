<?php

use App\Models\Throttle;
use Tests\Db;
use Tests\Http;
use Tests\T;

$ticket1 = (string)Db::value("SELECT ticket_number FROM reports WHERE id = 1");
$pin1 = (string)Db::value("SELECT security_pin FROM reports WHERE id = 1");

$login = function (Http $client, string $identifier, string $password) {
    return $client->post('/auth/authenticate', [
        '_csrf_token' => $client->get('/auth/login')->csrf(),
        'identifier'  => $identifier,
        'password'    => $password,
    ]);
};

return [
    'login terkunci setelah 5 password salah, password benar pun ditolak' => function () use ($login) {
        Db::exec("DELETE FROM throttle_events");
        $client = new Http(TEST_BASE_URL);
        for ($i = 1; $i <= Throttle::LIMITS['login_id'][0]; $i++) {
            $res = $login($client, 'humas@sigap.polri.go.id', 'salah-' . $i);
            T::true(str_ends_with($res->location(), '/auth/login'), "percobaan {$i} ditolak");
        }
        $res = $login($client, 'HUMAS@sigap.polri.go.id', 'password123');
        T::true(str_ends_with($res->location(), '/auth/login'), 'terkunci (identitas tidak peka huruf besar/kecil)');
        T::contains('Terlalu banyak percobaan login', $client->flash());

        // Akun lain dari IP yang sama tetap dapat login (batas per IP lebih longgar)
        $res = $login(new Http(TEST_BASE_URL), 'spkt@sigap.polri.go.id', 'password123');
        T::true(str_ends_with($res->location(), '/spkt'), 'akun lain tidak terdampak: ' . $res->location());
        Db::exec("DELETE FROM throttle_events");
    },

    'login berhasil menghapus hitungan gagal identitas tersebut' => function () use ($login) {
        Db::exec("DELETE FROM throttle_events");
        $client = new Http(TEST_BASE_URL);
        $login($client, 'kanit.tipidum@sigap.polri.go.id', 'salah');
        $res = $login($client, 'kanit.tipidum@sigap.polri.go.id', 'password123');
        T::true(str_ends_with($res->location(), '/satker'), 'login sukses');
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM throttle_events WHERE bucket = 'login_id'"));
        Db::exec("DELETE FROM throttle_events");
    },

    'laporan publik dibatasi per IP' => function () {
        Db::exec("DELETE FROM throttle_events");
        $max = Throttle::LIMITS['report_ip'][0];
        $client = new Http(TEST_BASE_URL);
        $submit = function (int $i) use ($client) {
            return $client->post('/report/store', [
                '_csrf_token' => $client->get('/report/create')->csrf(),
                'reporter_nik' => '3171000000000001', 'reporter_name' => 'Penguji','reporter_phone' => '080000000000', 'category_id' => '1',
                'title' => Db::PREFIX . "spam {$i}", 'description' => 'uji', 'incident_location' => 'uji',
            ]);
        };
        for ($i = 1; $i <= $max; $i++) {
            T::true(str_contains($submit($i)->location(), '/report/success'), "laporan {$i} diterima");
        }
        T::true(str_ends_with($submit($max + 1)->location(), '/report/create'), 'laporan berikutnya ditolak');
        T::contains('Batas pengiriman laporan', $client->flash());
        T::same($max, (int)Db::value("SELECT COUNT(*) FROM reports WHERE title LIKE ?", [Db::PREFIX . 'spam %']));
        Db::exec("DELETE FROM throttle_events");
    },

    'tebakan tiket tak dikenal dihitung dalam batas PIN per IP' => function () use ($ticket1, $pin1) {
        Db::exec("DELETE FROM throttle_events");
        $guest = new Http(TEST_BASE_URL);
        for ($i = 1; $i <= Throttle::LIMITS['pin_ip'][0]; $i++) {
            $guest->trackWithPin('LP-POLRI-000000-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT), '123456');
        }
        T::contains('Terlalu banyak percobaan PIN', $guest->trackWithPin($ticket1, $pin1)->body);
        Db::exec("DELETE FROM throttle_events");
    },
];
