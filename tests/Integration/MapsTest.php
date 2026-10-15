<?php

use Tests\Http;
use Tests\T;

$hasKey = \App\Config\Config::googleMapsKey() !== '';

return [
    'CSP mengizinkan Google Maps' => function () {
        $csp = (string)(new Http(TEST_BASE_URL))->get('/report/create')->header('content-security-policy');
        T::contains('https://maps.googleapis.com', $csp, 'script-src/connect-src');
        T::contains('https://*.gstatic.com', $csp, 'img-src tile Google');
    },

    'peta memakai Google Maps bila key diisi, selain itu Leaflet' => function () use ($hasKey) {
        $form = (new Http(TEST_BASE_URL))->get('/report/create')->body;
        $dashboard = Http::as('kapolri')->get('/executive')->body;
        foreach (['formulir' => $form, 'dashboard' => $dashboard] as $label => $html) {
            T::same($hasKey, str_contains($html, 'maps.googleapis.com/maps/api/js'), "loader Google ({$label})");
            T::contains('gm_authFailure', $html, "fallback Leaflet saat key ditolak ({$label})");
        }
    },
];
