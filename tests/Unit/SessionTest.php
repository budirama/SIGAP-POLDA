<?php

use App\Core\Session;
use Tests\T;

$now = 1_800_000_000;

return [
    'sesi aktif belum kedaluwarsa' => function () use ($now) {
        T::same(false, Session::isExpired(['_created' => $now - 3600, '_last_activity' => $now - 60], $now));
        T::same(false, Session::isExpired([], $now), 'sesi baru tanpa penanda waktu');
    },

    'sesi kedaluwarsa setelah idle melewati batas' => function () use ($now) {
        T::same(false, Session::isExpired(['_created' => $now, '_last_activity' => $now - Session::IDLE_TIMEOUT], $now), 'tepat di batas');
        T::same(true, Session::isExpired(['_created' => $now, '_last_activity' => $now - Session::IDLE_TIMEOUT - 1], $now));
    },

    'sesi kedaluwarsa setelah umur maksimum walau tetap aktif' => function () use ($now) {
        T::same(true, Session::isExpired(['_created' => $now - Session::ABSOLUTE_TIMEOUT - 1, '_last_activity' => $now], $now));
    },
];
