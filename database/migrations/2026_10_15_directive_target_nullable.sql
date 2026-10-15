-- Migrasi: target Satker instruksi Atensi Kapolda boleh kosong bila laporan belum didisposisikan
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_15_directive_target_nullable.sql

ALTER TABLE `kapolda_directives` MODIFY `target_satker_id` INT NULL COMMENT 'NULL bila laporan belum didisposisikan; diperbarui saat disposisi';
