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
    `path` VARCHAR(255) NULL COMMENT 'Materialized path id leluhur, mis. /1/10/100/',
    `type` ENUM('MABES', 'POLDA', 'DIREKTORAT', 'POLRES', 'POLSEK') NOT NULL DEFAULT 'POLDA',
    `level` TINYINT NOT NULL DEFAULT 2 COMMENT '1 Polsek, 2 Polres, 3 Polda, 4 Pusat',
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(30) NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_satkers_path` (`path`),
    INDEX `idx_satkers_level` (`level`, `is_active`),
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
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Wajib ganti sandi saat login berikutnya',
    `role` ENUM(
        'ROLE_CITIZEN',
        'ROLE_SPKT_OPERATOR',
        'ROLE_CYBER_HUMAS',
        'ROLE_SATKER_HEAD',
        'ROLE_SATKER_ADMIN',
        'ROLE_INVESTIGATOR',
        'ROLE_INTERNAL_AFFAIRS',
        'ROLE_EXECUTIVE',
        'ROLE_SUPERADMIN',
        'ROLE_POSKO_ADMIN',
        'ROLE_POSKO_OPERATOR',
        'ROLE_POSKO_EXECUTIVE'
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

-- 16. JENIS LAPORAN POSKO (dapat dikonfigurasi; BRD 4.2)
-- Tenggat berperiode = tanggal akhir periode + deadline_offset_days, pukul deadline_time (WIB)
-- Tenggat tanpa periode (kejadian khusus/insidentil) = waktu kejadian + deadline_hours
CREATE TABLE IF NOT EXISTS `posko_report_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(40) NOT NULL UNIQUE,
    `name` VARCHAR(120) NOT NULL,
    `kind` ENUM('ROUTINE', 'PERIODIC', 'SPECIAL_EVENT', 'INCIDENTAL') NOT NULL,
    `period_unit` ENUM('DAY', 'WEEK', 'MONTH', 'NONE') NOT NULL DEFAULT 'NONE',
    `deadline_offset_days` SMALLINT NOT NULL DEFAULT 0,
    `deadline_time` TIME NULL,
    `deadline_hours` SMALLINT NULL,
    `requires_approval` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Wajib disetujui sebelum diteruskan (BRD 2.2)',
    `allows_nil` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Boleh dinyatakan nihil',
    `applies_to_levels` SET('1', '2', '3', '4') NOT NULL DEFAULT '1,2,3' COMMENT 'Tingkat yang wajib/boleh membuat laporan ini',
    `description` VARCHAR(255) NULL,
    `sort_order` SMALLINT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. DEFINISI ISIAN PER JENIS LAPORAN (data kuantitatif sesuai format; dasar rekapitulasi)
CREATE TABLE IF NOT EXISTS `posko_report_fields` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_type_id` INT NOT NULL,
    `field_key` VARCHAR(50) NOT NULL,
    `label` VARCHAR(120) NOT NULL,
    `type` ENUM('number', 'text', 'textarea', 'select', 'date') NOT NULL DEFAULT 'number',
    `required` TINYINT(1) NOT NULL DEFAULT 0,
    `options` TEXT NULL COMMENT 'Pilihan untuk tipe select, satu per baris',
    `aggregate` ENUM('SUM', 'AVG', 'MAX', 'NONE') NOT NULL DEFAULT 'NONE' COMMENT 'Cara agregasi pada rekapitulasi',
    `unit_label` VARCHAR(30) NULL COMMENT 'Satuan angka, mis. kejadian, personel, Rp',
    `sort_order` SMALLINT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY `uq_posko_field` (`report_type_id`, `field_key`),
    CONSTRAINT `fk_posko_field_type` FOREIGN KEY (`report_type_id`) REFERENCES `posko_report_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. LAPORAN POSKO (data umum BRD 4.2)
