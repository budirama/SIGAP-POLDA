-- Management Posko Tahap 5: notifikasi dalam aplikasi & antrean email (BRD 4.6)
-- Aman dijalankan ulang.

-- 25. NOTIFIKASI DALAM APLIKASI (satu baris per penerima; dedupe_key mencegah pengingat ganda)
CREATE TABLE IF NOT EXISTS `posko_notifications` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(40) NOT NULL COMMENT 'report_submitted, report_returned, report_approved, report_forwarded, high_priority, deadline_soon, deadline_late',
    `title` VARCHAR(150) NOT NULL,
    `body` VARCHAR(500) NULL,
    `link` VARCHAR(255) NULL COMMENT 'Path relatif aplikasi, mis. /posko-laporan/show/12',
    `dedupe_key` VARCHAR(120) NULL,
    `read_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_posko_notif_dedupe` (`user_id`, `dedupe_key`),
    INDEX `idx_posko_notif_user` (`user_id`, `read_at`, `id`),
    CONSTRAINT `fk_posko_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 26. ANTREAN EMAIL (dikirim oleh scripts/posko_mailer.php agar request pengguna tidak menunggu SMTP)
CREATE TABLE IF NOT EXISTS `posko_notification_outbox` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `to_email` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `body` TEXT NOT NULL,
    `dedupe_key` VARCHAR(120) NULL,
    `status` ENUM('PENDING', 'SENT', 'FAILED') NOT NULL DEFAULT 'PENDING',
    `attempts` TINYINT NOT NULL DEFAULT 0,
    `last_error` VARCHAR(255) NULL,
    `next_attempt_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `sent_at` DATETIME NULL,
    UNIQUE KEY `uq_posko_outbox_dedupe` (`to_email`, `dedupe_key`),
    INDEX `idx_posko_outbox_due` (`status`, `next_attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indeks tambahan untuk kueri dashboard (status & waktu kirim per unit)
ALTER TABLE `posko_reports` ADD INDEX IF NOT EXISTS `idx_posko_report_submitted` (`satker_id`, `submitted_at`);
ALTER TABLE `posko_reports` ADD INDEX IF NOT EXISTS `idx_posko_report_status` (`status`, `priority`);
