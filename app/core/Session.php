<?php
/**
 * SIGAP-POLRI: Secure Session Management & CSRF Protection
 */

namespace App\Core;

class Session
{
    public const IDLE_TIMEOUT = 30 * 60;          // 30 menit tanpa aktivitas
    public const ABSOLUTE_TIMEOUT = 8 * 60 * 60;  // 8 jam sejak sesi dibuat

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set parameter cookie aman
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1'); // tolak ID sesi buatan klien (session fixation)
            ini_set('session.cookie_samesite', 'Strict');
            if (self::isHttps()) {
                ini_set('session.cookie_secure', '1');
            }

            session_start();

            $now = time();
            // Fingerprint User-Agent (tanpa IP agar pengguna seluler/CGNAT tidak terputus)
            $fingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
            $mismatch = isset($_SESSION['_fingerprint']) && !hash_equals($_SESSION['_fingerprint'], $fingerprint);

            if ($mismatch || self::isExpired($_SESSION, $now)) {
                $hadLogin = isset($_SESSION['auth_user']);
                self::restart();
                if ($hadLogin) {
                    $_SESSION['_flash']['error'][] = 'Sesi Anda telah berakhir. Silakan login kembali.';
                }
            }

            $_SESSION['_fingerprint'] = $fingerprint;
            $_SESSION['_created'] ??= $now;
            $_SESSION['_last_activity'] = $now;
        }
    }

    /**
     * Sesi kedaluwarsa bila tidak aktif melewati batas idle atau melewati umur maksimum
     */
    public static function isExpired(array $session, int $now): bool
    {
        if (isset($session['_last_activity']) && $now - (int)$session['_last_activity'] > self::IDLE_TIMEOUT) {
            return true;
        }
        return isset($session['_created']) && $now - (int)$session['_created'] > self::ABSOLUTE_TIMEOUT;
    }

    /**
     * Hapus seluruh isi sesi dan terbitkan ID sesi baru
     */
    public static function restart(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    /**
     * Ganti ID sesi (setelah peningkatan hak akses: login, verifikasi PIN)
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    // Flash Messages
    public static function setFlash(string $type, string $message): void
    {
        self::start();
        $_SESSION['_flash'][$type][] = $message;
    }

    public static function getFlash(string $type): array
    {
        self::start();
        $messages = $_SESSION['_flash'][$type] ?? [];
        unset($_SESSION['_flash'][$type]);
        return $messages;
    }

    public static function hasFlash(string $type): bool
    {
        self::start();
        return !empty($_SESSION['_flash'][$type]);
    }

    // CSRF Protection Token
    public static function generateCsrf(): string
    {
        self::start();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function getCsrfToken(): string
    {
        return self::generateCsrf();
    }

    public static function validateCsrf(?string $token): bool
    {
        self::start();
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}
