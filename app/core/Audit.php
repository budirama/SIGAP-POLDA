<?php
/**
 * SIGAP-POLRI: Pencatat Jejak Audit
 */

namespace App\Core;

use App\Models\AuditLog;

final class Audit
{
    /**
     * Catat satu kejadian audit. Kegagalan pencatatan tidak boleh menggagalkan alur utama.
     */
    public static function log(string $action, ?int $reportId = null, ?string $detail = null): void
    {
        try {
            $user = Auth::user();
            (new AuditLog())->insert(
                $user ? (int)$user['id'] : null,
                $user['role'] ?? null,
                $action,
                $reportId,
                $detail !== null ? mb_substr($detail, 0, 500) : null,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            );
        } catch (\Throwable $e) {
            error_log('[SIGAP-POLRI] Gagal mencatat audit ' . $action . ': ' . $e->getMessage());
        }
    }
}
