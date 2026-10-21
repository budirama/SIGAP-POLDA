<?php
/**
 * SIGAP-POLRI: Generator & Validator Pengenal Kasus Terpadu
 * Standar: "Satu Nomor, Satu Jejak" (POL-YYYYMM-KANAL-XXXXX)
 */

declare(strict_types=1);

namespace App\Core;

class OpsTracker
{
    public const CHANNELS = [
        '110_CALL'     => '110',
        'SPKT_LOKET'   => 'SPKT',
        'WEB_PORTAL'   => 'WEB',
        'SUPERAPP'     => 'APP',
        'DUMAS_PROPAM' => 'DMS',
        'CYBER_CRIME'  => 'CYB'
    ];

    /**
     * Hasilkan nomor lacak global unik: POL-YYYYMM-[KANAL]-[5_CHAR_ALPHANUM]
     * Contoh: POL-202610-110-7B9A2
     */
    public static function generateNumber(string $channelKey = 'WEB_PORTAL'): string
    {
        $yearMonth = date('Ym');
        $code = self::CHANNELS[$channelKey] ?? (strlen($channelKey) <= 4 ? strtoupper($channelKey) : 'GEN');
        $randomChars = strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));

        return sprintf('POL-%s-%s-%s', $yearMonth, $code, $randomChars);
    }

    /**
     * Alias untuk generateNumber
     */
    public static function generateTrackingNumber(string $channelKey = 'WEB_PORTAL'): string
    {
        return self::generateNumber($channelKey);
    }

    /**
     * Hasilkan 6-digit PIN keamanan pelapor publik
     */
    public static function generatePin(): string
    {
        return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Validasi format nomor tiket Satu Nomor
     */
    public static function isValidNumber(string $trackingNumber): bool
    {
        return (bool)preg_match('/^POL-\d{6}-[A-Z0-9]{3,4}-[A-Z0-9]{5}$/', trim($trackingNumber));
    }
}
