-- Migrasi Tahap 2 Management Posko: jenis laporan terkonfigurasi, isian dinamis, laporan posko & lampiran
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_15_posko_02_laporan.sql
-- Aman dijalankan ulang (IF NOT EXISTS).

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
    `period_start` DATE NULL,
    `period_end` DATE NULL,
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
    `created_by` INT NULL,
    `updated_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Satu laporan per unit, jenis & periode (jenis tanpa periode: period_start NULL, tidak dibatasi)
    UNIQUE KEY `uq_posko_report_period` (`satker_id`, `report_type_id`, `period_start`),
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
