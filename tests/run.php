<?php
/**
 * SIGAP-POLRI: Test Runner (tanpa dependensi)
 *
 * Penggunaan:
 *   php tests/run.php              # semua test
 *   php tests/run.php unit         # hanya unit test (tanpa server/DB)
 *   php tests/run.php integration  # hanya integration test HTTP
 *
 * Integration test membutuhkan: Apache + MySQL berjalan, database berisi data seeder,
 * dan .env dengan APP_ENV=development. URL dapat diubah via env TEST_BASE_URL.
 * Data uji (judul berawalan ZZTEST_) dibersihkan otomatis.
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$root = dirname(__DIR__);
spl_autoload_register(function (string $class) use ($root) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $file = $root . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
if (is_file($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php'; // PhpSpreadsheet untuk membaca hasil ekspor .xlsx
}
require_once __DIR__ . '/lib/TestKit.php';

$baseDir = basename($root);
define('TEST_BASE_URL', rtrim(getenv('TEST_BASE_URL') ?: "http://localhost/{$baseDir}/public", '/'));

$filter = $argv[1] ?? 'all';
$dirs = match ($filter) {
    'unit' => ['Unit'],
    'integration' => ['Integration'],
    default => ['Unit', 'Integration'],
};

if (in_array('Integration', $dirs, true) && !\App\Config\Config::isDevelopment()) {
    fwrite(STDERR, "Integration test hanya boleh dijalankan dengan APP_ENV=development (data uji ditulis ke database).\n");
    exit(2);
}

$passed = 0;
$failures = [];

foreach ($dirs as $dir) {
    $files = glob(__DIR__ . "/{$dir}/*Test.php");
    if (!in_array($filter, ['all', 'unit', 'integration'], true)) {
        $files = array_filter($files, fn($f) => stripos(basename($f), $filter) !== false);
    }
    sort($files);
    $needsCleanup = $dir === 'Integration';

    foreach ($files as $file) {
        if ($needsCleanup) {
            Tests\Db::cleanup();
        }
        /** @var array<string, callable> $tests */
        $tests = require $file;
        $group = $dir . '/' . basename($file, '.php');
        echo "\n{$group}\n";

        foreach ($tests as $name => $test) {
            try {
                $test();
                $passed++;
                echo "  ✓ {$name}\n";
            } catch (\Throwable $e) {
                $failures[] = "{$group} › {$name}";
                $where = $e instanceof Tests\AssertionFailed ? '' : ' [' . get_class($e) . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . ']';
                echo "  ✗ {$name}\n      {$e->getMessage()}{$where}\n";
            }
        }
    }

    if ($needsCleanup) {
        Tests\Db::cleanup();
    }
}

$total = $passed + count($failures);
echo "\n" . str_repeat('-', 50) . "\n";
echo count($failures) === 0
    ? "LULUS: {$passed}/{$total} test\n"
    : "GAGAL: " . count($failures) . " dari {$total} test\n  - " . implode("\n  - ", $failures) . "\n";

exit(count($failures) === 0 ? 0 : 1);
