<?php

use App\Config\Config;
use Tests\T;

return [
    'parseEnv membaca KEY=VALUE dan mengabaikan komentar baris' => function () {
        $env = Config::parseEnv("# komentar\nAPP_ENV=production\n\nDB_PASS=\nDB_HOST = 127.0.0.1 \n");
        T::same(['APP_ENV' => 'production', 'DB_PASS' => '', 'DB_HOST' => '127.0.0.1'], $env);
    },

    'parseEnv membuang komentar di akhir nilai tanpa kutip' => function () {
        $env = Config::parseEnv("APP_ENV=development   # development: detail error aktif\r\nDB_NAME=sigap_polda #nama db");
        T::same('development', $env['APP_ENV']);
        T::same('sigap_polda', $env['DB_NAME']);
    },

    'parseEnv mempertahankan nilai berkutip apa adanya' => function () {
        $env = Config::parseEnv("DB_PASS=\"rahasia # bukan komentar\"\nAPP_NAME='SIGAP POLDA'\nTOKEN=abc#def");
        T::same('rahasia # bukan komentar', $env['DB_PASS']);
        T::same('SIGAP POLDA', $env['APP_NAME']);
        T::same('abc#def', $env['TOKEN'], '"#" tanpa spasi sebelumnya bagian dari nilai');
    },

    'parseEnv mengabaikan baris tanpa "="' => function () {
        T::same([], Config::parseEnv("export\nSEMBARANG TEKS\n"));
    },
];
