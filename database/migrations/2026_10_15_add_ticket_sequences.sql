-- Migrasi: sequence nomor tiket atomik per periode
-- Jalankan pada database yang sudah ada: mysql -uroot sigap_polda < database/migrations/2026_10_15_add_ticket_sequences.sql
-- (Counter diinisialisasi otomatis dari nomor tiket tertinggi saat laporan pertama per periode dibuat)

-- 12. TABEL SEQUENCE NOMOR TIKET PER PERIODE (PENOMORAN ATOMIK ANTI-BENTROK)
CREATE TABLE IF NOT EXISTS `ticket_sequences` (
    `period` CHAR(6) PRIMARY KEY COMMENT 'Format YYYYMM',
    `last_seq` INT NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
