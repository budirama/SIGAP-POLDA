<?php

use App\Core\PoskoSchedule;
use Tests\T;

$daily = ['period_unit' => 'DAY', 'deadline_offset_days' => 0, 'deadline_time' => '23:59:00', 'deadline_hours' => null];
$weekly = ['period_unit' => 'WEEK', 'deadline_offset_days' => 1, 'deadline_time' => '12:00:00', 'deadline_hours' => null];
$monthly = ['period_unit' => 'MONTH', 'deadline_offset_days' => 5, 'deadline_time' => '15:00:00', 'deadline_hours' => null];
$incident = ['period_unit' => 'NONE', 'deadline_offset_days' => 0, 'deadline_time' => null, 'deadline_hours' => 6];

return [
    'periode harian, mingguan (Senin–Minggu) & bulanan' => function () {
        T::same(['start' => '2026-10-15', 'end' => '2026-10-15'], PoskoSchedule::periodFor('DAY', '2026-10-15'));
        T::same(['start' => '2026-10-12', 'end' => '2026-10-18'], PoskoSchedule::periodFor('WEEK', '2026-10-15'), 'Kamis');
        T::same(['start' => '2026-10-12', 'end' => '2026-10-18'], PoskoSchedule::periodFor('WEEK', '2026-10-18'), 'Minggu tetap minggu yang sama');
        T::same(['start' => '2026-10-19', 'end' => '2026-10-25'], PoskoSchedule::periodFor('WEEK', '2026-10-19'), 'Senin minggu baru');
        T::same(['start' => '2026-12-28', 'end' => '2027-01-03'], PoskoSchedule::periodFor('WEEK', '2027-01-01'), 'minggu lintas tahun');
        T::same(['start' => '2028-02-01', 'end' => '2028-02-29'], PoskoSchedule::periodFor('MONTH', '2028-02-10'), 'Februari kabisat');
        T::same(['start' => '2026-02-01', 'end' => '2026-02-28'], PoskoSchedule::periodFor('MONTH', '2026-02-28'));
        T::same(null, PoskoSchedule::periodFor('NONE', '2026-10-15'));
    },

    'tenggat dihitung dari akhir periode atau waktu kejadian' => function () use ($daily, $weekly, $monthly, $incident) {
        T::same('2026-10-15 23:59:00', PoskoSchedule::deadline($daily, '2026-10-15', null));
        T::same('2026-10-19 12:00:00', PoskoSchedule::deadline($weekly, '2026-10-18', null), 'Senin pukul 12.00');
        T::same('2027-01-05 15:00:00', PoskoSchedule::deadline($monthly, '2026-12-31', null), 'lintas tahun');
        T::same('2028-03-05 15:00:00', PoskoSchedule::deadline($monthly, '2028-02-29', null), 'setelah Februari kabisat');
        T::same('2026-10-16 03:30:00', PoskoSchedule::deadline($incident, null, '2026-10-15 21:30:00'), '+6 jam lewat tengah malam');
        T::same(null, PoskoSchedule::deadline($incident, null, null), 'tanpa waktu kejadian');
    },

    'label periode berbahasa Indonesia' => function () {
        T::same('15 Okt 2026', PoskoSchedule::label('DAY', '2026-10-15', '2026-10-15'));
        T::same('12–18 Okt 2026', PoskoSchedule::label('WEEK', '2026-10-12', '2026-10-18'));
        T::same('28 Sep – 4 Okt 2026', PoskoSchedule::label('WEEK', '2026-09-28', '2026-10-04'));
        T::same('Oktober 2026', PoskoSchedule::label('MONTH', '2026-10-01', '2026-10-31'));
    },
];
