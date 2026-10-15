-- Migrasi Tahap 1 Management Posko: hierarki organisasi 4 tingkat & role posko
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_15_posko_01_organisasi_role.sql
-- Aman dijalankan ulang (IF NOT EXISTS). Setelah ini jalankan ulang database/seeders.sql untuk data hierarki demo.

-- Tingkat posko: 1 Polsek, 2 Polres, 3 Polda, 4 Pusat (Mabes & satker Mabes)
-- path: materialized path id leluhur, mis. /1/10/100/1000/, untuk query "seluruh unit di bawah X"
ALTER TABLE `satkers`
    ADD COLUMN IF NOT EXISTS `level` TINYINT NOT NULL DEFAULT 2 COMMENT '1 Polsek, 2 Polres, 3 Polda, 4 Pusat' AFTER `type`,
    ADD COLUMN IF NOT EXISTS `path` VARCHAR(255) NULL COMMENT 'Materialized path id leluhur, mis. /1/10/100/' AFTER `parent_id`,
    ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `longitude`,
    ADD INDEX IF NOT EXISTS `idx_satkers_path` (`path`),
    ADD INDEX IF NOT EXISTS `idx_satkers_level` (`level`, `is_active`);

UPDATE `satkers` SET `level` = CASE `type`
    WHEN 'POLSEK' THEN 1
    WHEN 'POLRES' THEN 2
    WHEN 'POLDA' THEN 3
    ELSE 4
END;

-- Bangun ulang path dari akar (kedalaman maksimum 4 tingkat)
UPDATE `satkers` SET `path` = CONCAT('/', `id`, '/') WHERE `parent_id` IS NULL;
UPDATE `satkers` c JOIN `satkers` p ON p.`id` = c.`parent_id` SET c.`path` = CONCAT(p.`path`, c.`id`, '/') WHERE p.`path` IS NOT NULL;
UPDATE `satkers` c JOIN `satkers` p ON p.`id` = c.`parent_id` SET c.`path` = CONCAT(p.`path`, c.`id`, '/') WHERE p.`path` IS NOT NULL;
UPDATE `satkers` c JOIN `satkers` p ON p.`id` = c.`parent_id` SET c.`path` = CONCAT(p.`path`, c.`id`, '/') WHERE p.`path` IS NOT NULL;

-- Role posko (akun terpisah dari role SIGAP; tingkat diambil dari satker pengguna)
ALTER TABLE `users` MODIFY `role` ENUM(
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
) NOT NULL DEFAULT 'ROLE_CITIZEN';

-- Wajib ganti kata sandi pada login berikutnya (setelah dibuat/di-reset oleh admin posko)
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password_hash`;
