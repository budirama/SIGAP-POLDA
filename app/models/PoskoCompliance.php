<?php
/**
 * SIGAP-POLRI: Monitoring Kepatuhan Pelaporan Posko (BRD 4.5)
 *
 * Kewajiban lapor = setiap unit aktif yang tingkatnya termasuk applies_to_levels jenis laporan,
 * untuk setiap periode jenis tersebut. Laporan yang belum masuk ditampilkan sebagai
 * "Belum dibuat"/"Terlambat" — tidak pernah dianggap nihil (BRD 2.3.4).
 */

namespace App\Models;

use App\Core\Model;
use App\Core\PoskoSchedule;

class PoskoCompliance extends Model
{
    public const STATUS = [
        'NOT_CREATED'    => ['Belum dibuat', 'border-slate-600 text-slate-300 bg-slate-800/40'],
        'MISSING_LATE'   => ['Tidak masuk', 'border-red-700 text-red-200 bg-red-950/60'],
        'DRAFT'          => ['Draft', 'border-slate-500 text-slate-200 bg-slate-700/40'],
        'SUBMITTED'      => ['Dikirim', 'border-sky-700/60 text-sky-200 bg-sky-950/50'],
        'IN_REVIEW'      => ['Dalam verifikasi', 'border-indigo-700/60 text-indigo-200 bg-indigo-950/50'],
        'NEEDS_REVISION' => ['Perlu perbaikan', 'border-amber-600/70 text-amber-200 bg-amber-950/50'],
        'APPROVED'       => ['Disetujui', 'border-emerald-600/70 text-emerald-200 bg-emerald-950/50'],
        'NIL'            => ['Nihil terkonfirmasi', 'border-teal-600/70 text-teal-200 bg-teal-950/50'],
    ];

    /**
     * Daftar periode terakhir (terbaru di akhir): ['start' => ..., 'end' => ..., 'deadline' => ...]
     */
    public static function recentPeriods(array $type, int $count, ?string $today = null): array
    {
        $unit = $type['period_unit'];
        $step = ['DAY' => '-1 day', 'WEEK' => '-7 days', 'MONTH' => 'first day of last month'][$unit] ?? null;
        if ($step === null) {
            return [];
        }
        $date = $today ?? date('Y-m-d');
        $periods = [];
        for ($i = 0; $i < $count; $i++) {
            $p = PoskoSchedule::periodFor($unit, $date);
            $p['deadline'] = PoskoSchedule::deadline($type, $p['end'], null);
            array_unshift($periods, $p);
            $date = date('Y-m-d', strtotime($step, strtotime($p['start'])));
        }
        return $periods;
    }

    /**
     * Periode fokus: periode terbaru yang tenggatnya sudah lewat; bila belum ada, periode berjalan
     */
    public static function focusPeriod(array $type, ?string $now = null): ?array
    {
        $now = $now ?? date('Y-m-d H:i:s');
        $periods = self::recentPeriods($type, 4, substr($now, 0, 10));
        if (!$periods) {
            return null;
        }
        foreach (array_reverse($periods) as $p) {
            if ($p['deadline'] && $now > $p['deadline']) {
                return $p;
            }
        }
        return end($periods);
    }

    /**
     * Status kepatuhan per unit & periode: [unitId][periodStart] => ['code', 'late', 'report_id']
     *
     * @param int[] $unitIds
     */
    public function matrix(array $type, array $unitIds, array $periods, ?string $now = null): array
    {
        $unitIds = array_values(array_filter(array_map('intval', $unitIds)));
        if (!$unitIds || !$periods) {
            return [];
        }
        $now = $now ?? date('Y-m-d H:i:s');
        $starts = array_column($periods, 'start');
        $rows = $this->fetchAll(
            "SELECT id, satker_id, period_key, status, is_nil, submitted_at
             FROM posko_reports
             WHERE report_type_id = ? AND is_recap = 0 AND status <> 'CANCELLED'
               AND satker_id IN (" . implode(',', $unitIds) . ")
               AND period_key IN (" . implode(',', array_fill(0, count($starts), '?')) . ")",
            array_merge([(int)$type['id']], $starts)
        );
        $byKey = [];
        foreach ($rows as $r) {
            $byKey[$r['satker_id'] . '|' . $r['period_key']] = $r;
        }

        $out = [];
        foreach ($unitIds as $u) {
            foreach ($periods as $p) {
                $out[$u][$p['start']] = self::classify($byKey[$u . '|' . $p['start']] ?? null, $p['deadline'], $now);
            }
        }
        return $out;
    }

    public static function classify(?array $report, ?string $deadline, string $now): array
    {
        $pastDeadline = $deadline !== null && $now > $deadline;
        if (!$report) {
            return ['code' => $pastDeadline ? 'MISSING_LATE' : 'NOT_CREATED', 'late' => $pastDeadline, 'report_id' => null];
        }
        $late = $deadline !== null && ($report['submitted_at'] ? $report['submitted_at'] > $deadline : $pastDeadline);
        $code = match (true) {
            (int)$report['is_nil'] === 1 && in_array($report['status'], ['SUBMITTED', 'IN_REVIEW', 'APPROVED', 'FORWARDED'], true) => 'NIL',
            $report['status'] === 'FORWARDED' => 'APPROVED',
            default => $report['status'],
        };
        return ['code' => $code, 'late' => $late, 'report_id' => (int)$report['id']];
    }
}
