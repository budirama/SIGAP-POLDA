<?php
/**
 * SIGAP-POLDA: Single Entry Point
 * Sistem Informasi & Pengaduan Terpadu Polda (PHP Native MVC)
 */

declare(strict_types=1);

// Error Reporting (Development Mode)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set Timezone Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

// PSR-4 Autoloader Sederhana untuk Namespace App\
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Mulai Sesi Aman
\App\Core\Session::start();

// Jalankan Front Controller App
try {
    new \App\Core\App();
} catch (\Throwable $e) {
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>500 - Server Error</title>
          <script src='https://cdn.tailwindcss.com'></script></head>
          <body class='bg-[#070F1E] text-slate-200 p-8 flex items-center justify-center min-h-screen'>
            <div class='max-w-2xl w-full bg-[#0E1E38] p-6 rounded-2xl border border-red-800/50 shadow-2xl'>
              <h1 class='text-2xl font-bold text-red-500 mb-2'>Kesalahan Sistem (500)</h1>
              <p class='text-slate-300 font-mono text-sm bg-black/40 p-3 rounded-lg border border-red-900/30 mb-4'>" . htmlspecialchars($e->getMessage()) . "</p>
              <pre class='text-xs text-slate-400 overflow-x-auto p-3 bg-black/30 rounded border border-slate-800'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
            </div>
          </body></html>";
}