CREATE TABLE IF NOT EXISTS `posko_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_number` VARCHAR(80) NULL UNIQUE COMMENT 'PSK/{KODE-UNIT}/{YYYYMM}/{NNNN}, diberikan saat dikirim',
    `report_type_id` INT NOT NULL,
    `satker_id` INT NOT NULL COMMENT 'Unit pelapor',
    `holder_satker_id` INT NULL COMMENT 'Unit yang sedang memegang laporan (verifikasi/meneruskan)',
    `period_start` DATE NULL,
    `period_end` DATE NULL,
    `period_key` DATE NULL COMMENT 'Sama dengan period_start selama laporan tidak dibatalkan',
    `event_time` DATETIME NULL COMMENT 'Waktu kejadian (jenis tanpa periode)',
    `region` VARCHAR(255) NULL COMMENT 'Wilayah kejadian',
    `situation_summary` TEXT NULL,
    `description` TEXT NULL COMMENT 'Uraian kegiatan atau kejadian',
    `obstacles` TEXT NULL COMMENT 'Kendala dan permasalahan',
    `follow_up` TEXT NULL COMMENT 'Tindak lanjut',
    `assistance_needed` TEXT NULL COMMENT 'Kebutuhan bantuan atau eskalasi',
    `priority` ENUM('NORMAL', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'NORMAL',
    `reference_ticket` VARCHAR(50) NULL COMMENT 'Opsional: nomor tiket aduan SIGAP terkait',
    `status` ENUM('DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NEEDS_REVISION', 'APPROVED', 'FORWARDED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
    `is_nil` TINYINT(1) NOT NULL DEFAULT 0,
    `version` INT NOT NULL DEFAULT 1,
    `deadline_at` DATETIME NULL,
    `submitted_at` DATETIME NULL,
    `reviewed_by` INT NULL COMMENT 'Pemeriksa yang memulai verifikasi',
    `approved_by` INT NULL,
    `approved_at` DATETIME NULL,
    `forwarded_at` DATETIME NULL,
    `cancel_reason` VARCHAR(500) NULL,
    `created_by` INT NULL,
    `updated_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Satu laporan aktif per unit, jenis & periode (dibatalkan/tanpa periode: period_key NULL, tidak dibatasi)
    UNIQUE KEY `uq_posko_report_period_key` (`satker_id`, `report_type_id`, `period_key`),
    INDEX `idx_posko_reports_holder` (`holder_satker_id`, `status`),
    INDEX `idx_posko_reports_status` (`status`, `deadline_at`),
    INDEX `idx_posko_reports_type_period` (`report_type_id`, `period_start`),
    CONSTRAINT `fk_posko_report_type` FOREIGN KEY (`report_type_id`) REFERENCES `posko_report_types`(`id`),
    CONSTRAINT `fk_posko_report_satker` FOREIGN KEY (`satker_id`) REFERENCES `satkers`(`id`),
    CONSTRAINT `fk_posko_report_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_posko_report_updater` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. NILAI ISIAN LAPORAN (terstruktur agar rekap dapat dihitung dengan SQL)
CREATE TABLE IF NOT EXISTS `posko_report_values` (
    `report_id` INT NOT NULL,
    `field_key` VARCHAR(50) NOT NULL,
    `value_number` DECIMAL(18, 2) NULL,
    `value_text` TEXT NULL,
    PRIMARY KEY (`report_id`, `field_key`),
    CONSTRAINT `fk_posko_value_report` FOREIGN KEY (`report_id`) REFERENCES `posko_reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. LAMPIRAN LAPORAN POSKO (penyimpanan privat lewat App\Core\Upload)
CREATE TABLE IF NOT EXISTS `posko_report_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_name_original` VARCHAR(255) NULL,
    `mime` VARCHAR(80) NOT NULL,
    `size_bytes` INT NOT NULL,
    `uploaded_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_posko_attachment_report` (`report_id`),
    CONSTRAINT `fk_posko_attachment_report` FOREIGN KEY (`report_id`) REFERENCES `posko_reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_posko_attachment_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. SEQUENCE NOMOR LAPORAN POSKO PER UNIT & BULAN (penomoran atomik)
CREATE TABLE IF NOT EXISTS `posko_number_sequences` (
    `scope` VARCHAR(80) PRIMARY KEY COMMENT '{KODE-UNIT}/{YYYYMM}',
    `last_seq` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 22. RIWAYAT TINDAKAN LAPORAN POSKO (histori pemeriksaan BRD 4.3; tidak dapat diubah dari aplikasi)
CREATE TABLE IF NOT EXISTS `posko_report_history` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `actor_id` INT NULL,
    `actor_satker_id` INT NULL,
    `action` VARCHAR(30) NOT NULL COMMENT 'create, submit, start_review, approve, return, forward, acknowledge, revise, cancel',
    `status_from` VARCHAR(20) NULL,
    `status_to` VARCHAR(20) NULL,
    `holder_from` INT NULL,
    `holder_to` INT NULL,
    `version` INT NOT NULL DEFAULT 1,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_posko_history_report` (`report_id`, `id`),
    CONSTRAINT `fk_posko_history_report` FOREIGN KEY (`report_id`) REFERENCES `posko_reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 23. VERSI LAPORAN POSKO (snapshot isi setiap kali dikirim; BRD 5.3)
CREATE TABLE IF NOT EXISTS `posko_report_versions` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `version` INT NOT NULL,
    `snapshot_json` LONGTEXT NOT NULL,
    `reason` VARCHAR(500) NULL COMMENT 'Alasan revisi (versi > 1)',
    `changed_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_posko_version` (`report_id`, `version`),
    CONSTRAINT `fk_posko_version_report` FOREIGN KEY (`report_id`) REFERENCES `posko_reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
