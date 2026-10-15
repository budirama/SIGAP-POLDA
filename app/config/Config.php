<?php
/**
 * SIGAP-POLDA: Konfigurasi Global Aplikasi
 */

namespace App\Config;

class Config
{
    // Konfigurasi Database
    public const DB_HOST = '127.0.0.1';
    public const DB_PORT = '3306';
    public const DB_NAME = 'sigap_polda';
    public const DB_USER = 'root';
    public const DB_PASS = '';
    public const DB_CHARSET = 'utf8mb4';

    // Konfigurasi Aplikasi & Identitas
    public const APP_NAME = 'SIGAP-POLDA';
    public const APP_FULL_NAME = 'Sistem Informasi & Pengaduan Terpadu Polda';
    public const APP_MOTTO = 'Rastra Sewakotama - Polri Presisi';
    public const APP_VERSION = '1.1.0';

    // Lingkungan Aplikasi: 'development' atau 'production'
    // WAJIB diubah ke 'production' saat deploy (menonaktifkan Quick Login uji coba)
    public const APP_ENV = 'development';

    public static function isDevelopment(): bool
    {
        return self::APP_ENV === 'development';
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
