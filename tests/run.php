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
require_once __DIR__ . '/lib/TestKit.php';

define('TEST_BASE_URL', rtrim(getenv('TEST_BASE_URL') ?: 'http://localhost/SIGAP-Polda/public', '/'));

$suite = $argv[1] ?? 'all';
$dirs = match ($suite) {
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
    sort($files);
    $needsCleanup = $dir === 'Integration';

    if ($needsCleanup) {
        Tests\Db::cleanup();
    }

    foreach ($files as $file) {
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
