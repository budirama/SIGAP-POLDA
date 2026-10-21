<?php
/**
 * SIGAP-POLRI: Layanan Kepatuhan UU PDP (Perlindungan Data Pribadi)
 * Enkripsi At-Rest AES-256, Dynamic Data Masking & Dual-Control Audit Logging
 */

declare(strict_types=1);

namespace App\Core;

use App\Config\Config;
use App\Config\Database;
use PDO;
use Exception;

class OpsPdp
{
    private const CIPHER = 'AES-256-CBC';

    /**
     * Ambil encryption key dari konfigurasi .env atau key default deterministik
     */
    private static function getKey(): string
    {
        $rawKey = Config::env('OPS_PDP_ENCRYPTION_KEY', 'KunciRahasiaDinasPolriPresisi2026SatuNomorSatuJejak');
        return hash('sha256', $rawKey, true);
    }

    /**
     * Enkripsi data sensitif (NIK, Nama Korban, Kontak Whistleblower)
     */
    public static function encrypt(string $plainText): string
    {
        if ($plainText === '') {
            return '';
        }

        $key = self::getKey();
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $cipherText = openssl_encrypt($plainText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($cipherText === false) {
            return $plainText;
        }

        // Simpan gabungan IV + Ciphertext dalam biner
        return $iv . $cipherText;
    }

    /**
     * Alias spesifik enkripsi NIK
     */
    public static function encryptNik(string $nik): string
    {
        return self::encrypt($nik);
    }

    /**
     * Dekripsi data sensitif biner ke teks asli
     */
    public static function decrypt(?string $cipherBinary): ?string
    {
        if ($cipherBinary === null || $cipherBinary === '') {
            return null;
        }

        $key = self::getKey();
        $ivLength = openssl_cipher_iv_length(self::CIPHER);

        if (strlen($cipherBinary) <= $ivLength) {
            return $cipherBinary;
        }

        $iv = substr($cipherBinary, 0, $ivLength);
        $cipherText = substr($cipherBinary, $ivLength);

        $plainText = openssl_decrypt($cipherText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        return $plainText !== false ? $plainText : null;
    }

    /**
     * Samarkan NIK (16 digit) untuk tampilan publik / personil non-berwenang
     * Contoh: 3171012304950001 -> 3171**********01
     */
    public static function maskNik(?string $nik): string
    {
        if (empty($nik)) {
            return '-';
        }
        $clean = trim((string)$nik);
        $len = strlen($clean);
        if ($len < 6) {
            return str_repeat('*', $len);
        }
        return substr($clean, 0, 4) . str_repeat('*', max(0, $len - 6)) . substr($clean, -2);
    }

    /**
     * Samarkan Nomor Telepon
     * Contoh: 081298765432 -> 0812****5432
     */
    public static function maskPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '-';
        }
        $clean = trim((string)$phone);
        $len = strlen($clean);
        if ($len <= 8) {
            return substr($clean, 0, 3) . '****';
        }
        return substr($clean, 0, 4) . '****' . substr($clean, -4);
    }

    /**
     * Rekam audit akses data pribadi pelapor/korban ke tabel ops_audit_pdp_logs
     * Mendeteksi otomatis jika diakses oleh personil di luar satker penangan (Cross-Jurisdiction Alert)
     */
    public static function logAccess(
        string $trackingNumber,
        int $caseSatkerId,
        string $accessedData,
        string $justification = 'Pemeriksaan berkas perkara dinas'
    ): bool {
        try {
            $user = Auth::user();
            $viewerNrp = $user['nrp'] ?? 'GUEST_CITIZEN';
            $viewerSatkerId = (int)($user['satker_id'] ?? 0);
            $userRole = $user['role'] ?? 'ROLE_CITIZEN';

            // Jika dibuka oleh pejabat pengawas pusat (Mabes/Itwasum/Kapolri), bukan anomali
            $isSuperSatker = in_array($userRole, ['ROLE_EXECUTIVE', 'ROLE_SUPERADMIN', 'ROLE_INTERNAL_AFFAIRS'], true);
            $isOutOfJurisdiction = (!$isSuperSatker && $viewerSatkerId > 0 && $viewerSatkerId !== $caseSatkerId) ? 1 : 0;

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System';

            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                "INSERT INTO `ops_audit_pdp_logs`
                 (`tracking_number`, `viewer_nrp`, `viewer_satker_id`, `accessed_data`, `justification`, `is_out_of_jurisdiction`, `ip_address`, `user_agent`, `accessed_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            return $stmt->execute([
                $trackingNumber,
                $viewerNrp,
                $viewerSatkerId,
                $accessedData,
                $justification,
                $isOutOfJurisdiction,
                $ip,
                substr($ua, 0, 255)
            ]);
        } catch (Exception $e) {
            error_log("OpsPdp Log Error: " . $e->getMessage());
            return false;
        }
    }
}
