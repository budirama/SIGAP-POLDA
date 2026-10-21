-- Daftarkan modul Hilang & Temu pada Manajemen Modul (butuh tabel system_modules dari 2026_10_19_add_system_modules.sql)
-- Tanpa baris ini modul tetap aktif (kunci yang tidak terdaftar dianggap aktif).

INSERT INTO `system_modules`
(`module_key`, `parent_key`, `name`, `description`, `category`, `route_prefix`, `icon`, `is_active`, `is_core`, `sort_order`)
VALUES
('op_lostfound', 'OPERATIONAL', 'Hilang & Temu', 'Laporan kehilangan & temuan kendaraan, orang, barang berharga, dokumen, hewan dengan pencocokan otomatis dan klaim pemilik', 'OPERATIONAL', 'hilangtemu', 'fas fa-magnifying-glass-location', 1, 0, 36)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `route_prefix` = VALUES(`route_prefix`),
  `icon` = VALUES(`icon`),
  `sort_order` = VALUES(`sort_order`);
