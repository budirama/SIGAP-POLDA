<?php

use App\Models\Chatbot;
use Tests\T;

$intents = [
    ['code' => 'CARA_LAPOR', 'keywords' => 'cara lapor, buat laporan, pengaduan, lapor', 'sort_order' => 10],
    ['code' => 'SKCK', 'keywords' => 'skck, catatan kepolisian, kelakuan baik', 'sort_order' => 40],
    ['code' => 'HILANG', 'keywords' => 'hilang, kehilangan, dompet hilang', 'sort_order' => 30],
    ['code' => 'KENDARAAN', 'keywords' => 'motor hilang, motor dicuri, curanmor', 'sort_order' => 31],
];
$code = fn(string $q) => Chatbot::match($q, $intents)['intent']['code'] ?? null;

return [
    'normalisasi: huruf kecil, tanda baca jadi spasi, diapit spasi' => function () {
        T::same(' bagaimana cara buat skck ', Chatbot::normalize('Bagaimana cara buat SKCK??'));
    },

    'frasa utuh mengalahkan kata tunggal' => function () use ($code) {
        T::same('KENDARAAN', $code('Motor hilang di parkiran pasar'), 'frasa "motor hilang" > kata "hilang"');
        T::same('HILANG', $code('saya kehilangan KTP'));
    },

    'awalan/akhiran kata ikut dihitung (melaporkan → lapor) bila bersama kata kunci lain' => function () use ($code) {
        T::same('CARA_LAPOR', $code('Bagaimana cara melaporkan pengaduan?'));
        T::same('SKCK', $code('mau bikin SKCK'));
    },

    'tidak cukup cocok → null' => function () use ($code) {
        T::same(null, $code('cuaca hari ini cerah'));
        T::same(null, $code(''));
        T::same(null, $code('melaporkan'), 'satu kecocokan awalan saja (skor 1) di bawah ambang');
    },

    'skor seri dipecahkan oleh urutan terkecil' => function () {
        $tie = [
            ['code' => 'B', 'keywords' => 'polisi', 'sort_order' => 20],
            ['code' => 'A', 'keywords' => 'polisi', 'sort_order' => 5],
        ];
        T::same('A', Chatbot::match('polisi', $tie)['intent']['code']);
    },
];
