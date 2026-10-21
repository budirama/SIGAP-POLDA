<?php
/**
 * SIGAP-POLRI: Core Module Manager & Dynamic Feature Flagging
 * Mengelola ketersediaan modul, pemblokiran rute dinamis, dan rendering menu
 */

namespace App\Core;

use App\Models\SystemModule;

class ModuleManager
{
    private static ?array $statusMap = null;

    /**
     * Inisialisasi peta status modul (di-cache dalam memori selama siklus request)
     */
    public static function init(): void
    {
        if (self::$statusMap === null) {
            try {
                $model = new SystemModule();
                self::$statusMap = $model->getStatusMap();
            } catch (\Throwable $e) {
                // Fallback jika database belum termigrasi atau ada kendala koneksi
                self::$statusMap = [];
            }
        }
    }

    /**
     * Bersihkan cache memori (berguna saat test atau setelah toggle)
     */
    public static function clearCache(): void
    {
        self::$statusMap = null;
    }

    /**
     * Cek apakah sebuah modul / sub-modul sedang aktif
     * Jika modul induk dinonaktifkan, seluruh sub-modul di bawahnya otomatis menjadi nonaktif
     */
    public static function isActive(string $moduleKey): bool
    {
        self::init();

        if (!isset(self::$statusMap[$moduleKey])) {
            // Modul tidak terdaftar dalam tabel -> anggap aktif (backward compatibility)
            return true;
        }

        $item = self::$statusMap[$moduleKey];

        // Jika sub-modul, periksa juga status modul induknya
        if (!empty($item['parent_key']) && isset(self::$statusMap[$item['parent_key']])) {
            $parent = self::$statusMap[$item['parent_key']];
            if (!$parent['is_active']) {
                return false;
            }
        }

        return (bool)$item['is_active'];
    }

    /**
     * Middleware Guard: Menolak akses dengan HTTP 403 jika modul dinonaktifkan
     */
    public static function requireActive(string $moduleKey, ?string $customMessage = null): void
    {
        if (self::isActive($moduleKey)) {
            return;
        }

        $msg = $customMessage ?? "Modul atau fitur ini sedang dinonaktifkan sementara oleh Administrator / Divisi IT.";

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => $msg,
                'code'    => 'MODULE_DISABLED'
            ]);
            exit;
        }

        // Tampilkan halaman khusus HTTP 403 Modul Dinonaktifkan
        http_response_code(403);
        $baseUrl = \App\Config\Config::baseUrl();
        $module = (new SystemModule())->findByKey($moduleKey);
        $moduleName = $module['name'] ?? $moduleKey;

        $viewPath = dirname(__DIR__) . '/views/errors/module_disabled.php';
        if (is_file($viewPath)) {
            require $viewPath;
        } else {
            echo "<h1>403 - Akses Ditolak</h1><p>{$msg}</p><a href='{$baseUrl}/'>Kembali ke Beranda</a>";
        }
        exit;
    }

    /**
     * Toggle status aktif modul melalui Model
     */
    public static function toggle(string $moduleKey, bool $isActive, ?int $userId = null): bool
    {
        $model = new SystemModule();
        $res = $model->setStatus($moduleKey, $isActive, $userId);
        self::clearCache();
        return $res;
    }

    /**
     * Perbarui metadata modul
     */
    public static function updateInfo(string $moduleKey, array $data, ?int $userId = null): bool
    {
        $model = new SystemModule();
        $res = $model->updateModule($moduleKey, $data, $userId);
        self::clearCache();
        return $res;
    }
}
