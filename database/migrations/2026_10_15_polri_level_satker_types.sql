-- Migrasi: SIGAP tingkat Polri — tipe satker MABES & Polda jajaran sebagai tujuan disposisi
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_15_polri_level_satker_types.sql
-- Data demo tingkat Polri: jalankan ulang database/seeders.sql setelah migrasi ini.

ALTER TABLE `satkers` MODIFY `type` ENUM('MABES', 'POLDA', 'DIREKTORAT', 'POLRES', 'POLSEK') NOT NULL DEFAULT 'POLDA';
ALTER TABLE `report_categories` MODIFY `default_satker_type` ENUM('DIREKTORAT', 'POLDA', 'POLRES') NOT NULL DEFAULT 'DIREKTORAT';
