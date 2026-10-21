<?php
/**
 * Watchdog SLA tiket disposisi Satker. Jalankan terjadwal, mis. tiap 15 menit:
 *   php scripts/disposition_watchdog.php
 *
 * Tiket yang belum diterima Satker melewati batas waktu (EMERGENCY 1 jam, HIGH 6 jam, NORMAL 24 jam)
 * ditandai eskalasi dan tampil di meja SPKT serta inbox satuan induk. Aman dijalankan berulang.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

date_default_timezone_set('Asia/Jakarta');

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

$count = (new \App\Models\Disposition())->escalateOverdue();
echo date('Y-m-d H:i:s') . " Eskalasi tiket disposisi: {$count}\n";
