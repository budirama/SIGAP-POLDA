<?php
/**
 * SIGAP-POLRI: Periode & Tenggat Pelaporan Posko (WIB)
 *
 * Periode: DAY = satu tanggal, WEEK = Senin–Minggu, MONTH = tanggal 1–akhir bulan.
 * Tenggat berperiode = tanggal akhir periode + deadline_offset_days, pukul deadline_time.
 * Tenggat tanpa periode (kejadian khusus/insidentil) = waktu kejadian + deadline_hours.
 */

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;

class PoskoSchedule
{
    public const TZ = 'Asia/Jakarta';

    /**
     * Periode yang memuat tanggal $date: ['start' => 'Y-m-d', 'end' => 'Y-m-d'], null untuk NONE
     */
    public static function periodFor(string $unit, string $date): ?array
    {
        $d = new DateTimeImmutable($date, new DateTimeZone(self::TZ));
        return match ($unit) {
            'DAY' => ['start' => $d->format('Y-m-d'), 'end' => $d->format('Y-m-d')],
            'WEEK' => [
                'start' => $d->modify('monday this week')->format('Y-m-d'),
                'end' => $d->modify('sunday this week')->format('Y-m-d'),
            ],
            'MONTH' => [
                'start' => $d->modify('first day of this month')->format('Y-m-d'),
                'end' => $d->modify('last day of this month')->format('Y-m-d'),
            ],
            default => null,
        };
    }

    /**
     * Tenggat ('Y-m-d H:i:s') untuk jenis laporan & periode/waktu kejadian; null bila tidak bertenggat
     */
    public static function deadline(array $type, ?string $periodEnd, ?string $eventTime): ?string
    {
        $tz = new DateTimeZone(self::TZ);

        if (($type['period_unit'] ?? 'NONE') === 'NONE') {
            if ($eventTime === null || $eventTime === '' || empty($type['deadline_hours'])) {
                return null;
            }
            return (new DateTimeImmutable($eventTime, $tz))
                ->modify('+' . (int)$type['deadline_hours'] . ' hours')
                ->format('Y-m-d H:i:s');
        }

        if ($periodEnd === null || $periodEnd === '') {
            return null;
        }
        $time = $type['deadline_time'] ?: '23:59:00';
        return (new DateTimeImmutable($periodEnd . ' ' . $time, $tz))
            ->modify('+' . (int)$type['deadline_offset_days'] . ' days')
            ->format('Y-m-d H:i:s');
    }

    /**
     * Label periode untuk tampilan, mis. "15 Okt 2026", "13–19 Okt 2026", "Oktober 2026"
     */
    public static function label(string $unit, ?string $start, ?string $end): string
    {
        if (!$start) {
            return '-';
        }
        $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $short = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $s = new DateTimeImmutable($start);
        $e = new DateTimeImmutable($end ?: $start);

        return match ($unit) {
            'DAY' => $s->format('j') . ' ' . $short[(int)$s->format('n')] . ' ' . $s->format('Y'),
            'WEEK' => $s->format('n') === $e->format('n')
                ? $s->format('j') . '–' . $e->format('j') . ' ' . $short[(int)$e->format('n')] . ' ' . $e->format('Y')
                : $s->format('j') . ' ' . $short[(int)$s->format('n')] . ' – ' . $e->format('j') . ' ' . $short[(int)$e->format('n')] . ' ' . $e->format('Y'),
            'MONTH' => $months[(int)$s->format('n')] . ' ' . $s->format('Y'),
            default => $s->format('d/m/Y'),
        };
    }
}
