-- Migrasi Tahap 4 Management Posko: rekapitulasi berjenjang, laporan nihil & penandaan tinjau ulang
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_15_posko_04_rekap.sql
-- Aman dijalankan ulang (IF NOT EXISTS / IF EXISTS).

-- Rekap disimpan sebagai laporan posko (is_recap = 1) agar memakai workflow, riwayat & versi yang sama.
ALTER TABLE `posko_reports`
    ADD COLUMN IF NOT EXISTS `is_recap` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Laporan rekapitulasi wilayah (nilai agregat otomatis)' AFTER `is_nil`,
    ADD COLUMN IF NOT EXISTS `needs_review` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Rekap perlu ditinjau ulang karena laporan sumbernya direvisi' AFTER `is_recap`,
    ADD COLUMN IF NOT EXISTS `recap_computed_at` DATETIME NULL COMMENT 'Waktu nilai agregat terakhir dihitung' AFTER `needs_review`;

-- Satu laporan sumber & satu rekap per unit, jenis & periode (indeks baru dibuat lebih dulu karena dipakai FK satker_id)
ALTER TABLE `posko_reports` ADD UNIQUE INDEX IF NOT EXISTS `uq_posko_report_period_recap` (`satker_id`, `report_type_id`, `period_key`, `is_recap`);
ALTER TABLE `posko_reports` DROP INDEX IF EXISTS `uq_posko_report_period_key`;

-- 24. SUMBER REKAPITULASI (jejak dari setiap angka rekap ke laporan sumber & versinya; BRD 4.4, 12.8)
CREATE TABLE IF NOT EXISTS `posko_recap_sources` (
    `recap_report_id` INT NOT NULL,
    `source_report_id` INT NOT NULL,
    `source_version` INT NOT NULL,
    PRIMARY KEY (`recap_report_id`, `source_report_id`),
    INDEX `idx_posko_recap_source` (`source_report_id`),
    CONSTRAINT `fk_posko_recap_report` FOREIGN KEY (`recap_report_id`) REFERENCES `posko_reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_posko_recap_source` FOREIGN KEY (`source_report_id`) REFERENCES `posko_reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
