<?php
/**
 * SIGAP-POLDA: PinAttempt Model (Rate Limit Brute-Force PIN Pelacakan Tiket)
 */

namespace App\Models;

use App\Core\Model;

class PinAttempt extends Model
{
    // Maks. percobaan gagal per tiket (dari IP mana pun) dalam jendela waktu
    public const MAX_PER_TICKET = 5;
    // Maks. percobaan gagal per alamat IP (lintas tiket) dalam jendela waktu
    public const MAX_PER_IP = 20;
    public const WINDOW_MINUTES = 15;

    public function isLocked(string $ticketNumber, string $ip): bool
    {
        $row = $this->fetch(
            "SELECT
                SUM(ticket_number = ?) AS ticket_failures,
                SUM(ip_address = ?) AS ip_failures
             FROM pin_attempts
             WHERE (ticket_number = ? OR ip_address = ?)
               AND attempted_at > NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE",
            [$ticketNumber, $ip, $ticketNumber, $ip]
        );

        return (int)($row['ticket_failures'] ?? 0) >= self::MAX_PER_TICKET
            || (int)($row['ip_failures'] ?? 0) >= self::MAX_PER_IP;
    }

    public function recordFailure(string $ticketNumber, string $ip): void
    {
        $this->execute(
            "INSERT INTO pin_attempts (ticket_number, ip_address) VALUES (?, ?)",
            [$ticketNumber, $ip]
        );

        // Bersihkan catatan kedaluwarsa agar tabel tidak terus membesar
        $this->execute("DELETE FROM pin_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY");
    }

    public function clearFailures(string $ticketNumber): void
    {
        $this->execute("DELETE FROM pin_attempts WHERE ticket_number = ?", [$ticketNumber]);
    }
}
