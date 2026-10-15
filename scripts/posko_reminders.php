<?php
/**
 * Pengingat tenggat Management Posko (P5-06). Jalankan terjadwal, mis. tiap 30 menit:
 *   php scripts/posko_reminders.php            # waktu sekarang
 *   php scripts/posko_reminders.php --now="2026-10-15 22:00:00"   # simulasi/uji
 *
 * - Tenggat mendekat : tenggat periode fokus dalam 24 jam ke depan & unit belum mengirim → operator unit.
 * - Terlambat        : tenggat sudah lewat (maks. 7 hari) & unit belum mengirim → operator unit.
 * Aman dijalankan berulang: kunci dedupe per unit/jenis/periode mencegah pengingat ganda.
 * Laporan yang belum masuk tidak pernah dianggap nihil; yang sudah dikirim (termasuk nihil) tidak diingatkan.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Models\PoskoCompliance;
use App\Models\PoskoNotification;
use App\Models\PoskoReportType;
use App\Models\Satker;
use App\Core\PoskoSchedule;

$now = date('Y-m-d H:i:s');
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--now=')) {
        $parsed = strtotime(substr($arg, 6));
        if ($parsed === false) {
            fwrite(STDERR, "Format --now tidak valid.\n");
            exit(2);
        }
        $now = date('Y-m-d H:i:s', $parsed);
    }
}

const SOON_HOURS = 24;
const LATE_DAYS = 7;

$notifications = new PoskoNotification();
$compliance = new PoskoCompliance();
$units = array_filter((new Satker())->getAll(), fn($s) => (int)$s['is_active'] && $s['type'] !== 'DIREKTORAT');
$soonLimit = date('Y-m-d H:i:s', strtotime($now . ' +' . SOON_HOURS . ' hours'));
$lateFloor = date('Y-m-d H:i:s', strtotime($now . ' -' . LATE_DAYS . ' days'));
$created = ['deadline_soon' => 0, 'deadline_late' => 0];

foreach ((new PoskoReportType())->all(true) as $type) {
    if ($type['period_unit'] === 'NONE') {
        continue;
    }
    $levels = array_map('intval', explode(',', (string)$type['applies_to_levels']));
    $typeUnits = array_filter($units, fn($s) => in_array((int)$s['level'], $levels, true));
    if (!$typeUnits) {
        continue;
    }
    $periods = PoskoCompliance::recentPeriods($type, 4, substr($now, 0, 10));
    $matrix = $compliance->matrix($type, array_column($typeUnits, 'id'), $periods, $now);

    foreach ($periods as $p) {
        if (!$p['deadline']) {
            continue;
        }
        $kind = null;
        if ($p['deadline'] > $now && $p['deadline'] <= $soonLimit) {
            $kind = 'deadline_soon';
        } elseif ($p['deadline'] <= $now && $p['deadline'] >= $lateFloor) {
            $kind = 'deadline_late';
        }
        if ($kind === null) {
            continue;
        }
        $label = PoskoSchedule::label($type['period_unit'], $p['start'], $p['end']);
        $deadlineText = date('d/m/Y H:i', strtotime($p['deadline']));

        foreach ($typeUnits as $u) {
            $cell = $matrix[(int)$u['id']][$p['start']] ?? null;
            // Hanya unit yang belum membuat/mengirim: belum dibuat, tidak masuk, draft, atau perlu perbaikan
            if (!$cell || !in_array($cell['code'], ['NOT_CREATED', 'MISSING_LATE', 'DRAFT', 'NEEDS_REVISION'], true)) {
                continue;
            }
            $key = "{$kind}:{$type['id']}:{$u['id']}:{$p['start']}";
            $title = $kind === 'deadline_soon' ? 'Tenggat laporan mendekat' : 'Laporan terlambat';
            $body = $kind === 'deadline_soon'
                ? "{$type['name']} {$label} harus dikirim sebelum {$deadlineText}."
                : "{$type['name']} {$label} melewati tenggat {$deadlineText} dan belum dikirim.";
            $link = $cell['report_id'] ? '/posko-laporan/show/' . $cell['report_id'] : '/posko-laporan/create?type=' . (int)$type['id'];
            $created[$kind] += $notifications->notify($notifications->operatorsOf((int)$u['id']), $kind, $title, $body, $link, $key);
        }
    }
}

echo "[{$now}] Pengingat baru — tenggat mendekat: {$created['deadline_soon']}, terlambat: {$created['deadline_late']}\n";
