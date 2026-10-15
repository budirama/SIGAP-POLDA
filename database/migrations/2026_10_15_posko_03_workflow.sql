-- Migrasi Tahap 3 Management Posko: workflow berjenjang, riwayat tindakan, versi laporan & eskalasi
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_15_posko_03_workflow.sql
-- Aman dijalankan ulang (IF NOT EXISTS / IF EXISTS).

-- Unit yang saat ini memegang laporan (menunggu tindakannya) & jejak persetujuan
ALTER TABLE `posko_reports`
    ADD COLUMN IF NOT EXISTS `holder_satker_id` INT NULL COMMENT 'Unit yang sedang memegang laporan (verifikasi/meneruskan)' AFTER `satker_id`,
    ADD COLUMN IF NOT EXISTS `reviewed_by` INT NULL COMMENT 'Pemeriksa yang memulai verifikasi' AFTER `submitted_at`,
    ADD COLUMN IF NOT EXISTS `approved_by` INT NULL AFTER `reviewed_by`,
    ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`,
    ADD COLUMN IF NOT EXISTS `forwarded_at` DATETIME NULL AFTER `approved_at`,
    ADD COLUMN IF NOT EXISTS `cancel_reason` VARCHAR(500) NULL AFTER `forwarded_at`,
    -- Kunci periode terpisah agar laporan yang dibatalkan melepas periodenya (BRD 3.2: batalkan, bukan hapus)
    ADD COLUMN IF NOT EXISTS `period_key` DATE NULL COMMENT 'Sama dengan period_start selama laporan tidak dibatalkan' AFTER `period_end`,
    ADD INDEX IF NOT EXISTS `idx_posko_reports_holder` (`holder_satker_id`, `status`);

UPDATE `posko_reports` SET `period_key` = `period_start` WHERE `status` <> 'CANCELLED';

-- Indeks baru dibuat lebih dulu: indeks lama juga dipakai foreign key satker_id
ALTER TABLE `posko_reports` ADD UNIQUE INDEX IF NOT EXISTS `uq_posko_report_period_key` (`satker_id`, `report_type_id`, `period_key`);
ALTER TABLE `posko_reports` DROP INDEX IF EXISTS `uq_posko_report_period`;

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
