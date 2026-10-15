<?php
/**
 * SIGAP-POLRI: Kebijakan Hak Akses Management Posko (berbasis wilayah & tingkat)
 *
 * Matriks BRD 3.2: setiap tingkat melihat unitnya sendiri + seluruh unit di bawahnya,
 * memverifikasi laporan unit tepat satu tingkat di bawahnya, dan mengubah data unitnya sendiri.
 * System Administrator (ROLE_SUPERADMIN) mengelola konfigurasi global, bukan operasional laporan.
 */

namespace App\Core;

use App\Config\Constants;
use App\Models\Satker;

class PoskoAccess
{
    /** @var int[]|null cache per request */
    private static ?array $scope = null;
    private static ?int $scopeOwner = null;

    public static function isPoskoUser(): bool
    {
        return Auth::check() && in_array(Auth::role(), Constants::POSKO_ROLES, true);
    }

    public static function isSystemAdmin(): bool
    {
        return Auth::check() && Auth::role() === Constants::ROLE_SUPERADMIN;
    }

    /**
     * Tingkat posko pengguna (1 Polsek … 4 Pusat), null bila bukan pengguna posko
     */
    public static function level(): ?int
    {
        if (!self::isPoskoUser()) {
            return null;
        }
        $level = Auth::user()['satker_level'] ?? null;
        return $level !== null ? (int)$level : null;
    }

    public static function unitId(): ?int
    {
        return self::isPoskoUser() ? Auth::satkerId() : null;
    }

    /**
     * Id unit yang boleh dilihat: unit sendiri + seluruh turunannya. System Administrator: semua.
     *
     * @return int[]
     */
    public static function scopeSatkerIds(): array
    {
        $owner = Auth::id();
        if (self::$scope !== null && self::$scopeOwner === $owner) {
            return self::$scope;
        }

        $model = new Satker();
        if (self::isSystemAdmin()) {
            $ids = array_map('intval', array_column($model->getAll(), 'id'));
        } elseif (self::isPoskoUser() && Auth::satkerId()) {
            $ids = $model->selfAndDescendantIds((int)Auth::satkerId());
        } else {
            $ids = [];
        }

        self::$scopeOwner = $owner;
        return self::$scope = $ids;
    }

    /**
     * Unit berada dalam wilayah pengguna (lihat data/laporan unit tersebut)
     */
    public static function canViewUnit(int $satkerId): bool
    {
        return in_array($satkerId, self::scopeSatkerIds(), true);
    }

    /**
     * Membuat/mengubah laporan atas nama unit: hanya operator/admin unit itu sendiri
     */
    public static function canEditForUnit(int $satkerId): bool
    {
        return Auth::hasRole([Constants::ROLE_POSKO_OPERATOR, Constants::ROLE_POSKO_ADMIN])
            && Auth::satkerId() === $satkerId;
    }

    /**
     * Memverifikasi laporan dari unit: hanya operator/admin unit induk langsungnya
     */
    public static function canVerifyUnit(int $satkerId): bool
    {
        if (!Auth::hasRole([Constants::ROLE_POSKO_OPERATOR, Constants::ROLE_POSKO_ADMIN]) || !Auth::satkerId()) {
            return false;
        }
        $unit = (new Satker())->findById($satkerId);
        return $unit && (int)$unit['parent_id'] === Auth::satkerId();
    }

    /**
     * Mengelola akun pengguna posko pada unit: Admin Posko untuk unit dalam wilayahnya,
     * System Administrator untuk semua unit
     */
    public static function canManageUsersOf(int $satkerId): bool
    {
        if (self::isSystemAdmin()) {
            return true;
        }
        return Auth::role() === Constants::ROLE_POSKO_ADMIN && self::canViewUnit($satkerId);
    }

    /**
     * Mengelola struktur organisasi (tambah/pindah/nonaktifkan unit):
     * System Administrator & Admin Posko tingkat Pusat
     */
    public static function canManageOrganization(): bool
    {
        return self::isSystemAdmin()
            || (Auth::role() === Constants::ROLE_POSKO_ADMIN && self::level() === Constants::LEVEL_PUSAT);
    }

    /**
     * Uji coba: kosongkan cache cakupan (mis. setelah mutasi satker dalam satu request)
     */
    public static function resetCache(): void
    {
        self::$scope = null;
        self::$scopeOwner = null;
    }
}
