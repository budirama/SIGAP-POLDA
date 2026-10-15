<?php
/**
 * SIGAP-POLRI: Konfigurasi Global Aplikasi
 */

namespace App\Config;

class Config
{
    private static ?array $env = null;

    // Konfigurasi Aplikasi & Identitas
    public const APP_NAME = 'SIGAP-POLRI';
    public const APP_FULL_NAME = 'Sistem Informasi & Pengaduan Terpadu Polri';
    public const APP_MOTTO = 'Rastra Sewakotama - Polri Presisi';
    public const APP_VERSION = '1.1.0';

    /**
     * Baca nilai konfigurasi dari file .env di root proyek (fallback ke environment server)
     */
    public static function env(string $key, string $default = ''): string
    {
        if (self::$env === null) {
            $file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
            self::$env = is_readable($file) ? self::parseEnv((string)file_get_contents($file)) : [];
        }

        if (array_key_exists($key, self::$env)) {
            return self::$env[$key];
        }

        $server = getenv($key);
        return $server !== false ? $server : $default;
    }

    /**
     * Parse isi file .env: KEY=VALUE per baris, komentar "#" (baris penuh atau
     * setelah spasi pada nilai tanpa kutip), nilai boleh diapit kutip.
     */
    public static function parseEnv(string $content): array
    {
        $env = [];
        foreach (preg_split('/\R/', $content) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if (preg_match('/^(["\'])(.*)\1/', $value, $m)) {
                $value = $m[2];
            } else {
                $value = trim(preg_replace('/\s+#.*$/', '', $value));
            }
            $env[$name] = $value;
        }

        return $env;
    }

    // Lingkungan Aplikasi: 'development' atau 'production' (default production bila .env tidak ada)
    public static function isDevelopment(): bool
    {
        return self::env('APP_ENV', 'production') === 'development';
    }

    // API key Google Maps JavaScript dari .env; kosong/tidak valid = peta memakai Leaflet + OpenStreetMap
    public static function googleMapsKey(): string
    {
        return self::sanitizeMapsKey(self::env('GOOGLE_MAPS_API_KEY', ''));
    }

    public static function sanitizeMapsKey(string $key): string
    {
        $key = trim($key);
        return preg_match('/^[A-Za-z0-9_-]{20,100}$/', $key) ? $key : '';
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

    // Direktori Upload Bukti & Dokumen (privat, di luar web root public/)
    public static function getUploadDir(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
    }
}
