-- Migrasi: tabel jejak audit
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_15_add_audit_logs.sql

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
