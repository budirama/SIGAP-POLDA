<?php
/**
 * SIGAP-POLRI: Seeder Runner Modul Satu Nomor, Satu Jejak
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/Config.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/core/OpsPdp.php';

try {
    $pdo = \App\Config\Database::getConnection();
    $sqlFile = __DIR__ . '/../database/seeders_ops.sql';
    
    if (!file_exists($sqlFile)) {
        throw new RuntimeException("File seeder tidak ditemukan: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);

    // Update NIK terenkripsi untuk kasus demo
    $niks = [
        'POL-202610-110-8924A' => '3171011508890001',
        'POL-202610-SPKT-7712B' => '3171025510920002',
        'POL-202610-WEB-4102C' => '3273011204850003',
        'POL-202610-CYB-3310D' => '3578016409940004',
        'POL-202610-DMS-1049E' => '3171012304950001',
        'POL-202610-BHB-5521F' => '3374011802760005',
        'POL-202610-DEF-9901G' => '3174012903820006'
    ];

    $stmtNik = $pdo->prepare("UPDATE `ops_global_tracks` SET `citizen_nik_encrypted` = ? WHERE `tracking_number` = ?");
    foreach ($niks as $trackNum => $nikRaw) {
        $encrypted = \App\Core\OpsPdp::encrypt($nikRaw);
        $stmtNik->execute([$encrypted, $trackNum]);
    }

    echo "SEED_OPS_SUCCESS: Data seeder Satu Nomor, Satu Jejak berhasil diisikan.\n";
} catch (Throwable $e) {
    echo "SEED_OPS_FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
