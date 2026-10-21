-- Tiket Disposisi ke Satker Fungsi (Satlantas, Reskrim, Jatanras, Krimsus, Siber, Narkoba, Propam, Samapta)
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_21_satker_fungsi_ticketing.sql
-- Aman dijalankan ulang (idempoten). Satuan fungsi dibentuk dari data Polda & Polres yang sudah ada.

-- 1. KODE FUNGSI PADA SATKER & KATEGORI LAPORAN
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'satkers' AND COLUMN_NAME = 'function_code');
SET @sql := IF(@col = 0,
    "ALTER TABLE `satkers` ADD COLUMN `function_code` VARCHAR(20) NULL COMMENT 'Fungsi satuan: RESKRIMUM, JATANRAS, RESKRIMSUS, NARKOBA, SIBER, LANTAS, PROPAM, SAMAPTA' AFTER `level`, ADD INDEX `idx_satkers_function` (`function_code`)",
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'report_categories' AND COLUMN_NAME = 'function_code');
SET @sql := IF(@col = 0,
    "ALTER TABLE `report_categories` ADD COLUMN `function_code` VARCHAR(20) NULL COMMENT 'Fungsi penanganan default untuk rekomendasi disposisi' AFTER `default_satker_type`",
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. KATEGORI → FUNGSI (+ kategori Jatanras & Krimsus)
UPDATE `report_categories` SET `function_code` = 'RESKRIMUM'  WHERE `code` = 'CAT-KRIMINAL-UMUM';
UPDATE `report_categories` SET `function_code` = 'SIBER'      WHERE `code` = 'CAT-SIBER-KHUSUS';
UPDATE `report_categories` SET `function_code` = 'NARKOBA'    WHERE `code` = 'CAT-NARKOBA';
UPDATE `report_categories` SET `function_code` = 'LANTAS'     WHERE `code` = 'CAT-LANTAS';
UPDATE `report_categories` SET `function_code` = 'PROPAM'     WHERE `code` = 'CAT-PROPAM';
UPDATE `report_categories` SET `function_code` = 'SAMAPTA'    WHERE `code` = 'CAT-KAMTIBMAS';

INSERT INTO `report_categories` (`code`, `name`, `default_satker_type`, `function_code`, `sla_hours`, `icon`, `description`) VALUES
('CAT-JATANRAS', 'Kejahatan dengan Kekerasan (Begal, Perampokan, Curas)', 'POLRES', 'JATANRAS', 24, 'swords', 'Pencurian dengan kekerasan, pembegalan, perampokan, penganiayaan berat'),
('CAT-KRIMSUS', 'Kriminal Khusus (Korupsi, Perbankan, Investasi, Lingkungan)', 'POLDA', 'RESKRIMSUS', 72, 'landmark', 'Tipikor, kejahatan perbankan & investasi bodong, illegal logging/mining, pemalsuan')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `function_code` = VALUES(`function_code`);

-- 3. DIREKTORAT MABES
UPDATE `satkers` SET `function_code` = 'RESKRIMUM' WHERE `code` = 'DITTIPIDUM';
UPDATE `satkers` SET `function_code` = 'SIBER'     WHERE `code` = 'DITTIPIDSIBER';
UPDATE `satkers` SET `function_code` = 'NARKOBA'   WHERE `code` = 'DITTIPIDNARKOBA';
UPDATE `satkers` SET `function_code` = 'LANTAS'    WHERE `code` = 'KORLANTAS';
UPDATE `satkers` SET `function_code` = 'PROPAM'    WHERE `code` = 'DIVPROPAM';

-- 4. SATUAN FUNGSI POLDA (tipe DIREKTORAT: tidak ikut kewajiban lapor Posko)
INSERT IGNORE INTO `satkers` (`parent_id`, `type`, `level`, `function_code`, `code`, `name`, `address`, `phone`)
SELECT p.id, 'DIREKTORAT', 4, f.fn, CONCAT(p.code, '-', f.suffix), CONCAT(f.label, ' ', p.name), p.address, p.phone
FROM `satkers` p
JOIN (
    SELECT 'RESKRIMUM' AS fn, 'DITRESKRIMUM' AS suffix, 'Ditreskrimum' AS label UNION ALL
    SELECT 'RESKRIMSUS', 'DITRESKRIMSUS', 'Ditreskrimsus' UNION ALL
    SELECT 'NARKOBA', 'DITRESNARKOBA', 'Ditresnarkoba' UNION ALL
    SELECT 'SIBER', 'DITRESSIBER', 'Ditressiber' UNION ALL
    SELECT 'LANTAS', 'DITLANTAS', 'Ditlantas' UNION ALL
    SELECT 'SAMAPTA', 'DITSAMAPTA', 'Ditsamapta' UNION ALL
    SELECT 'PROPAM', 'BIDPROPAM', 'Bidpropam'
) f
WHERE p.type = 'POLDA';

-- Subdit III Jatanras di bawah Ditreskrimum tiap Polda
INSERT IGNORE INTO `satkers` (`parent_id`, `type`, `level`, `function_code`, `code`, `name`, `address`, `phone`)
SELECT d.id, 'DIREKTORAT', 4, 'JATANRAS', CONCAT(p.code, '-JATANRAS'), CONCAT('Subdit Jatanras Ditreskrimum ', p.name), p.address, p.phone
FROM `satkers` d JOIN `satkers` p ON p.id = d.parent_id
WHERE d.code = CONCAT(p.code, '-DITRESKRIMUM') AND p.type = 'POLDA';

-- 5. SATUAN FUNGSI POLRES
INSERT IGNORE INTO `satkers` (`parent_id`, `type`, `level`, `function_code`, `code`, `name`, `address`, `phone`)
SELECT p.id, 'DIREKTORAT', 4, f.fn, CONCAT(p.code, '-', f.suffix), CONCAT(f.label, ' ', p.name), p.address, p.phone
FROM `satkers` p
JOIN (
    SELECT 'RESKRIMUM' AS fn, 'SATRESKRIM' AS suffix, 'Satreskrim' AS label UNION ALL
    SELECT 'NARKOBA', 'SATRESNARKOBA', 'Satresnarkoba' UNION ALL
    SELECT 'LANTAS', 'SATLANTAS', 'Satlantas' UNION ALL
    SELECT 'SAMAPTA', 'SATSAMAPTA', 'Satsamapta' UNION ALL
    SELECT 'PROPAM', 'SIPROPAM', 'Sipropam'
) f
WHERE p.type = 'POLRES';

-- Materialized path untuk satuan baru (hingga 2 tingkat di bawah induk teritorial)
UPDATE `satkers` c JOIN `satkers` p ON p.id = c.parent_id
SET c.path = CONCAT(p.path, c.id, '/') WHERE c.path IS NULL AND p.path IS NOT NULL;
UPDATE `satkers` c JOIN `satkers` p ON p.id = c.parent_id
SET c.path = CONCAT(p.path, c.id, '/') WHERE c.path IS NULL AND p.path IS NOT NULL;

-- 6. RIWAYAT TIKET DISPOSISI (SPKT → Satker → jajaran)
CREATE TABLE IF NOT EXISTS `report_dispositions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `from_satker_id` INT NULL COMMENT 'NULL = dari SPKT / Command Center',
    `to_satker_id` INT NOT NULL,
    `disposed_by` INT NULL,
    `notes` TEXT NULL,
    `status` ENUM('PENDING', 'ACCEPTED', 'RETURNED', 'FORWARDED', 'REVOKED') NOT NULL DEFAULT 'PENDING',
    `ack_deadline` DATETIME NOT NULL COMMENT 'Batas waktu satker menerima tiket',
    `responded_by` INT NULL,
    `responded_at` DATETIME NULL,
    `response_notes` TEXT NULL,
    `escalated_at` DATETIME NULL COMMENT 'Diisi watchdog saat batas terima terlewati',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_disp_report` (`report_id`, `id`),
    INDEX `idx_disp_inbox` (`to_satker_id`, `status`),
    INDEX `idx_disp_overdue` (`status`, `ack_deadline`),
    FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_satker_id`) REFERENCES `satkers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`to_satker_id`) REFERENCES `satkers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`disposed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`responded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Laporan yang sudah berada di satker sebelum migrasi: catat sebagai tiket yang sudah diterima
INSERT INTO `report_dispositions` (`report_id`, `to_satker_id`, `status`, `ack_deadline`, `responded_at`, `notes`)
SELECT r.id, r.current_satker_id, 'ACCEPTED', r.created_at, r.created_at, 'Data awal sebelum modul tiket disposisi'
FROM `reports` r
WHERE r.current_satker_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `report_dispositions` d WHERE d.report_id = r.id);
