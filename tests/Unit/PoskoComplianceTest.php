<?php

use App\Models\PoskoCompliance;
use Tests\T;

$daily = ['id' => 1, 'period_unit' => 'DAY', 'deadline_offset_days' => 0, 'deadline_time' => '23:59:00', 'deadline_hours' => null];
$weekly = ['id' => 2, 'period_unit' => 'WEEK', 'deadline_offset_days' => 1, 'deadline_time' => '12:00:00', 'deadline_hours' => null];

return [
    'laporan yang belum masuk bukan nihil: belum dibuat / terlambat' => function () {
        T::same('NOT_CREATED', PoskoCompliance::classify(null, '2026-10-15 23:59:00', '2026-10-15 20:00:00')['code']);
        T::same('MISSING_LATE', PoskoCompliance::classify(null, '2026-10-15 23:59:00', '2026-10-16 08:00:00')['code']);
    },

    'status laporan, nihil terkonfirmasi & penanda terlambat' => function () {
        $r = fn(string $status, ?string $sent, int $nil = 0) => ['id' => 9, 'status' => $status, 'is_nil' => $nil, 'submitted_at' => $sent];
        $deadline = '2026-10-15 23:59:00';
        $now = '2026-10-17 10:00:00';
        T::same(['NIL', false], array_values(array_slice(PoskoCompliance::classify($r('SUBMITTED', '2026-10-15 20:00:00', 1), $deadline, $now), 0, 2)));
        T::same(['APPROVED', true], array_values(array_slice(PoskoCompliance::classify($r('FORWARDED', '2026-10-16 09:00:00'), $deadline, $now), 0, 2)), 'dikirim lewat tenggat');
        T::same(['DRAFT', true], array_values(array_slice(PoskoCompliance::classify($r('DRAFT', null), $deadline, $now), 0, 2)), 'draft melewati tenggat');
        T::same('NEEDS_REVISION', PoskoCompliance::classify($r('NEEDS_REVISION', '2026-10-15 21:00:00'), $deadline, $now)['code']);
        T::same('DRAFT', PoskoCompliance::classify($r('DRAFT', null, 1), $deadline, $now)['code'], 'nihil yang belum dikirim bukan nihil terkonfirmasi');
    },

    'periode terakhir untuk matriks kepatuhan' => function () use ($daily, $weekly) {
        $days = PoskoCompliance::recentPeriods($daily, 3, '2026-03-01');
        T::same(['2026-02-27', '2026-02-28', '2026-03-01'], array_column($days, 'start'), 'lintas bulan, urut lama → baru');
        T::same('2026-03-01 23:59:00', $days[2]['deadline']);
        $weeks = PoskoCompliance::recentPeriods($weekly, 2, '2026-10-15');
        T::same(['2026-10-05', '2026-10-12'], array_column($weeks, 'start'));
        T::same('2026-10-19 12:00:00', $weeks[1]['deadline']);
    },
];
