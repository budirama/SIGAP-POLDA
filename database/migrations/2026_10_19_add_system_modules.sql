-- Migrasi Tabel Pengaturan Modul & Menu Superadmin / IT
-- SIGAP-POLDA: Modul Management & Dynamic Feature Flagging

CREATE TABLE IF NOT EXISTS `system_modules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `module_key` VARCHAR(64) NOT NULL UNIQUE,
  `parent_key` VARCHAR(64) NULL,
  `name` VARCHAR(128) NOT NULL,
  `description` VARCHAR(255) NULL,
  `category` VARCHAR(64) NOT NULL DEFAULT 'OPERATIONAL',
  `route_prefix` VARCHAR(128) NULL,
  `icon` VARCHAR(64) NOT NULL DEFAULT 'fas fa-cube',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_core` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT NULL,
  INDEX `idx_parent_key` (`parent_key`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data Modul Terdaftar (Predefined)
INSERT INTO `system_modules` 
(`module_key`, `parent_key`, `name`, `description`, `category`, `route_prefix`, `icon`, `is_active`, `is_core`, `sort_order`) 
VALUES
-- 1. KELOMPOK INDUK: ONESERVICE
('ONESERVICE', NULL, 'Satu Nomor, Satu Jejak Presisi (OneService)', 'Paket 8 Modul Pelayanan Publik & Pelacakan Terpadu', 'ONESERVICE', 'oneservice', 'fas fa-fingerprint', 1, 1, 10),
('oneservice_hub', 'ONESERVICE', 'Hub Layanan Terpadu', 'Pusat integrasi 8 layanan kepolisian satu atap', 'ONESERVICE', 'oneservice', 'fas fa-th-large', 1, 1, 11),
('oneservice_track', 'ONESERVICE', 'Portal Pelacak Warga', 'Pelacakan progres pengaduan publik dengan verifikasi PIN & UU PDP', 'ONESERVICE', 'oneservice/track', 'fas fa-magnifying-glass', 1, 1, 12),
('oneservice_dispatch', 'ONESERVICE', 'Triase & CAD 110', 'Computer-Aided Dispatch panggilan darurat 110 dan tracking respon TKP', 'ONESERVICE', 'oneservice/dispatch', 'fas fa-headset', 1, 0, 13),
('oneservice_spkt', 'ONESERVICE', 'Loket SPKT Ramah Korban', 'Penerimaan laporan standar Zero-Rejection & Jalur Khusus PPA', 'ONESERVICE', 'oneservice/spkt', 'fas fa-hand-holding-heart', 1, 1, 14),
('oneservice_cases', 'ONESERVICE', 'Manajemen Perkara & SP2HP', 'Monitoring timeline SLA penyidikan & penerbitan SP2HP elektronik A1-A5', 'ONESERVICE', 'oneservice/cases', 'fas fa-folder-tree', 1, 0, 15),
('oneservice_cyber', 'ONESERVICE', 'Fast-Action Anti-Fraud Siber', 'Deteksi kluster sindikat rekening & permohonan blokir cepat ke OJK/Bank', 'ONESERVICE', 'oneservice/cyber', 'fas fa-shield-virus', 1, 0, 16),
('oneservice_bhabin', 'ONESERVICE', 'Problem Solving Bhabinkamtibmas', 'Pencatatan mediasi damai Restorative Justice (RJ) di tingkat desa', 'ONESERVICE', 'oneservice/bhabin', 'fas fa-handshake', 1, 0, 17),
('oneservice_dumas', 'ONESERVICE', 'Dumas Terpadu & Anomali Integritas', 'Satu pintu aduan masyarakat & radar peringatan dini pungli loket', 'ONESERVICE', 'oneservice/dumas', 'fas fa-bullhorn', 1, 0, 18),
('oneservice_dashboard', 'ONESERVICE', 'Dashboard Akuntabilitas 100 Hari', 'Transparansi 6 Pertanyaan Strategis Halaman 41 Paparan Prioritas Kapolri', 'ONESERVICE', 'oneservice/dashboard', 'fas fa-chart-pie', 1, 0, 19),

-- 2. KELOMPOK INDUK: POSKO
('POSKO', NULL, 'Management Posko Presisi', 'Ekosistem pelaporan berjenjang Polsek - Polres - Polda - Mabes', 'POSKO', 'posko', 'fas fa-sitemap', 1, 1, 20),
('posko_home', 'POSKO', 'Beranda Posko', 'Dashboard matriks pemantauan pelaporan program presisi', 'POSKO', 'posko', 'fas fa-sitemap', 1, 1, 21),
('posko_reports', 'POSKO', 'Laporan Posko', 'Pengisian & pengiriman berkas laporan berjenjang satuan kewilayahan', 'POSKO', 'posko-laporan', 'fas fa-file-lines', 1, 1, 22),
('posko_compliance', 'POSKO', 'Kepatuhan Pelaporan', 'Tabel kepatuhan dan ketepatan waktu pengiriman laporan harian', 'POSKO', 'posko-kepatuhan', 'fas fa-table-cells', 1, 0, 23),
('posko_recap', 'POSKO', 'Susun Rekap Berjenjang', 'Agregasi rekapitulasi data laporan dari jajaran bawah', 'POSKO', 'posko-rekap', 'fas fa-layer-group', 1, 0, 24),
('posko_verification', 'POSKO', 'Antrean Verifikasi', 'Inbox verifikasi dan persetujuan laporan yang masuk dari jajaran', 'POSKO', 'posko-laporan/inbox', 'fas fa-inbox', 1, 0, 25),
('posko_users', 'POSKO', 'Pengguna Posko', 'Manajemen akun personil operator dan admin posko kewilayahan', 'POSKO', 'posko/users', 'fas fa-users-gear', 1, 0, 26),
('posko_org', 'POSKO', 'Organisasi Posko', 'Struktur hierarki induk dan unit kerja posko', 'POSKO', 'posko/organizations', 'fas fa-building-shield', 1, 0, 27),
('posko_types', 'POSKO', 'Jenis Laporan Posko', 'Konfigurasi template formulir dan jenis laporan posko pusat', 'POSKO', 'posko-jenis', 'fas fa-list-check', 1, 0, 28),

-- 3. KELOMPOK INDUK: OPERASIONAL
('OPERATIONAL', NULL, 'Operasional Kepolisian', 'Modul operasional harian penanganan perkara & aduan masyarakat', 'OPERATIONAL', 'operational', 'fas fa-shield-halved', 1, 1, 30),
('op_spkt', 'OPERATIONAL', 'Triage SPKT Sentral', 'Sentra Pelayanan Kepolisian Terpadu & penerimaan berkas perkara awal', 'OPERATIONAL', 'spkt', 'fas fa-desktop', 1, 1, 31),
('op_social', 'OPERATIONAL', 'Social Media Ingestion', 'Patroli siber & konversi pengaduan viral media sosial ke tiket perkara', 'OPERATIONAL', 'social', 'fab fa-x-twitter', 1, 0, 32),
('op_satker', 'OPERATIONAL', 'Disposisi Satker & Kanit', 'Penerimaan disposisi laporan dari SPKT dan penugasan ke penyidik', 'OPERATIONAL', 'satker', 'fas fa-inbox', 1, 0, 33),
('op_investigator', 'OPERATIONAL', 'Workspace Penyidik', 'Manajemen berkas perkara penyidikan, administrasi Sprintgas & SP2HP', 'OPERATIONAL', 'investigator', 'fas fa-user-edit', 1, 0, 34),
('op_omnichat', 'OPERATIONAL', 'Omnichat Live Chat', 'Kanal percakapan interaktif warga langsung dengan operator jaga', 'OPERATIONAL', 'omnichat', 'fas fa-comments', 1, 0, 35),

-- 4. KELOMPOK INDUK: EKSEKUTIF
('EXECUTIVE', NULL, 'Eksekutif Pimpinan', 'Pemantauan indikator kinerja utama & arahan strategis pimpinan', 'EXECUTIVE', 'executive', 'fas fa-chart-line', 1, 0, 40),
('exec_kapolri', 'EXECUTIVE', 'Dashboard Kapolri', 'Executive dashboard visualisasi peta wilayah & direktif pimpinan', 'EXECUTIVE', 'executive', 'fas fa-chart-line', 1, 0, 41),

-- 5. KELOMPOK INDUK: PENGAWASAN
('AUDIT', NULL, 'Pengawasan & Audit', 'Log pengawasan aktivitas personil dan kepatuhan sistem', 'AUDIT', 'audit', 'fas fa-clipboard-list', 1, 1, 50),
('audit_trail', 'AUDIT', 'Jejak Audit', 'Pencatatan riwayat setiap aksi dan modifikasi data oleh pengguna', 'AUDIT', 'audit', 'fas fa-clipboard-list', 1, 1, 51)
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `route_prefix` = VALUES(`route_prefix`),
  `icon` = VALUES(`icon`),
  `is_core` = VALUES(`is_core`),
  `sort_order` = VALUES(`sort_order`);
