-- =============================================================================
-- DATABASE SCHEMA: SIGAP-POLRI (Sistem Informasi & Pengaduan Terpadu Polri)
-- Catatan: nama database/tabel/kolom bertanda 'polda'/'kapolda' dipertahankan demi kompatibilitas data.
-- Standard: MySQL 5.7+ / MariaDB 10.4+ InnoDB Engine
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `sigap_polda` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sigap_polda`;

-- 1. TABEL SATUAN KERJA (MABES POLRI, SATKER MABES & POLDA JAJARAN)
CREATE TABLE IF NOT EXISTS `satkers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NULL,
    `type` ENUM('MABES', 'POLDA', 'DIREKTORAT', 'POLRES', 'POLSEK') NOT NULL DEFAULT 'POLDA',
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(30) NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_satkers_parent` FOREIGN KEY (`parent_id`) REFERENCES `satkers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL PENGGUNA (USERS & ROLES KEPOLISIAN + WARGA)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nrp` VARCHAR(50) NULL UNIQUE COMMENT 'Nomor Registrasi Pokok Anggota Polri (kosong jika warga)',
    `nik` VARCHAR(16) NULL UNIQUE COMMENT 'NIK KTP Pelapor (kosong jika personil dinas)',
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(100) NULL,
    `phone` VARCHAR(25) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM(
        'ROLE_CITIZEN',
        'ROLE_SPKT_OPERATOR',
        'ROLE_CYBER_HUMAS',
        'ROLE_SATKER_HEAD',
        'ROLE_SATKER_ADMIN',
        'ROLE_INVESTIGATOR',
        'ROLE_INTERNAL_AFFAIRS',
        'ROLE_EXECUTIVE',
        'ROLE_SUPERADMIN'
    ) NOT NULL DEFAULT 'ROLE_CITIZEN',
    `satker_id` INT NULL,
    `rank` VARCHAR(50) NULL COMMENT 'Pangkat Personil: Bripka, Iptu, Kombes, Irjen, dll',
    `position` VARCHAR(100) NULL COMMENT 'Jabatan struktural',
    `avatar_url` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_satker` FOREIGN KEY (`satker_id`) REFERENCES `satkers`(`id`) ON DELETE SET NULL,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_nik` (`nik`),
    INDEX `idx_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABEL SOCIAL MEDIA INGESTION FEEDS (OMNICHANNEL DARI X, IG, FB, TIKTOK)
CREATE TABLE IF NOT EXISTS `social_feeds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `platform` ENUM('TWITTER_X', 'INSTAGRAM', 'FACEBOOK', 'TIKTOK') NOT NULL,
    `post_id_str` VARCHAR(100) NOT NULL COMMENT 'ID postingan unik dari medsos',
    `author_username` VARCHAR(100) NOT NULL,
    `author_name` VARCHAR(150) NULL,
    `author_avatar` VARCHAR(255) NULL,
    `post_content` TEXT NOT NULL,
    `post_url` VARCHAR(255) NOT NULL,
    `media_urls` TEXT NULL COMMENT 'JSON array link foto/video lampiran',
    `sentiment` ENUM('NEGATIVE_URGENT', 'NEUTRAL', 'POSITIVE', 'SPAM') NOT NULL DEFAULT 'NEUTRAL',
    `status` ENUM('NEW', 'CONVERTED_TO_TICKET', 'DISMISSED_SPAM') NOT NULL DEFAULT 'NEW',
    `converted_report_id` INT NULL,
    `handled_by_user_id` INT NULL,
    `handled_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_social_platform_status` (`platform`, `status`),
    CONSTRAINT `fk_social_handler` FOREIGN KEY (`handled_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABEL KATEGORI PERKARA / TINDAK PIDANA
