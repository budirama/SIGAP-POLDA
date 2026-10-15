<?php
/**
 * SIGAP-POLRI: Single Entry Point
 * Sistem Informasi & Pengaduan Terpadu Polri (PHP Native MVC)
 */

declare(strict_types=1);

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

// Pustaka Composer (PhpSpreadsheet, Dompdf) — opsional; tanpa vendor/ fitur ekspor menampilkan petunjuk pemasangan
$composerAutoload = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

// Error Reporting: detail error hanya ditampilkan di lingkungan development
$isDevelopment = \App\Config\Config::isDevelopment();
error_reporting(E_ALL);
ini_set('display_errors', $isDevelopment ? '1' : '0');
ini_set('log_errors', '1');

// Header keamanan HTTP
header_remove('X-Powered-By');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=()');
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com https://maps.googleapis.com https://maps.gstatic.com; "
    . "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://unpkg.com https://fonts.googleapis.com; "
    . "font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com; "
    . "img-src 'self' data: https://unpkg.com https://*.tile.openstreetmap.org "
    . "https://*.googleapis.com https://*.gstatic.com https://*.ggpht.com; "
    . "connect-src 'self' https://maps.googleapis.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'");
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Mulai Sesi Aman
\App\Core\Session::start();

// Jalankan Front Controller App
try {
    new \App\Core\App();
} catch (\Throwable $e) {
    error_log('[SIGAP-POLRI] ' . $e);
    http_response_code(500);

    $errorDetail = $isDevelopment
        ? "<p class='text-slate-300 font-mono text-sm bg-black/40 p-3 rounded-lg border border-red-900/30 mb-4'>" . htmlspecialchars($e->getMessage()) . "</p>
              <pre class='text-xs text-slate-400 overflow-x-auto p-3 bg-black/30 rounded border border-slate-800'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>"
        : "<p class='text-slate-300 text-sm'>Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi atau hubungi Admin TIK Polri.</p>";

    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>500 - Server Error</title>
          <script src='https://cdn.tailwindcss.com'></script></head>
          <body class='bg-[#070F1E] text-slate-200 p-8 flex items-center justify-center min-h-screen'>
            <div class='max-w-2xl w-full bg-[#0E1E38] p-6 rounded-2xl border border-red-800/50 shadow-2xl'>
              <h1 class='text-2xl font-bold text-red-500 mb-2'>Kesalahan Sistem (500)</h1>
              {$errorDetail}
            </div>
          </body></html>";
}
