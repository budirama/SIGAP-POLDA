<?php
/**
 * SIGAP-POLRI: Runner Migrasi Skema Satu Nomor, Satu Jejak
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/Database.php';

try {
    $pdo = \App\Config\Database::getConnection();
    $sqlFile = __DIR__ . '/../database/migrations/2026_10_18_ops_satu_nomor.sql';
    
    if (!file_exists($sqlFile)) {
        throw new RuntimeException("File migrasi tidak ditemukan: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);

    echo "MIGRATION_OPS_SUCCESS: Tabel-tabel Satu Nomor, Satu Jejak berhasil dipasang.\n";
} catch (Throwable $e) {
    echo "MIGRATION_OPS_FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
