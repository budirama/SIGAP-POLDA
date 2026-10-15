<?php
/**
 * Migrasi: pindahkan file unggahan lama dari public/uploads/ (dapat diakses publik)
 * ke storage/uploads/ (privat). Path di database ("uploads/<nama file>") tetap valid.
 * Jalankan sekali via CLI:
 *   php database/migrations/2026_10_15_move_uploads_to_storage.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$root = dirname(__DIR__, 2);
$from = $root . '/public/uploads';
$to = $root . '/storage/uploads';

if (!is_dir($to)) {
    mkdir($to, 0750, true);
}

$moved = 0;
$skipped = 0;
foreach (is_dir($from) ? scandir($from) : [] as $name) {
    $source = $from . '/' . $name;
    if ($name === '.' || $name === '..' || $name === '.gitkeep' || !is_file($source)) {
        continue;
    }

    $target = $to . '/' . $name;
    if (file_exists($target)) {
        echo "Lewati (sudah ada di storage): {$name}" . PHP_EOL;
        $skipped++;
        continue;
    }

    if (rename($source, $target)) {
        $moved++;
    } else {
        echo "GAGAL memindahkan: {$name}" . PHP_EOL;
        $skipped++;
    }
}

echo "File dipindahkan ke storage/uploads: {$moved}, dilewati: {$skipped}" . PHP_EOL;