CREATE TABLE IF NOT EXISTS `report_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `default_satker_type` ENUM('DIREKTORAT', 'POLDA', 'POLRES') NOT NULL DEFAULT 'DIREKTORAT',
    `sla_hours` INT NOT NULL DEFAULT 72 COMMENT 'Batas jam tanggap awal standar',
    `icon` VARCHAR(50) NULL,
    `description` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. TABEL UTAMA LAPORAN PENGADUAN (REPORTS)
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Format: LP-POLRI-YYYYMM-XXXX',
    `security_pin` VARCHAR(6) NOT NULL COMMENT 'PIN 6-digit untuk pelacakan tiket publik',
    `source_channel` ENUM(
        'WEB_PORTAL',
        'WHATSAPP_BOT',
        'TWITTER_X',
        'INSTAGRAM',
        'FACEBOOK',
        'TIKTOK',
        'CALL_CENTER_110',
        'LIVE_CHAT'
    ) NOT NULL DEFAULT 'WEB_PORTAL',
    `social_feed_id` INT NULL,
    `social_post_url` VARCHAR(255) NULL,
    `reporter_id` INT NULL,
    `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Rahasiakan identitas dari publik/terlapor',
    `reporter_name_raw` VARCHAR(150) NOT NULL,
    `reporter_nik_raw` VARCHAR(16) NULL,
    `reporter_phone` VARCHAR(25) NOT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `incident_date` DATE NOT NULL,
    `incident_time` TIME NULL,
    `incident_location` TEXT NOT NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `current_satker_id` INT NULL,
    `assigned_investigator_id` INT NULL,
    `status` ENUM(
        'DRAFT',
        'SUBMITTED',
        'VERIFIED_SPKT',
        'DISPATCHED',
        'ASSIGNED',
        'INVESTIGATING',
        'CLOSED',
        'REJECTED'
    ) NOT NULL DEFAULT 'SUBMITTED',
    `urgency_level` ENUM('NORMAL', 'HIGH', 'EMERGENCY') NOT NULL DEFAULT 'NORMAL',
    `is_kapolda_attention` TINYINT(1) NOT NULL DEFAULT 0,
    `kapolda_attention_at` DATETIME NULL,
    `rejection_reason` TEXT NULL,
    `sla_deadline` DATETIME NULL,
    `closed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_reports_category` FOREIGN KEY (`category_id`) REFERENCES `report_categories`(`id`),
    CONSTRAINT `fk_reports_satker` FOREIGN KEY (`current_satker_id`) REFERENCES `satkers`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_reports_investigator` FOREIGN KEY (`assigned_investigator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_reports_social` FOREIGN KEY (`social_feed_id`) REFERENCES `social_feeds`(`id`) ON DELETE SET NULL,
    INDEX `idx_reports_ticket` (`ticket_number`),
    INDEX `idx_reports_status` (`status`),
    INDEX `idx_reports_kapolda` (`is_kapolda_attention`),
    INDEX `idx_reports_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hubungkan balik social_feeds ke reports jika sudah ada
ALTER TABLE `social_feeds` 
ADD CONSTRAINT `fk_social_report` FOREIGN KEY (`converted_report_id`) REFERENCES `reports`(`id`) ON DELETE SET NULL;

-- 6. TABEL ATTACHMENTS (BUKTI & DOKUMEN BAP)
CREATE TABLE IF NOT EXISTS `report_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `uploader_id` INT NULL,
    `uploader_type` ENUM('REPORTER', 'OFFICER') NOT NULL DEFAULT 'REPORTER',
    `file_type` ENUM('IMAGE', 'VIDEO', 'DOCUMENT_PDF') NOT NULL DEFAULT 'IMAGE',
    `file_path` VARCHAR(255) NOT NULL,
    `file_name_original` VARCHAR(255) NOT NULL,
    `file_size_kb` INT NULL,
    `caption` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_attach_report` FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attach_uploader` FOREIGN KEY (`uploader_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. TABEL TIMELINE & SP2HP-E (HISTORI STATUS & UPDATE BERKALA)
