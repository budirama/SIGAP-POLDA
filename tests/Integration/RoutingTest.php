<?php

use Tests\Http;
use Tests\T;

return [
    'modul Media Sosial dapat diakses Humas via /social' => function () {
        $humas = Http::as('humas');
        $res = $humas->get('/social');
        T::same(200, $res->status);
        T::contains('Omnichannel Media Sosial', $res->body);
    },

    'login Humas diarahkan ke /social' => function () {
        $res = (new Http(TEST_BASE_URL))->get('/auth/quickLogin/humas');
        T::true(str_ends_with($res->location(), '/social'), 'redirect: ' . $res->location());
    },

    'rute tak dikenal, helper & parameter tidak valid menghasilkan 404' => function () {
        $penyidik = Http::as('penyidik');
        foreach (['/xyz', '/report', '/report/render', '/report/__construct', '/investigator/detail', '/investigator/detail/abc', '/investigator/detail/1/2'] as $path) {
            T::same(404, $penyidik->get($path)->status, $path);
        }
    },

    'halaman publik utama dapat diakses' => function () {
        $guest = new Http(TEST_BASE_URL);
        foreach (['/', '/report/create', '/report/track', '/auth/login'] as $path) {
            T::same(200, $guest->get($path)->status, $path);
        }
    },

    'file sensitif di root proyek tidak dapat diakses dari web' => function () {
        $root = preg_replace('#/public$#', '', TEST_BASE_URL);
        $guest = new Http($root);
        foreach (['/.env', '/.git/config', '/app/config/Config.php', '/database/schema.sql', '/storage/uploads/.gitkeep', '/tests/run.php'] as $path) {
            T::same(403, $guest->get($path)->status, $path);
        }
    },
];
