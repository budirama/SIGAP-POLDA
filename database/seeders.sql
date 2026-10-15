-- =============================================================================
-- SEEDERS: SIGAP-POLRI INITIAL DATA & DEMO ACCOUNTS (TINGKAT MABES POLRI)
-- Default Password untuk semua akun dinas: password123
-- Seluruh nama personel, nomor telepon & isi perkara adalah DATA DEMO FIKTIF.
-- Prasyarat database lama: database/migrations/2026_10_15_polri_level_satker_types.sql
-- =============================================================================

USE `sigap_polda`;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. SEED DATA SATKER: MABES POLRI, SATKER MABES (DIREKTORAT/KORPS/DIVISI) & POLDA JAJARAN
INSERT INTO `satkers` (`id`, `parent_id`, `type`, `code`, `name`, `address`, `phone`, `latitude`, `longitude`) VALUES
(1, NULL, 'MABES', 'MABES-POLRI', 'Mabes Polri (Command Center SPKT)', 'Jl. Trunojoyo No. 3, Kebayoran Baru, Jakarta Selatan', '021-7210001', -6.24150000, 106.79980000),
(2, 1, 'DIREKTORAT', 'DITTIPIDUM', 'Dittipidum Bareskrim Polri (Tindak Pidana Umum)', 'Gedung Bareskrim Polri, Jl. Trunojoyo No. 3', '021-7210002', -6.24160000, 106.79990000),
(3, 1, 'DIREKTORAT', 'DITTIPIDSIBER', 'Dittipidsiber Bareskrim Polri (Tindak Pidana Siber)', 'Gedung Bareskrim Polri, Jl. Trunojoyo No. 3', '021-7210003', -6.24170000, 106.80000000),
(4, 1, 'DIREKTORAT', 'DITTIPIDNARKOBA', 'Dittipidnarkoba Bareskrim Polri (Tindak Pidana Narkoba)', 'Gedung Bareskrim Polri, Jl. Trunojoyo No. 3', '021-7210004', -6.24180000, 106.80010000),
(5, 1, 'DIREKTORAT', 'KORLANTAS', 'Korlantas Polri (Korps Lalu Lintas)', 'Jl. MT Haryono Kav. 37-38, Jakarta Selatan', '021-7210005', -6.24180000, 106.84850000),
(6, 1, 'DIREKTORAT', 'DIVPROPAM', 'Divpropam Polri (Profesi & Pengamanan)', 'Mabes Polri, Jl. Trunojoyo No. 3', '021-7210006', -6.24140000, 106.79970000),
(7, 1, 'POLDA', 'POLDA-METRO', 'Polda Metro Jaya', 'Jl. Jenderal Sudirman Kav. 55, Jakarta Selatan', '021-7210007', -6.22350000, 106.80990000),
(8, 1, 'POLDA', 'POLDA-JABAR', 'Polda Jawa Barat', 'Jl. Soekarno-Hatta, Bandung', '022-7210008', -6.94020000, 107.66920000),
(9, 1, 'POLDA', 'POLDA-JATENG', 'Polda Jawa Tengah', 'Jl. Pahlawan, Semarang', '024-7210009', -6.99360000, 110.42110000),
(10, 1, 'POLDA', 'POLDA-JATIM', 'Polda Jawa Timur', 'Jl. Ahmad Yani, Surabaya', '031-7210010', -7.33340000, 112.73150000),
(11, 1, 'POLDA', 'POLDA-SUMUT', 'Polda Sumatera Utara', 'Jl. Sisingamangaraja, Medan', '061-7210011', 3.53710000, 98.70560000)
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`), `type`=VALUES(`type`), `code`=VALUES(`code`), `name`=VALUES(`name`),
    `address`=VALUES(`address`), `phone`=VALUES(`phone`), `latitude`=VALUES(`latitude`), `longitude`=VALUES(`longitude`);

-- 2. SEED KATEGORI PERKARA
INSERT INTO `report_categories` (`id`, `code`, `name`, `default_satker_type`, `sla_hours`, `icon`, `description`) VALUES
(1, 'CAT-KRIMINAL-UMUM', 'Kriminal Umum (Curanmor, Curat, Curas, Penipuan)', 'DIREKTORAT', 48, 'shield-alert', 'Pencurian, kekerasan fisik, pemerasan, dan penggelapan'),
(2, 'CAT-SIBER-KHUSUS', 'Kejahatan Siber & Kejahatan Finansial Khusus', 'DIREKTORAT', 48, 'cpu', 'Penipuan online, kebocoran data, pemerasan siber, pornografi anak'),
(3, 'CAT-NARKOBA', 'Peredaran & Penyalahgunaan Narkoba', 'DIREKTORAT', 24, 'alert-triangle', 'Informasi peredaran narkotika, tempat pesta sabu/inex'),
(4, 'CAT-LANTAS', 'Lalu Lintas (Kemacetan Ekstrem, Lakalantas, Pungli)', 'DIREKTORAT', 12, 'truck', 'Pelanggaran lalu lintas jalan raya, laka maut, aksi balap liar'),
(5, 'CAT-PROPAM', 'Pengaduan Perilaku Anggota Polri (Dumas Propam)', 'DIREKTORAT', 72, 'user-x', 'Pungli oleh personil, arogansi kewenangan, kekerasan oknum'),
(6, 'CAT-KAMTIBMAS', 'Gangguan Keamanan & Ketertiban Lingkungan (Tawuran/Miras)', 'POLDA', 24, 'users', 'Tawuran warga, geng motor, keresahan ketertiban umum')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `default_satker_type`=VALUES(`default_satker_type`);

-- 3. SEED AKUN PENGGUNA RESMI (9 ROLES KEPOLISIAN & MASYARAKAT) — NAMA FIKTIF
-- Hash bcrypt '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O' = password123
INSERT INTO `users` (`id`, `nrp`, `nik`, `name`, `email`, `phone`, `password_hash`, `role`, `satker_id`, `rank`, `position`) VALUES
-- Superadmin IT (Divisi TIK Polri)
(1, '90010101', NULL, 'Bripka Adhi Nugraha, S.Kom', 'superadmin@sigap.polri.go.id', '081211110001', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SUPERADMIN', 1, 'Bripka', 'Administrator Sistem Divisi TIK Polri'),
-- Kapolri (Executive)
(2, '70050201', NULL, 'Jenderal Pol. Dr. Arya Wiratama, M.Si.', 'kapolri@sigap.polri.go.id', '081211110002', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_EXECUTIVE', 1, 'Jenderal Pol', 'Kepala Kepolisian Negara Republik Indonesia'),
-- Operator SPKT / Command Center Mabes
(3, '88030301', NULL, 'Aipda Hendra Gunawan', 'spkt@sigap.polri.go.id', '081211110003', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SPKT_OPERATOR', 1, 'Aipda', 'Operator Verifikator SPKT Mabes Polri'),
-- Tim Divisi Humas / Patroli Siber Medsos
(4, '92080401', NULL, 'Briptu Cindy Permata', 'humas@sigap.polri.go.id', '081211110004', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_CYBER_HUMAS', 1, 'Briptu', 'Petugas Patroli Siber Divisi Humas Polri'),
-- Pimpinan Satker: Dirtipidum Bareskrim
(5, '75020501', NULL, 'Brigjen Pol. Wira Satya Triputra', 'dirtipidum@sigap.polri.go.id', '081211110005', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_HEAD', 2, 'Brigjen Pol', 'Direktur Tindak Pidana Umum Bareskrim Polri'),
-- Kanit Dittipidum (Admin Satker)
(6, '83090601', NULL, 'Kompol Denny Arifin, S.H.', 'kanit.tipidum@sigap.polri.go.id', '081211110006', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 2, 'Kompol', 'Kanit 1 Subdit Jatanras Dittipidum Bareskrim Polri'),
-- Penyidik Pelaksana Dittipidum
(7, '91040701', NULL, 'Ipda Rian Prasetya, S.Tr.K.', 'penyidik.rian@sigap.polri.go.id', '081211110007', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 2, 'Ipda', 'Penyidik Pembantu Dittipidum Bareskrim Polri'),
-- Pengawas Internal (Itwasum Polri)
(8, '78010801', NULL, 'Irjen Pol. Raden Firman, S.I.K.', 'itwasum@sigap.polri.go.id', '081211110008', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INTERNAL_AFFAIRS', 1, 'Irjen Pol', 'Auditor Inspektorat Pengawasan Umum (Itwasum) Polri'),
-- Warga Pelapor Terverifikasi
(9, NULL, '3171012304950001', 'Budi Santoso (Masyarakat)', 'budi.santoso95@gmail.com', '081298765432', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_CITIZEN', NULL, NULL, 'Wiraswasta')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `email`=VALUES(`email`), `satker_id`=VALUES(`satker_id`),
    `rank`=VALUES(`rank`), `position`=VALUES(`position`);

-- 4. SEED SAMPLE SOCIAL MEDIA FEEDS (MENTIONS & VIRAL POSTS)
INSERT INTO `social_feeds` (`id`, `platform`, `post_id_str`, `author_username`, `author_name`, `author_avatar`, `post_content`, `post_url`, `media_urls`, `sentiment`, `status`, `converted_report_id`, `handled_by_user_id`, `created_at`) VALUES
(1, 'TWITTER_X', 'tw_1846192837461', 'doni_pratama', 'Doni Pratama', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100', 'Mohon atensinya @HumasPolri! Barusan terjadi aksi begal motor bersenjata tajam di flyover Pesing Jakbar. Korban luka sabetan dan motor Beat hitam dibawa kabur!', 'https://x.com/doni_pratama/status/1846192837461', '["https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=600"]', 'NEGATIVE_URGENT', 'CONVERTED_TO_TICKET', 1, 4, DATE_SUB(NOW(), INTERVAL 14 HOUR)),
(2, 'INSTAGRAM', 'ig_9482710492819', 'infoviral.nusantara', 'Info Viral Nusantara', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=100', 'Viral! Gerombolan debt collector menghadang mobil keluarga di Jl. Sudirman depan Senayan City dan merampas kunci secara paksa. Mohon ditindak @humaspolri', 'https://instagram.com/p/DB1829aBcd/', '["https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600"]', 'NEGATIVE_URGENT', 'NEW', NULL, NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 'TIKTOK', 'tt_7384910284719', 'antonio_news', 'Antonio Warga', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100', 'Balap liar tiap malam jam 2 dini hari di jalan arteri Surabaya, knalpot brong sangat bising dan mengancam pengguna jalan lain. Tolong ditertibkan Pak Polisi.', 'https://tiktok.com/@antonio_news/video/7384910284719', NULL, 'NEGATIVE_URGENT', 'NEW', NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR))
ON DUPLICATE KEY UPDATE `author_username`=VALUES(`author_username`), `author_name`=VALUES(`author_name`),
    `post_content`=VALUES(`post_content`), `handled_by_user_id`=VALUES(`handled_by_user_id`);

-- 5. SEED SAMPLE REPORTS (KASUS SELESAI, SEDANG PROSES, ATENSI KAPOLRI & DISPOSISI KE POLDA)
INSERT INTO `reports` (`id`, `ticket_number`, `security_pin`, `source_channel`, `social_feed_id`, `social_post_url`, `reporter_id`, `is_anonymous`, `reporter_name_raw`, `reporter_nik_raw`, `reporter_phone`, `category_id`, `title`, `description`, `incident_date`, `incident_time`, `incident_location`, `latitude`, `longitude`, `current_satker_id`, `assigned_investigator_id`, `status`, `urgency_level`, `is_kapolda_attention`, `kapolda_attention_at`, `sla_deadline`, `closed_at`, `created_at`) VALUES
-- Kasus 1 & 2 bukan milik akun warga #9 (pelapor dari medsos / whistleblower); kasus 3 milik Budi Santoso
-- Kasus 1: Atensi Kapolri (begal bersenjata dari medsos X) ditangani Dittipidum Bareskrim
(1, 'LP-POLRI-202610-0001', '819203', 'TWITTER_X', 1, 'https://x.com/doni_pratama/status/1846192837461', NULL, 0, 'Doni Pratama', NULL, NULL, 1, 'Aksi Curas Pembegalan Bersenjata Tajam di Flyover Pesing', 'Terjadi pembegalan terhadap pemotor Honda Beat hitam B 4521 TZA oleh 4 pelaku boncengan 2 motor menggunakan celurit. Korban mengalami luka di lengan kiri. Modus serupa terjadi di beberapa provinsi sehingga ditangani Bareskrim.', CURDATE(), '01:30:00', 'Flyover Pesing, Daan Mogot, Grogol Petamburan, Jakarta Barat', -6.15780000, 106.77250000, 2, 7, 'INVESTIGATING', 'HIGH', 1, DATE_SUB(NOW(), INTERVAL 9 HOUR), DATE_ADD(NOW(), INTERVAL 24 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 14 HOUR)),

-- Kasus 2: Penipuan investasi online (whistleblower) didisposisikan ke Dittipidsiber
(2, 'LP-POLRI-202610-0002', '459102', 'WEB_PORTAL', NULL, NULL, NULL, 1, 'Siti Nurhaliza (Disamarkan)', '3175029384710002', '081388776655', 2, 'Penipuan Investasi Trading Robot Berkedok Arisan Online Rp 150 Juta', 'Terlapor atas nama inisial AR menjanjikan keuntungan 25% per bulan melalui aplikasi trading fiktif dengan korban di berbagai provinsi. Setelah dana ditransfer Rp 150.000.000, kontak terlapor diblokir.', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '14:00:00', 'Kelapa Gading, Jakarta Utara', -6.16050000, 106.90800000, 3, NULL, 'DISPATCHED', 'NORMAL', 0, NULL, DATE_ADD(NOW(), INTERVAL 48 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Kasus 3: Kasus selesai (curanmor) ditangani Dittipidum Bareskrim
(3, 'LP-POLRI-202610-0003', '201948', 'WEB_PORTAL', NULL, NULL, 9, 0, 'Budi Santoso', '3171012304950001', '081298765432', 1, 'Pencurian Motor Vario di Parkiran Minimarket Kemayoran', 'Sepeda motor Honda Vario 160 putih B 6789 PQR dicuri saat pelapor berbelanja selama 10 menit. Rekaman CCTV minimarket jelas memperlihatkan wajah 2 pelaku kunci T.', DATE_SUB(CURDATE(), INTERVAL 5 DAY), '19:45:00', 'Jl. Garuda No. 12, Kemayoran, Jakarta Pusat', -6.16200000, 106.84500000, 2, 7, 'CLOSED', 'NORMAL', 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),

-- Kasus 4: Gangguan kamtibmas di daerah didisposisikan ke Polda jajaran (Polda Jawa Timur)
(4, 'LP-POLRI-202610-0004', '730516', 'WEB_PORTAL', NULL, NULL, NULL, 0, 'Rahmat Hidayat', '3578010101900004', '081377712345', 6, 'Tawuran Remaja Berulang di Kawasan Pasar Malam Surabaya', 'Tawuran antarkelompok remaja bersenjata tajam terjadi hampir setiap akhir pekan di sekitar pasar malam. Warga resah dan pedagang menutup lapak lebih awal.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '23:15:00', 'Kawasan Pasar Malam, Surabaya, Jawa Timur', -7.25750000, 112.75210000, 10, NULL, 'DISPATCHED', 'NORMAL', 0, NULL, DATE_ADD(NOW(), INTERVAL 12 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 12 HOUR))
ON DUPLICATE KEY UPDATE `ticket_number`=VALUES(`ticket_number`), `title`=VALUES(`title`), `description`=VALUES(`description`),
    `reporter_id`=VALUES(`reporter_id`), `reporter_nik_raw`=VALUES(`reporter_nik_raw`), `reporter_phone`=VALUES(`reporter_phone`),
    `current_satker_id`=VALUES(`current_satker_id`), `assigned_investigator_id`=VALUES(`assigned_investigator_id`),
    `status`=VALUES(`status`), `kapolda_attention_at`=VALUES(`kapolda_attention_at`), `closed_at`=VALUES(`closed_at`);

-- 6. SEED TIMELINE & SP2HP DOKUMEN
-- Timeline laporan demo dibuat ulang setiap seeder dijalankan (tanpa ID tetap agar tidak bentrok dengan data operasional)
DELETE FROM `report_timeline` WHERE `report_id` IN (1, 2, 3, 4);
INSERT INTO `report_timeline` (`report_id`, `actor_id`, `status_from`, `status_to`, `title`, `public_notes`, `internal_notes`, `is_sp2hp`, `sp2hp_number`, `created_at`) VALUES
-- Kasus 1
(1, 4, 'SUBMITTED', 'VERIFIED_SPKT', 'Eskalasi Laporan dari Medsos X', 'Laporan yang diviralkan di akun media sosial X telah divalidasi dan diterbitkan Nomor Registrasi Resmi LP-POLRI-202610-0001.', 'Hasil koordinasi dengan akun @doni_pratama. Rekaman video dan TKP valid.', 0, NULL, DATE_SUB(NOW(), INTERVAL 13 HOUR)),
(1, 3, 'VERIFIED_SPKT', 'DISPATCHED', 'Disposisi Perkara ke Dittipidum Bareskrim Polri', 'Laporan telah didisposisikan ke Direktorat Tindak Pidana Umum Bareskrim Polri (Subdit Jatanras) untuk pengejaran pelaku pembegalan.', 'Disposisi atensi cepat mengingat korban luka bacok dan modus lintas provinsi.', 0, NULL, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(1, 6, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Kanit 1 Jatanras telah menerbitkan Surat Perintah Penugasan (Sprintgas) kepada Ipda Rian Prasetya.', 'Sprintgas No: SP.Gas/108/10/2026/DITTIPIDUM.', 0, NULL, DATE_SUB(NOW(), INTERVAL 11 HOUR)),
(1, 7, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan di lokasi kejadian.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(1, 2, 'INVESTIGATING', 'INVESTIGATING', 'Instruksi Khusus: Atensi Kapolri', 'Laporan Anda mendapatkan atensi khusus langsung dari Kapolri untuk penanganan prioritas cepat.', 'ATENSI KAPOLRI: Koordinasi dengan Polda Metro Jaya dan CCTV Dishub, amankan barang bukti dan segera tangkap pelaku kriminal jalanan yang meresahkan.', 0, NULL, DATE_SUB(NOW(), INTERVAL 9 HOUR)),
(1, 7, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1 (A1)', 'Penyidik telah melakukan olah TKP di Flyover Pesing, mengamankan rekaman CCTV Dishub di perempatan Daan Mogot, dan memeriksa 2 saksi mata pedagang sekitar.', 'Identitas 2 motor pelaku (Nmax biru dan Beat hitam tanpa plat) telah terdeteksi mengarah ke wilayah Kalideres. Tim opsnal sedang bergerak.', 1, 'SP2HP/012/10/2026/DITTIPIDUM', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
-- Kasus 2
(2, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui portal publik.', 0, NULL, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(2, 3, 'SUBMITTED', 'DISPATCHED', 'Laporan Diverifikasi & Didisposisikan', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Dittipidsiber Bareskrim Polri (Tindak Pidana Siber) untuk tindak lanjut.', 'Korban tersebar di beberapa provinsi; koordinasi dengan PPATK.', 0, NULL, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
-- Kasus 3 (Kasus Selesai)
(3, 3, 'SUBMITTED', 'VERIFIED_SPKT', 'Verifikasi Berkas Laporan', 'Laporan diterima dan diverifikasi oleh SPKT Polri.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 3, 'VERIFIED_SPKT', 'DISPATCHED', 'Disposisi Perkara ke Dittipidum Bareskrim Polri', 'Laporan telah didisposisikan ke Direktorat Tindak Pidana Umum Bareskrim Polri untuk ditindaklanjuti.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 118 HOUR)),
(3, 6, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Kanit telah menerbitkan Surat Perintah Penugasan (Sprintgas) kepada Ipda Rian Prasetya.', 'Sprintgas No: SP.Gas/097/10/2026/DITTIPIDUM.', 0, NULL, DATE_SUB(NOW(), INTERVAL 115 HOUR)),
(3, 7, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik telah mengamankan rekaman CCTV minimarket dan memulai identifikasi pelaku.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 110 HOUR)),
(3, 7, 'INVESTIGATING', 'CLOSED', 'Kasus Selesai & Pelaku Berhasil Ditangkap', 'Tim penyidik berhasil menangkap kedua pelaku curanmor beserta barang bukti sepeda motor Vario milik korban dalam keadaan utuh. Motor telah diserahkan kembali dengan Berita Acara Pinjam Pakai.', 'Pelaku residivis a.n Jaka dan Dani diamankan di Kampung Muara Kamal.', 1, 'SP2HP/031/10/2026/DITTIPIDUM', DATE_SUB(NOW(), INTERVAL 1 DAY)),
-- Kasus 4 (disposisi ke Polda jajaran)
(4, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui portal publik.', 0, NULL, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(4, 3, 'SUBMITTED', 'DISPATCHED', 'Laporan Diverifikasi & Didisposisikan', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Polda Jawa Timur untuk tindak lanjut.', 'Kewenangan wilayah: diteruskan ke Polda Jawa Timur untuk penanganan oleh satuan kewilayahan.', 0, NULL, DATE_SUB(NOW(), INTERVAL 11 HOUR));

-- 7. SEED DIRECTIVE ATENSI KAPOLRI
INSERT INTO `kapolda_directives` (`id`, `report_id`, `created_by`, `directive_text`, `target_satker_id`, `response_notes`, `status`, `created_at`, `responded_at`) VALUES
(1, 1, 2, 'Tangkap komplotan begal bersenjata tajam ini dalam 1x24 jam dan petakan jaringannya di provinsi lain. Jangan ada kompromi terhadap aksi kekerasan jalanan!', 2, 'Siap Jenderal, Subdit Jatanras Dittipidum telah bergerak bersama Polda Metro Jaya mengidentifikasi jalur pelarian pelaku.', 'ACKNOWLEDGED', DATE_SUB(NOW(), INTERVAL 9 HOUR), DATE_SUB(NOW(), INTERVAL 8 HOUR))
ON DUPLICATE KEY UPDATE `directive_text`=VALUES(`directive_text`), `response_notes`=VALUES(`response_notes`), `target_satker_id`=VALUES(`target_satker_id`);

-- 8. SEED FEEDBACK SKM (SURVEI KEPUASAN)
INSERT INTO `report_feedbacks` (`id`, `report_id`, `rating_stars`, `speed_score`, `fairness_score`, `comments`, `created_at`) VALUES
(1, 3, 5, 5, 5, 'Luar biasa respons cepat kepolisian! Motor saya bisa kembali dalam 4 hari tanpa dipungut biaya sepeser pun. Sangat apresiasi keterbukaan SP2HP digitalnya.', DATE_SUB(NOW(), INTERVAL 18 HOUR))
ON DUPLICATE KEY UPDATE `rating_stars`=VALUES(`rating_stars`);

SET FOREIGN_KEY_CHECKS = 1;
