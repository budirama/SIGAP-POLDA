<?php
/**
 * SIGAP-POLRI: Throttle Model (Pembatasan Percobaan Generik: Login, Laporan Publik, PIN)
 */

namespace App\Models;

use App\Core\Model;

class Throttle extends Model
{
    // [maks. kejadian, jendela menit] per bucket
    public const LIMITS = [
        'login_id'      => [5, 15],   // login gagal per identitas (email/NRP/HP)
        'login_ip'      => [20, 15],  // login gagal per IP
        'report_ip'     => [5, 60],   // laporan publik per IP
        'pin_ticket_ip' => [5, 15],   // PIN salah per tiket dari IP yang sama
        'pin_ip'        => [20, 15],  // PIN salah per IP (lintas tiket, termasuk tiket tak dikenal)
        'pin_ticket'    => [50, 60],  // plafon PIN salah per tiket dari seluruh IP (anti brute-force terdistribusi)
        'chat_start_ip' => [5, 60],   // sesi Live Chat baru per IP
        'chat_msg_ip'   => [40, 5],   // pesan Live Chat warga per IP
        'lostfound_ip'  => [5, 60],   // laporan Hilang & Temu publik per IP
        'lostfound_claim_ip' => [5, 60], // klaim kepemilikan barang temuan per IP
        'lostfound_pin_ip'   => [10, 15], // kode/PIN cek status Hilang & Temu salah per IP
        'pin_desk_user'      => [30, 60], // lihat/reset PIN pelapor oleh satu petugas (cegah panen PIN)
    ];

    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function tooMany(string $bucket, string $key): bool
    {
        [$max, $minutes] = self::LIMITS[$bucket];
        $count = (int)($this->fetch(
            "SELECT COUNT(*) AS c FROM throttle_events
             WHERE bucket = ? AND throttle_key = ? AND created_at > NOW() - INTERVAL {$minutes} MINUTE",
            [$bucket, $key]
        )['c'] ?? 0);

        return $count >= $max;
    }

    public function hit(string $bucket, string $key): void
    {
        $this->execute("INSERT INTO throttle_events (bucket, throttle_key) VALUES (?, ?)", [$bucket, $key]);

        // Bersihkan catatan kedaluwarsa sesekali agar tabel tidak terus membesar
        if (random_int(1, 50) === 1) {
            $this->execute("DELETE FROM throttle_events WHERE created_at < NOW() - INTERVAL 1 DAY");
        }
    }

    public function clear(string $bucket, string $key): void
    {
        $this->execute("DELETE FROM throttle_events WHERE bucket = ? AND throttle_key = ?", [$bucket, $key]);
    }

    public static function windowMinutes(string $bucket): int
    {
        return self::LIMITS[$bucket][1];
    }
}