CREATE TABLE IF NOT EXISTS `report_timeline` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `actor_id` INT NULL COMMENT 'NULL = dicatat sistem (laporan publik)',
    `status_from` VARCHAR(50) NULL,
    `status_to` VARCHAR(50) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `public_notes` TEXT NULL COMMENT 'Pesan perkembangan yang dikirimkan ke Pelapor (SP2HP)',
    `internal_notes` TEXT NULL COMMENT 'Catatan rahasia intelijen/penyelidikan kepolisian',
    `is_sp2hp` TINYINT(1) NOT NULL DEFAULT 0,
    `sp2hp_number` VARCHAR(100) NULL COMMENT 'Nomor Dokumen SP2HP resmi',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_timeline_report` FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_timeline_actor` FOREIGN KEY (`actor_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. TABEL DIRECTIVE ATENSI KAPOLRI (ATENSI PIMPINAN)
CREATE TABLE IF NOT EXISTS `kapolda_directives` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `created_by` INT NOT NULL COMMENT 'User ID Kapolri / Wakapolri',
    `directive_text` TEXT NOT NULL COMMENT 'Instruksi langsung tertulis dari Kapolri',
    `target_satker_id` INT NULL COMMENT 'NULL bila laporan belum didisposisikan; diperbarui saat disposisi',
    `response_notes` TEXT NULL COMMENT 'Laporan tanggapan dari Kasatker',
    `status` ENUM('PENDING', 'ACKNOWLEDGED', 'RESOLVED') NOT NULL DEFAULT 'PENDING',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `responded_at` DATETIME NULL,
    CONSTRAINT `fk_directive_report` FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_directive_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
    CONSTRAINT `fk_directive_satker` FOREIGN KEY (`target_satker_id`) REFERENCES `satkers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. TABEL SURVEI KEPUASAN MASYARAKAT (SKM)
CREATE TABLE IF NOT EXISTS `report_feedbacks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL UNIQUE,
    `rating_stars` TINYINT(1) NOT NULL COMMENT 'Rating 1 - 5 Bintang',
    `speed_score` TINYINT(1) NOT NULL COMMENT 'Skor Kecepatan (1-5)',
    `fairness_score` TINYINT(1) NOT NULL COMMENT 'Skor Keadilan/Transparansi (1-5)',
    `comments` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_feedback_report` FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. TABEL CASE MESSAGES (RUANG KOMUNIKASI & KLARIFIKASI DUA ARAH POLISI & MASYARAKAT)
CREATE TABLE IF NOT EXISTS `case_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `sender_type` ENUM('CITIZEN', 'POLICE') NOT NULL,
    `sender_id` INT NULL,
    `sender_name` VARCHAR(150) NOT NULL,
    `message_text` TEXT NOT NULL,
    `attachment_path` VARCHAR(255) NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_msg_report` FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_msg_report` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. TABEL THROTTLE (PEMBATASAN PERCOBAAN: LOGIN, LAPORAN PUBLIK, PIN PELACAKAN)
CREATE TABLE IF NOT EXISTS `throttle_events` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `bucket` VARCHAR(30) NOT NULL COMMENT 'login_id, login_ip, report_ip, pin_ticket_ip, pin_ip, pin_ticket',
    `throttle_key` VARCHAR(191) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_throttle_lookup` (`bucket`, `throttle_key`, `created_at`),
    INDEX `idx_throttle_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. TABEL SEQUENCE NOMOR TIKET PER PERIODE (PENOMORAN ATOMIK ANTI-BENTROK)
CREATE TABLE IF NOT EXISTS `ticket_sequences` (
    `period` CHAR(6) PRIMARY KEY COMMENT 'Format YYYYMM',
    `last_seq` INT NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. TABEL JEJAK AUDIT (LOGIN, AKSES BERKAS PERKARA, UNDUHAN FILE, TINDAKAN PERSONIL)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL COMMENT 'NULL untuk aksi publik / belum login',
    `user_role` VARCHAR(40) NULL COMMENT 'Role saat aksi dilakukan',
    `action` VARCHAR(50) NOT NULL COMMENT 'auth.login, case.view, file.download, report.dispatch, ...',
    `report_id` INT NULL,
    `detail` VARCHAR(500) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`user_id`, `created_at`),
    INDEX `idx_audit_report` (`report_id`, `created_at`),
    INDEX `idx_audit_action` (`action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. TABEL PERCAKAPAN LIVE CHAT (satu baris per sesi chat warga)
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

-- 15. TABEL PESAN LIVE CHAT
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
