<?php
/**
 * SIGAP-POLRI: Koneksi Database PDO Singleton
 */

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                Config::env('DB_HOST', '127.0.0.1'),
                Config::env('DB_PORT', '3306'),
                Config::env('DB_NAME', 'sigap_polda'),
                Config::env('DB_CHARSET', 'utf8mb4')
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Menggunakan Native Prepared Statements aman
                // Zona waktu WIB (konsisten dengan PHP Asia/Jakarta) & mode strict (tolak data terpotong/tidak valid)
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+07:00', "
                    . "sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
            ];

            try {
                self::$instance = new PDO($dsn, Config::env('DB_USER', 'root'), Config::env('DB_PASS'), $options);
            } catch (PDOException $e) {
                throw new RuntimeException("Koneksi Database SIGAP-POLRI Gagal: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
