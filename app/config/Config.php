<?php
/**
 * SIGAP-POLDA: Konfigurasi Global Aplikasi
 */

namespace App\Config;

class Config
{
    private static ?array $env = null;

    // Konfigurasi Aplikasi & Identitas
    public const APP_NAME = 'SIGAP-POLDA';
    public const APP_FULL_NAME = 'Sistem Informasi & Pengaduan Terpadu Polda';
    public const APP_MOTTO = 'Rastra Sewakotama - Polri Presisi';
    public const APP_VERSION = '1.1.0';

    /**
     * Baca nilai konfigurasi dari file .env di root proyek (fallback ke environment server)
     */
    public static function env(string $key, string $default = ''): string
    {
        if (self::$env === null) {
            self::$env = [];
            $file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
            if (is_readable($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                        continue;
                    }
                    [$name, $value] = array_map('trim', explode('=', $line, 2));
                    self::$env[$name] = trim($value, "\"'");
                }
            }
        }

        if (array_key_exists($key, self::$env)) {
            return self::$env[$key];
        }

        $server = getenv($key);
        return $server !== false ? $server : $default;
    }

    // Lingkungan Aplikasi: 'development' atau 'production' (default production bila .env tidak ada)
    public static function isDevelopment(): bool
    {
        return self::env('APP_ENV', 'production') === 'development';
    }

    // Path Base URL (Deteksi Otomatis atau Fallback)
    public static function getBaseUrl(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            
            // Dapatkan subfolder path jika di htdocs
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            $scriptDir = rtrim($scriptDir, '/');
            
            return $protocol . $host . $scriptDir;
        }
        return 'http://localhost/SIGAP-Polda/public';
    }

    // Direktori Upload Bukti & Dokumen
    public static function getUploadDir(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
    }
}
