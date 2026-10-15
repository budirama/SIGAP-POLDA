<?php
/**
 * SIGAP-POLDA: Kebijakan Hak Akses ke Data Laporan
 */

namespace App\Core;

use App\Config\Constants;

class ReportAccess
{
    // Role yang berwenang melihat seluruh laporan
    private const FULL_ACCESS_ROLES = [
        Constants::ROLE_SUPERADMIN,
        Constants::ROLE_SPKT_OPERATOR,
        Constants::ROLE_INTERNAL_AFFAIRS,
        Constants::ROLE_EXECUTIVE,
    ];

    /**
     * Personil dinas yang berwenang atas laporan ini:
     * pimpinan/pengawas semua laporan, Satker hanya disposisinya, Penyidik hanya kasusnya.
     */
    public static function officerCanView(array $report): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $role = Auth::role();
        if (in_array($role, self::FULL_ACCESS_ROLES, true)) {
            return true;
        }

        return match ($role) {
            Constants::ROLE_SATKER_HEAD, Constants::ROLE_SATKER_ADMIN => !empty($report['current_satker_id'])
                && (int)$report['current_satker_id'] === Auth::satkerId(),
            Constants::ROLE_INVESTIGATOR => (int)($report['assigned_investigator_id'] ?? 0) === Auth::id(),
            default => false,
        };
    }

    /**
     * Pelapor: akun warga pemilik laporan, atau sesi yang telah memverifikasi PIN tiket
     */
    public static function reporterCanView(array $report): bool
    {
        if (Auth::check() && Auth::role() === Constants::ROLE_CITIZEN
            && !empty($report['reporter_id']) && (int)$report['reporter_id'] === Auth::id()) {
            return true;
        }

        $granted = Session::get('report_access', []);
        return !empty($granted[$report['ticket_number']]);
    }

    public static function grantReporter(string $ticketNumber): void
    {
        $granted = Session::get('report_access', []);
        $granted[$ticketNumber] = true;
        Session::set('report_access', $granted);
    }
}
