-- Migrasi: kanal Live Chat warga & konsol Omnichat agen Command Center
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_15_add_livechat.sql

-- 13. PERCAKAPAN LIVE CHAT (satu baris per sesi chat warga)
CREATE TABLE IF NOT EXISTS `chat_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `channel` VARCHAR(20) NOT NULL DEFAULT 'LIVE_CHAT' COMMENT 'Kanal masuk (disiapkan untuk kanal lain di Omnichat)',
    `visitor_token` CHAR(64) NOT NULL COMMENT 'Token acak pemilik sesi chat (disimpan juga di sesi warga)',
    `visitor_name` VARCHAR(100) NOT NULL,
    `visitor_phone` VARCHAR(25) NULL,
    `status` ENUM('WAITING', 'ACTIVE', 'CLOSED') NOT NULL DEFAULT 'WAITING',
    `assigned_agent_id` INT NULL,
    `report_id` INT NULL COMMENT 'Tiket laporan hasil konversi chat',
    `closed_by` ENUM('VISITOR', 'AGENT') NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_message_at` DATETIME NULL,
    `closed_at` DATETIME NULL,
    INDEX `idx_chat_status` (`status`, `last_message_at`),
    CONSTRAINT `fk_chat_agent` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_chat_report` FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. PESAN LIVE CHAT
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `sender_type` ENUM('VISITOR', 'AGENT', 'SYSTEM') NOT NULL,
    `sender_id` INT NULL COMMENT 'User ID agen (NULL untuk warga/sistem)',
    `body` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_chat_messages_conv` (`conversation_id`, `id`),
    CONSTRAINT `fk_chat_messages_conv` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kanal laporan baru: tiket hasil konversi Live Chat
ALTER TABLE `reports` MODIFY `source_channel` ENUM(
    'WEB_PORTAL', 'WHATSAPP_BOT', 'TWITTER_X', 'INSTAGRAM', 'FACEBOOK', 'TIKTOK', 'CALL_CENTER_110', 'LIVE_CHAT'
) NOT NULL DEFAULT 'WEB_PORTAL';
