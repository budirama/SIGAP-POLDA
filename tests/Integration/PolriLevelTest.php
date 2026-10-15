<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

return [
    'struktur demo tingkat Mabes Polri: pusat MABES, satker Mabes & Polda jajaran' => function () {
        T::same('MABES', Db::value("SELECT type FROM satkers WHERE id = 1"), 'Satker pusat');
        T::true((int)Db::value("SELECT COUNT(*) FROM satkers WHERE type = 'POLDA'") >= 5, 'Polda jajaran');
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM reports WHERE id <= 4 AND ticket_number NOT LIKE 'LP-POLRI-%'"), 'tiket demo berprefiks LP-POLRI');
        T::same('Jenderal Pol', Db::value("SELECT `rank` FROM users WHERE email = 'kapolri@sigap.polri.go.id'"), 'eksekutif = Kapolri');
    },

    'disposisi SPKT dapat ke Satker Mabes maupun Polda jajaran' => function () {
        $page = Http::as('spkt')->get('/spkt')->body;
        T::contains('SATKER MABES POLRI', $page);
        T::contains('<optgroup label="POLDA JAJARAN">', $page);
        T::contains('Polda Jawa Timur', $page);
    },

    'antarmuka memakai sebutan Polri & Kapolri' => function () {
        $home = (new Http(TEST_BASE_URL))->get('/')->body;
        T::contains('SIGAP POLRI', $home);
        T::notContains('Kapolda', $home);

        $dashboard = Http::as('kapolri')->get('/executive')->body;
        T::contains('Kapolri', $dashboard);
        T::notContains('Kapolda', $dashboard);
        T::contains('Polda Jawa Timur', $dashboard, 'watchlist SLA memuat Polda jajaran');
    },
];
