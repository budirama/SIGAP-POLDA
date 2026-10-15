-- Migrasi: aktor timeline boleh NULL (dicatat sistem) agar laporan publik tidak tercatat atas nama user #1
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_15_timeline_actor_nullable.sql

ALTER TABLE `report_timeline` MODIFY `actor_id` INT NULL COMMENT 'NULL = dicatat sistem (laporan publik)';
