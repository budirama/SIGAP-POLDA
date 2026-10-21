<?php
/**
 * SIGAP-POLRI: RBAC Authentication & Guard Helper
 */

namespace App\Core;

use App\Config\Config;
use App\Config\Constants;

class Auth
{
    private static bool $refreshed = false;

    public static function login(array $user): void
    {
        Session::start();
        // Hapus password hash dari session demi keamanan
        unset($user['password_hash']);
        Session::set('auth_user', $user);
        Session::remove('_csrf_token'); // token CSRF baru untuk sesi login
        Session::regenerate(); // Mencegah session fixation
        self::$refreshed = true;
    }

    public static function logout(): void
    {
        Session::start();
        Session::remove('auth_user');
        Session::destroy();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        self::refresh();
        return Session::get('auth_user');
    }

    /**
     * Sekali per request: muat ulang data user dari database agar penonaktifan,
     * perubahan role, atau mutasi Satker langsung berlaku pada sesi yang sedang berjalan.
     */
    private static function refresh(): void
    {
        if (self::$refreshed) {
            return;
        }
        self::$refreshed = true;

        $cached = Session::get('auth_user');
        if (!$cached) {
            return;
        }

        $fresh = (new \App\Models\User())->findById((int)$cached['id']);
        if (!$fresh || !(int)$fresh['is_active']) {
            Session::restart();
            Session::setFlash('error', 'Akun Anda tidak aktif. Silakan hubungi Admin TIK Polri.');
            return;
        }

        unset($fresh['password_hash']);
        Session::set('auth_user', $fresh);
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int)$user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function satkerId(): ?int
    {
        $user = self::user();
        return isset($user['satker_id']) ? (int)$user['satker_id'] : null;
    }

    public static function hasRole(string|array $roles): bool
    {
        if (!self::check()) {
            return false;
        }

        $userRole = self::role();
        if ($userRole === Constants::ROLE_SUPERADMIN) {
            return true; // Superadmin memiliki akses universal
        }

        if (is_array($roles)) {
            return in_array($userRole, $roles, true);
        }

        return $userRole === $roles;
    }

    /**
     * Guard Middleware: Pastikan pengguna sudah login
     */
    public static function requireAuth(?string $redirectUrl = null): void
    {
        $baseUrl = Config::getBaseUrl();

        if (!self::check()) {
            Session::setFlash('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
            header("Location: {$baseUrl}/auth/login");
            exit;
        }
    }

    /**
     * Guard Middleware: Pastikan pengguna login dan memiliki role yang diizinkan
     */
    public static function requireRole(string|array $roles, ?string $redirectUrl = null): void
    {
        $baseUrl = Config::getBaseUrl();

        if (!self::check()) {
            Session::setFlash('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
            header("Location: {$baseUrl}/auth/login");
            exit;
        }

        if (!self::hasRole($roles)) {
            Session::setFlash('error', 'Akses Ditolak: Anda tidak memiliki wewenang untuk membuka halaman ini.');
            $target = $redirectUrl ?? "{$baseUrl}/";
            header("Location: {$target}");
            exit;
        }
    }
}
