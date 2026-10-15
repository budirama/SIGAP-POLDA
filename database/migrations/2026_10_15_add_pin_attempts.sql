-- Migrasi: tabel rate limit percobaan PIN pelacakan tiket
-- Jalankan pada database yang sudah ada: mysql -uroot sigap_polda < database/migrations/2026_10_15_add_pin_attempts.sql

-- 11. TABEL PERCOBAAN PIN GAGAL (RATE LIMIT BRUTE-FORCE PELACAKAN TIKET)
CREATE TABLE IF NOT EXISTS `pin_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pin_ticket` (`ticket_number`, `attempted_at`),
    INDEX `idx_pin_ip` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
