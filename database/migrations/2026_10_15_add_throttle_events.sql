-- Migrasi: tabel throttle generik (login, laporan publik, PIN) menggantikan pin_attempts
-- Jalankan pada database yang sudah ada: mysql -uroot sigap_polda < database/migrations/2026_10_15_add_throttle_events.sql

-- 11. TABEL THROTTLE (PEMBATASAN PERCOBAAN: LOGIN, LAPORAN PUBLIK, PIN PELACAKAN)
CREATE TABLE IF NOT EXISTS `throttle_events` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `bucket` VARCHAR(30) NOT NULL COMMENT 'login_id, login_ip, report_ip, pin_ticket_ip, pin_ip, pin_ticket',
    `throttle_key` VARCHAR(191) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_throttle_lookup` (`bucket`, `throttle_key`, `created_at`),
    INDEX `idx_throttle_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `pin_attempts`;
