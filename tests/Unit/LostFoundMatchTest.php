<?php

use App\Models\LostFound;
use Tests\T;

$lost = [
    'report_type' => 'LOST', 'item_category' => 'KENDARAAN', 'title' => 'Honda Beat hitam hilang',
    'description' => 'Motor Honda Beat hitam, stiker klub di spakbor belakang', 'event_location' => 'Pasar Minggu Jakarta',
    'event_date' => '2026-10-01', 'plate_number' => 'B1234XYZ', 'brand' => 'Honda Beat', 'color' => 'Hitam',
];

return [
    'normalisasi nomor identitas: huruf kapital tanpa spasi/tanda baca' => function () {
        T::same('B1234XYZ', LostFound::normalizeId(' b 1234-xyz '));
        T::same(null, LostFound::normalizeId(' - '));
        T::same(null, LostFound::normalizeId(null));
    },

    'plat nomor sama → skor tinggi walau penulisan berbeda' => function () use ($lost) {
        $found = ['report_type' => 'FOUND', 'item_category' => 'KENDARAAN', 'title' => 'Motor ditemukan terparkir',
                  'description' => 'Ditemukan motor tanpa kunci', 'event_location' => 'Kalibata', 'event_date' => '2026-10-03',
                  'plate_number' => 'b 1234 xyz', 'color' => 'hitam'];
        [$score, $reasons] = LostFound::score($lost, $found);
        T::true($score >= 70, "skor {$score}");
        T::true(in_array('Plat nomor sama', $reasons, true), 'alasan plat');
    },

    'plat berbeda menurunkan skor di bawah ambang' => function () use ($lost) {
        $found = $lost;
        $found['report_type'] = 'FOUND';
        $found['plate_number'] = 'D9999AB';
        [$score] = LostFound::score($lost, $found);
        T::true($score < LostFound::MATCH_THRESHOLD, "skor {$score}");
    },

    'temuan jauh sebelum tanggal hilang tidak dianggap sesuai' => function () use ($lost) {
        $found = ['event_date' => '2026-09-01', 'color' => 'Hitam'];
        [, $reasons] = LostFound::score($lost, $found);
        T::true(!in_array('Rentang tanggal sesuai', $reasons, true), 'tanggal di luar rentang');
    },

    'orang hilang: nama mirip + usia & jenis kelamin sesuai' => function () {
        $a = ['person_name' => 'Siti Aminah', 'person_age' => 70, 'person_gender' => 'P', 'event_date' => '2026-10-01'];
        $b = ['person_name' => 'siti aminah', 'person_age' => 72, 'person_gender' => 'P', 'event_date' => '2026-10-02'];
        [$score] = LostFound::score($a, $b);
        T::true($score >= 60, "skor {$score}");

        $b['person_gender'] = 'L';
        [$score] = LostFound::score($a, $b);
        T::true($score < LostFound::MATCH_THRESHOLD, "jenis kelamin beda: skor {$score}");
    },

    'tampilan publik: nomor temuan disamarkan, plat kehilangan utuh' => function () use ($lost) {
        $pub = fn(array $item) => array_column(LostFound::publicIdentifiers($item), 1, 0);

        T::same('B1234XYZ', $pub($lost)['Plat nomor']);
        $found = ['report_type' => 'FOUND', 'plate_number' => 'B1234XYZ', 'serial_number' => '356789012345678'];
        T::same('******YZ', $pub($found)['Plat nomor']);
        T::same(str_repeat('*', 13) . '78', $pub($found)['IMEI / no. seri']);
    },
];
