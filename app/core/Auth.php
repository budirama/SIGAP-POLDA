<?php
/**
 * SIGAP-POLDA: RBAC Authentication & Guard Helper
 */

namespace App\Core;

use App\Config\Config;
use App\Config\Constants;

class Auth
{
    public static function login(array $user): void
    {
        Session::start();
        // Hapus password hash dari session demi keamanan
        unset($user['password_hash']);
        Session::set('auth_user', $user);
        session_regenerate_id(true); // Mencegah session fixation
    }

    public static function logout(): void
    {
        Session::start();
        Session::remove('auth_user');
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::has('auth_user');
    }

    public static function user(): ?array
    {
        return Session::get('auth_user');
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
