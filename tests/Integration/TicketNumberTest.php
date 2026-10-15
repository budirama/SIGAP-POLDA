<?php

use Tests\Db;
use Tests\T;

return [
    'nomor tiket berurutan melanjutkan nomor tertinggi yang ada' => function () {
        $prefix = 'LP-POLDA-' . date('Ym') . '-';
        $max = (int)Db::value("SELECT COALESCE(MAX(CAST(SUBSTRING(ticket_number, ?) AS UNSIGNED)), 0) FROM reports WHERE ticket_number LIKE ?", [strlen($prefix) + 1, $prefix . '%']);

        [, $first] = Db::createReport(['title' => Db::PREFIX . 'tiket 1']);
        [, $second] = Db::createReport(['title' => Db::PREFIX . 'tiket 2']);

        T::same($prefix . str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT), $first);
        T::same($prefix . str_pad((string)($max + 2), 4, '0', STR_PAD_LEFT), $second);
    },
];
