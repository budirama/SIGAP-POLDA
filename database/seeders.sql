-- =============================================================================
-- SEEDERS: SIGAP-POLRI INITIAL DATA & DEMO ACCOUNTS (TINGKAT MABES POLRI)
-- Default Password untuk semua akun dinas: password123
-- Seluruh nama personel, nomor telepon & isi perkara adalah DATA DEMO FIKTIF.
-- Bagian 1–8: data inti (dipakai test otomatis, id 1–4). Bagian 9: skenario demo tambahan (id 5+).
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
-- Pangkat disimpan di kolom `rank` (tidak diulang di `name`; tampilan menggabungkan pangkat + nama)
-- Hash bcrypt '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O' = password123
INSERT INTO `users` (`id`, `nrp`, `nik`, `name`, `email`, `phone`, `password_hash`, `role`, `satker_id`, `rank`, `position`) VALUES
-- Superadmin IT (Divisi TIK Polri)
(1, '90010101', NULL, 'Adhi Nugraha, S.Kom', 'superadmin@sigap.polri.go.id', '081211110001', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SUPERADMIN', 1, 'Bripka', 'Administrator Sistem Divisi TIK Polri'),
-- Kapolri (Executive)
(2, '70050201', NULL, 'Dr. Arya Wiratama, M.Si.', 'kapolri@sigap.polri.go.id', '081211110002', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_EXECUTIVE', 1, 'Jenderal Pol', 'Kepala Kepolisian Negara Republik Indonesia'),
-- Operator SPKT / Command Center Mabes
(3, '88030301', NULL, 'Hendra Gunawan', 'spkt@sigap.polri.go.id', '081211110003', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SPKT_OPERATOR', 1, 'Aipda', 'Operator Verifikator SPKT Mabes Polri'),
-- Tim Divisi Humas / Patroli Siber Medsos
(4, '92080401', NULL, 'Cindy Permata', 'humas@sigap.polri.go.id', '081211110004', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_CYBER_HUMAS', 1, 'Briptu', 'Petugas Patroli Siber Divisi Humas Polri'),
-- Pimpinan Satker: Dirtipidum Bareskrim
(5, '75020501', NULL, 'Wira Satya Triputra', 'dirtipidum@sigap.polri.go.id', '081211110005', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_HEAD', 2, 'Brigjen Pol', 'Direktur Tindak Pidana Umum Bareskrim Polri'),
-- Kanit Dittipidum (Admin Satker)
(6, '83090601', NULL, 'Denny Arifin, S.H.', 'kanit.tipidum@sigap.polri.go.id', '081211110006', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 2, 'Kompol', 'Kanit 1 Subdit Jatanras Dittipidum Bareskrim Polri'),
-- Penyidik Pelaksana Dittipidum
(7, '91040701', NULL, 'Rian Prasetya, S.Tr.K.', 'penyidik.rian@sigap.polri.go.id', '081211110007', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 2, 'Ipda', 'Penyidik Pembantu Dittipidum Bareskrim Polri'),
-- Pengawas Internal (Itwasum Polri)
(8, '78010801', NULL, 'Raden Firman, S.I.K.', 'itwasum@sigap.polri.go.id', '081211110008', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INTERNAL_AFFAIRS', 1, 'Irjen Pol', 'Auditor Inspektorat Pengawasan Umum (Itwasum) Polri'),
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

-- =============================================================================
-- 9. DATA DEMO TAMBAHAN (SKENARIO KOMPLEKS) — seluruh nama & perkara FIKTIF
--    Personel tiap Satker/Polda, 20 laporan lintas kanal & status (SLA lewat/mendekati),
--    penolakan, atensi Kapolri (PENDING/ACKNOWLEDGED/RESOLVED), SKM, ruang klarifikasi,
--    feed medsos (terkonversi, spam, positif). Waktu relatif terhadap NOW().
-- =============================================================================

-- 9a. Personel & warga tambahan (akun id 22 sengaja nonaktif: contoh mutasi)
INSERT INTO `users` (`id`, `nrp`, `nik`, `name`, `email`, `phone`, `password_hash`, `role`, `satker_id`, `rank`, `position`, `is_active`) VALUES
(10, '93051001', NULL, 'Dimas Arya Saputra, S.Kom.', 'penyidik.dimas@sigap.polri.go.id', '081211110010', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 3, 'Iptu', 'Penyidik Subdit Siber Dittipidsiber Bareskrim Polri', 1),
(11, '85061101', NULL, 'Lestari Wulandari, S.I.K.', 'kanit.siber@sigap.polri.go.id', '081211110011', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 3, 'AKP', 'Kanit 2 Subdit Siber Dittipidsiber Bareskrim Polri', 1),
(12, '87071201', NULL, 'Yosua Manurung', 'penyidik.yosua@sigap.polri.go.id', '081211110012', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 4, 'Aiptu', 'Penyidik Pembantu Dittipidnarkoba Bareskrim Polri', 1),
(13, '94081301', NULL, 'Ahmad Fauzi', 'penyidik.fauzi@sigap.polri.go.id', '081211110013', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 5, 'Ipda', 'Penyidik Laka Lantas Korlantas Polri', 1),
(14, '90091401', NULL, 'Maria Christina Tambunan, S.H.', 'pemeriksa.maria@sigap.polri.go.id', '081211110014', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 6, 'Iptu', 'Pemeriksa Paminal Divpropam Polri', 1),
(15, '72011501', NULL, 'Bambang Setiawan, M.Si.', 'kapolda.metro@sigap.polri.go.id', '081211110015', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_HEAD', 7, 'Irjen Pol', 'Kapolda Metro Jaya', 1),
(16, '84021601', NULL, 'Rudi Hartono, S.H.', 'kanit.metro@sigap.polri.go.id', '081211110016', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 7, 'Kompol', 'Kanit Jatanras Ditreskrimum Polda Metro Jaya', 1),
(17, '95031701', NULL, 'Siti Rahmawati', 'penyidik.siti@sigap.polri.go.id', '081211110017', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 7, 'Bripka', 'Penyidik Pembantu Polda Metro Jaya', 1),
(18, '83041801', NULL, 'Agus Salim, S.H.', 'kanit.jatim@sigap.polri.go.id', '081211110018', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 10, 'Kompol', 'Kanit Resmob Ditreskrimum Polda Jawa Timur', 1),
(19, '92051901', NULL, 'Eko Prasetyo', 'penyidik.eko@sigap.polri.go.id', '081211110019', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 10, 'Aipda', 'Penyidik Pembantu Polda Jawa Timur', 1),
(20, '96062001', NULL, 'Togar Sihombing', 'penyidik.togar@sigap.polri.go.id', '081211110020', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 11, 'Brigpol', 'Penyidik Pembantu Polda Sumatera Utara', 1),
(21, '97072101', NULL, 'Nur Aisyah', 'spkt.shift2@sigap.polri.go.id', '081211110021', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SPKT_OPERATOR', 1, 'Brigpol', 'Operator SPKT Mabes Polri (Shift Malam)', 1),
(22, '86082201', NULL, 'Hendro Wibowo', 'penyidik.hendro@sigap.polri.go.id', '081211110022', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 2, 'Aipda', 'Penyidik (mutasi — akun nonaktif)', 0),
(23, NULL, '3273014507900003', 'Dewi Lestari', 'dewi.lestari90@gmail.com', '081322334455', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_CITIZEN', NULL, NULL, 'Karyawan Swasta', 1),
(24, NULL, '3175062201880005', 'Andi Pratama', 'andi.pratama88@gmail.com', '081255667788', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_CITIZEN', NULL, NULL, 'Wirausaha', 1),
(25, NULL, '3374035808950002', 'Ratna Sari', 'ratnasari95@gmail.com', '085711223344', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_CITIZEN', NULL, NULL, 'Guru', 1),
(26, '82092601', NULL, 'Hadi Susanto, S.I.K.', 'kanit.narkoba@sigap.polri.go.id', '081211110026', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 4, 'AKP', 'Kanit 1 Subdit Narkotika Dittipidnarkoba Bareskrim Polri', 1),
(27, '81102701', NULL, 'I Wayan Sudarma', 'kanit.lantas@sigap.polri.go.id', '081211110027', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 5, 'AKP', 'Kanit Laka Lantas Korlantas Polri', 1),
(28, '79112801', NULL, 'Febri Hidayat, S.H., M.H.', 'kanit.paminal@sigap.polri.go.id', '081211110028', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 6, 'AKBP', 'Kabag Paminal Divpropam Polri', 1),
(29, '84122901', NULL, 'Rizal Nasution', 'kanit.sumut@sigap.polri.go.id', '081211110029', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 11, 'Kompol', 'Kanit Jatanras Ditreskrimum Polda Sumatera Utara', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `email`=VALUES(`email`), `role`=VALUES(`role`), `satker_id`=VALUES(`satker_id`),
    `rank`=VALUES(`rank`), `position`=VALUES(`position`), `is_active`=VALUES(`is_active`);

-- 9b. Feed media sosial tambahan
INSERT INTO `social_feeds` (`id`, `platform`, `post_id_str`, `author_username`, `author_name`, `author_avatar`, `post_content`, `post_url`, `media_urls`, `sentiment`, `status`, `converted_report_id`, `handled_by_user_id`, `created_at`) VALUES
(4, 'TWITTER_X', 'tw_1838800112233', 'warga_kambon', 'Warga Kampung Ambon', NULL, 'Tolong @HumasPolri, transaksi sabu di gang belakang pasar makin terang-terangan, anak-anak sekolah ikut jadi kurir. Kami takut melapor langsung.', 'https://x.com/warga_kambon/status/1838800112233', NULL, 'NEGATIVE_URGENT', 'CONVERTED_TO_TICKET', 8, 4, DATE_SUB(NOW(), INTERVAL 675 HOUR)),
(5, 'INSTAGRAM', 'ig_9500112233445', 'surabaya.malam', 'Surabaya Malam', NULL, 'Viral video pesta narkoba di salah satu tempat hiburan malam Surabaya, pengunjung terlihat menggunakan pil ekstasi terang-terangan. @humaspolri mohon ditindak!', 'https://instagram.com/p/DC5512xYz/', NULL, 'NEGATIVE_URGENT', 'CONVERTED_TO_TICKET', 13, 4, DATE_SUB(NOW(), INTERVAL 31 HOUR)),
(6, 'FACEBOOK', 'fb_77120033', 'promo.obat.pelangsing', 'Promo Pelangsing Herbal', NULL, 'Dijamin turun 10 kg seminggu!! Hubungi kami sekarang, gratis ongkir seluruh Indonesia. #polri #viral', 'https://facebook.com/promo.obat.pelangsing/posts/77120033', NULL, 'SPAM', 'DISMISSED_SPAM', NULL, 4, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(7, 'TWITTER_X', 'tw_1846300998877', 'rina_commuter', 'Rina Commuter', NULL, 'Terima kasih petugas yang sigap mengatur lalu lintas di simpang Semanggi pagi ini, perjalanan jadi lancar. @HumasPolri', 'https://x.com/rina_commuter/status/1846300998877', NULL, 'POSITIVE', 'NEW', NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(8, 'TIKTOK', 'tt_7390011223344', 'medan.update', 'Medan Update', NULL, 'Ada yang bakar lahan sengketa di pinggiran Medan, asap tebal sampai ke permukiman. Warga diancam kalau protes. Tolong pak polisi!', 'https://tiktok.com/@medan.update/video/7390011223344', NULL, 'NEGATIVE_URGENT', 'NEW', NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR))
ON DUPLICATE KEY UPDATE `post_content`=VALUES(`post_content`), `status`=VALUES(`status`),
    `converted_report_id`=VALUES(`converted_report_id`), `handled_by_user_id`=VALUES(`handled_by_user_id`);

-- 9c. Laporan tambahan (id 5–24)
INSERT INTO `reports` (`id`, `ticket_number`, `security_pin`, `source_channel`, `social_feed_id`, `social_post_url`, `reporter_id`, `is_anonymous`, `reporter_name_raw`, `reporter_nik_raw`, `reporter_phone`, `category_id`, `title`, `description`, `incident_date`, `incident_time`, `incident_location`, `latitude`, `longitude`, `current_satker_id`, `assigned_investigator_id`, `status`, `urgency_level`, `is_kapolda_attention`, `kapolda_attention_at`, `rejection_reason`, `sla_deadline`, `closed_at`, `created_at`) VALUES
(5, 'LP-POLRI-202609-0001', '384920', 'WHATSAPP_BOT', NULL, NULL, 23, 0, 'Dewi Lestari', '3273014507900003', '081322334455', 2, 'Pembobolan Rekening via File APK Undangan Pernikahan Palsu', 'Pelapor menerima pesan WhatsApp berisi file APK "undangan pernikahan". Setelah dipasang, saldo rekening Rp 23.400.000 berpindah ke 3 rekening penampung dalam 15 menit.', DATE_SUB(CURDATE(), INTERVAL 31 DAY), '20:15:00', 'Kec. Coblong, Kota Bandung, Jawa Barat', -6.8855, 107.6137, 3, 10, 'CLOSED', 'NORMAL', 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 672 HOUR), DATE_SUB(NOW(), INTERVAL 288 HOUR), DATE_SUB(NOW(), INTERVAL 720 HOUR)),
(6, 'LP-POLRI-202609-0002', '592011', 'CALL_CENTER_110', NULL, NULL, NULL, 0, 'Sutrisno (Pengemudi Travel)', NULL, '081377001122', 4, 'Kecelakaan Beruntun Truk Rem Blong di Tol Cipularang KM 92', 'Truk bermuatan pasir mengalami rem blong di turunan KM 92 arah Jakarta dan menabrak 6 kendaraan. 2 korban luka berat dievakuasi ke RS terdekat.', DATE_SUB(CURDATE(), INTERVAL 25 DAY), '14:40:00', 'Tol Cipularang KM 92, Purwakarta, Jawa Barat', -6.6211, 107.439, 5, 13, 'CLOSED', 'EMERGENCY', 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 588 HOUR), DATE_SUB(NOW(), INTERVAL 480 HOUR), DATE_SUB(NOW(), INTERVAL 600 HOUR)),
(7, 'LP-POLRI-202609-0003', '710348', 'WEB_PORTAL', NULL, NULL, NULL, 1, 'Whistleblower Satpas (Disamarkan)', NULL, '081299887766', 5, 'Dugaan Pungli Oknum Petugas dalam Pengurusan SIM', 'Pemohon SIM yang tidak lulus ujian praktik ditawari "jalur cepat" Rp 750.000 oleh oknum berseragam di area Satpas. Pelapor menyimpan rekaman suara percakapan.', DATE_SUB(CURDATE(), INTERVAL 22 DAY), '10:05:00', 'Satpas SIM, Kota Bekasi, Jawa Barat', -6.2383, 106.9756, 6, 14, 'INVESTIGATING', 'HIGH', 1, DATE_SUB(NOW(), INTERVAL 40 HOUR), NULL, DATE_ADD(NOW(), INTERVAL -48 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 504 HOUR)),
(8, 'LP-POLRI-202609-0004', '268805', 'TWITTER_X', 4, 'https://x.com/warga_kambon/status/1838800112233', NULL, 0, 'Warga Kampung Ambon', NULL, 'Medsos @warga_kambon', 3, 'Peredaran Sabu Melibatkan Kurir Pelajar di Kampung Ambon', 'Laporan viral di media sosial X: transaksi sabu dilakukan terbuka di gang belakang pasar dan melibatkan pelajar sebagai kurir.', DATE_SUB(CURDATE(), INTERVAL 28 DAY), '23:00:00', 'Kampung Ambon, Cengkareng, Jakarta Barat', -6.1662, 106.7445, 4, 12, 'CLOSED', 'HIGH', 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 648 HOUR), DATE_SUB(NOW(), INTERVAL 144 HOUR), DATE_SUB(NOW(), INTERVAL 672 HOUR)),
(9, 'LP-POLRI-202609-0005', '451276', 'WEB_PORTAL', NULL, NULL, 24, 0, 'Andi Pratama', '3175062201880005', '081255667788', 1, 'Mobil Bekas Tidak Kunjung Diserahkan Penjual Marketplace', 'Pelapor telah membayar uang muka Rp 15.000.000 untuk mobil bekas. Penjual masih dapat dihubungi dan menjanjikan penyerahan bulan depan.', DATE_SUB(CURDATE(), INTERVAL 27 DAY), '16:30:00', 'Cakung, Jakarta Timur', -6.1821, 106.9412, NULL, NULL, 'REJECTED', 'NORMAL', 0, NULL, 'Berdasarkan kronologi, perkara merupakan sengketa wanprestasi (perdata) karena penjual masih beritikad menyerahkan barang. Pelapor disarankan menempuh mediasi atau gugatan perdata; laporan dapat diajukan kembali bila ditemukan unsur penipuan.', NULL, NULL, DATE_SUB(NOW(), INTERVAL 624 HOUR)),
(10, 'LP-POLRI-202609-0006', '906153', 'FACEBOOK', NULL, NULL, NULL, 0, 'Forum Warga Medan Johor', NULL, '081366554433', 6, 'Geng Motor Bersenjata Tajam Resahkan Warga Medan Johor', 'Rombongan geng motor melintas tiap malam Minggu sambil mengacungkan senjata tajam; dua warga menjadi korban penganiayaan.', DATE_SUB(CURDATE(), INTERVAL 18 DAY), '01:10:00', 'Kec. Medan Johor, Kota Medan, Sumatera Utara', 3.5202, 98.6708, 11, 20, 'CLOSED', 'NORMAL', 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 408 HOUR), DATE_SUB(NOW(), INTERVAL 96 HOUR), DATE_SUB(NOW(), INTERVAL 432 HOUR)),
(11, 'LP-POLRI-202610-0005', '137584', 'WEB_PORTAL', NULL, NULL, 25, 0, 'Ratna Sari', '3374035808950002', '085711223344', 1, 'Pencurian dengan Pemberatan Rumah Kosong saat Ditinggal Mudik', 'Pintu belakang rumah dicongkel saat keluarga pelapor ke luar kota. Perhiasan emas 40 gram, laptop, dan uang tunai hilang.', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:00:00', 'Kec. Tembalang, Kota Semarang, Jawa Tengah', -7.0538, 110.4382, 9, NULL, 'DISPATCHED', 'NORMAL', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL 6 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 42 HOUR)),
(12, 'LP-POLRI-202610-0006', '860442', 'WHATSAPP_BOT', NULL, NULL, NULL, 1, 'Korban Sextortion (Disamarkan)', NULL, '081277665544', 2, 'Pemerasan Bermodus Rekaman Video Call (Sextortion)', 'Korban diancam penyebaran rekaman video call bila tidak mentransfer Rp 5.000.000. Pelaku menggunakan 2 nomor WhatsApp luar negeri.', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '22:45:00', 'Kec. Tebet, Jakarta Selatan', -6.2261, 106.8536, 3, 10, 'INVESTIGATING', 'HIGH', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL 10 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 38 HOUR)),
(13, 'LP-POLRI-202610-0007', '225917', 'INSTAGRAM', 5, 'https://instagram.com/p/DC5512xYz/', NULL, 0, 'Surabaya Malam', NULL, 'Medsos @surabaya.malam', 3, 'Pesta Narkoba di Tempat Hiburan Malam Surabaya Viral di Instagram', 'Video viral memperlihatkan pengunjung tempat hiburan malam menggunakan pil ekstasi secara terbuka.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '02:30:00', 'Jl. Embong Malang, Surabaya, Jawa Timur', -7.2611, 112.7356, 10, 19, 'ASSIGNED', 'HIGH', 1, DATE_SUB(NOW(), INTERVAL 20 HOUR), NULL, DATE_ADD(NOW(), INTERVAL -6 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(14, 'LP-POLRI-202610-0008', '648301', 'CALL_CENTER_110', NULL, NULL, NULL, 0, 'Hendra Kurniawan', NULL, '081233445566', 4, 'Balap Liar Rutin di Jalan Arteri Surabaya Dini Hari', 'Puluhan motor berknalpot brong menggelar balap liar setiap pukul 02.00 dan beberapa kali menabrak pengguna jalan.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '02:10:00', 'Jl. Ahmad Yani (Frontage), Surabaya, Jawa Timur', -7.3203, 112.7313, 10, 19, 'INVESTIGATING', 'NORMAL', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL -8 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(15, 'LP-POLRI-202610-0009', '391755', 'WEB_PORTAL', NULL, NULL, NULL, 0, 'Komite Sekolah SMA Pasar Rebo', NULL, '081388990011', 6, 'Tawuran Pelajar Bersenjata Tajam di Flyover Pasar Rebo', 'Dua kelompok pelajar bentrok membawa celurit di bawah flyover; satu pelajar luka bacok dan dirawat di RS Pasar Rebo.', DATE_SUB(CURDATE(), INTERVAL 0 DAY), '15:20:00', 'Flyover Pasar Rebo, Jakarta Timur', -6.3092, 106.8675, 7, 17, 'INVESTIGATING', 'EMERGENCY', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL 8 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 16 HOUR)),
(16, 'LP-POLRI-202610-0010', '574026', 'WEB_PORTAL', NULL, NULL, 23, 0, 'Dewi Lestari', '3273014507900003', '081322334455', 1, 'Penjambretan Ponsel di Halte TransJakarta Kuningan', 'Ponsel pelapor dirampas pengendara motor saat menunggu bus di halte. Pelaku berboncengan dan kabur ke arah Mampang.', DATE_SUB(CURDATE(), INTERVAL 9 DAY), '18:50:00', 'Halte TransJakarta Kuningan, Jakarta Selatan', -6.2295, 106.8262, 7, 17, 'CLOSED', 'NORMAL', 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 168 HOUR), DATE_SUB(NOW(), INTERVAL 48 HOUR), DATE_SUB(NOW(), INTERVAL 216 HOUR)),
(17, 'LP-POLRI-202610-0011', '812630', 'WEB_PORTAL', NULL, NULL, NULL, 0, 'Sri Mulyani (Ketua RT 05)', NULL, '081344556677', 1, 'Penipuan Arisan Online Puluhan Ibu Rumah Tangga Rp 87 Juta', 'Pengelola arisan online menghilang setelah menghimpun setoran 32 peserta senilai total Rp 87.000.000.', DATE_SUB(CURDATE(), INTERVAL 0 DAY), '08:00:00', 'Kec. Ciracas, Jakarta Timur', -6.3239, 106.8707, NULL, NULL, 'SUBMITTED', 'NORMAL', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL 45 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(18, 'LP-POLRI-202610-0012', '409518', 'WHATSAPP_BOT', NULL, NULL, NULL, 1, 'Mahasiswa (Disamarkan)', NULL, '081266778899', 3, 'Dugaan Bandar Narkoba Beroperasi di Asrama Mahasiswa', 'Seorang penghuni asrama diduga menjual ganja sintetis kepada mahasiswa baru melalui grup chat tertutup.', DATE_SUB(CURDATE(), INTERVAL 0 DAY), '21:30:00', 'Kawasan Asrama Mahasiswa, Depok, Jawa Barat', -6.3602, 106.8273, NULL, NULL, 'SUBMITTED', 'HIGH', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL 23 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(19, 'LP-POLRI-202610-0013', '963204', 'WEB_PORTAL', NULL, NULL, NULL, 0, 'Bayu Saputra', NULL, '081299001122', 4, 'Juru Parkir Liar Mengatasnamakan Petugas Kepolisian', 'Juru parkir liar di kawasan wisata memungut Rp 20.000 per motor dengan mengaku "binaan polisi" dan mengintimidasi pengunjung.', DATE_SUB(CURDATE(), INTERVAL 0 DAY), '11:00:00', 'Kawasan Wisata Kota Tua, Jakarta Barat', -6.1352, 106.8133, NULL, NULL, 'VERIFIED_SPKT', 'NORMAL', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL 7 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(20, 'LP-POLRI-202610-0014', '287561', 'WEB_PORTAL', NULL, NULL, NULL, 0, 'Irfan Hakim', NULL, '081355667700', 5, 'Dugaan Arogansi Oknum Anggota saat Razia Kendaraan', 'Oknum anggota membentak dan menendang motor pelapor saat razia, serta menolak menunjukkan surat perintah tugas.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '19:40:00', 'Jl. Raya Bogor, Jakarta Timur', -6.2955, 106.8664, 6, NULL, 'DISPATCHED', 'NORMAL', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL 46 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(21, 'LP-POLRI-202610-0015', '150937', 'CALL_CENTER_110', NULL, NULL, NULL, 0, 'Manajer Toko (Saksi Korban)', NULL, '081211223344', 1, 'Perampokan Minimarket dengan Senjata Api di Bekasi', 'Dua pelaku bersenjata api menodong kasir dan membawa kabur uang Rp 35.000.000 serta rokok. Satu tembakan peringatan dilepaskan.', DATE_SUB(CURDATE(), INTERVAL 0 DAY), '03:20:00', 'Kec. Tambun Selatan, Kabupaten Bekasi', -6.2651, 107.0551, 7, 17, 'ASSIGNED', 'EMERGENCY', 1, DATE_SUB(NOW(), INTERVAL 4 HOUR), NULL, DATE_ADD(NOW(), INTERVAL 42 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(22, 'LP-POLRI-202610-0016', '738264', 'WEB_PORTAL', NULL, NULL, 24, 0, 'Andi Pratama', '3175062201880005', '081255667788', 2, 'Akun Media Sosial Diretas dan Dipakai Meminjam Uang', 'Akun Instagram pelapor diambil alih dan dipakai meminta pinjaman uang ke teman-teman. Bukti tangkapan layar belum dilampirkan.', DATE_SUB(CURDATE(), INTERVAL 3 DAY), '13:00:00', 'Jakarta Timur', NULL, NULL, NULL, NULL, 'REJECTED', 'NORMAL', 0, NULL, 'Kronologi dan bukti belum lengkap (tidak ada tangkapan layar, nomor rekening penerima, maupun identitas korban transfer). Silakan lengkapi lalu ajukan laporan baru melalui portal.', NULL, NULL, DATE_SUB(NOW(), INTERVAL 50 HOUR)),
(23, 'LP-POLRI-202610-0017', '604185', 'WEB_PORTAL', NULL, NULL, NULL, 0, 'Pengurus Koperasi Sejahtera', NULL, '081322110099', 1, 'Penggelapan Dana Koperasi Simpan Pinjam oleh Bendahara', 'Bendahara koperasi diduga menggelapkan dana anggota Rp 1,2 miliar selama 2 tahun melalui pencairan pinjaman fiktif.', DATE_SUB(CURDATE(), INTERVAL 5 DAY), '10:00:00', 'Kec. Kebayoran Lama, Jakarta Selatan', -6.2468, 106.7789, 2, 7, 'INVESTIGATING', 'NORMAL', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL -48 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 96 HOUR)),
(24, 'LP-POLRI-202610-0018', '329740', 'WEB_PORTAL', NULL, NULL, NULL, 0, 'Kelompok Tani Desa', NULL, '081377889900', 6, 'Pembakaran Lahan Sengketa dan Intimidasi Warga', 'Lahan yang sedang disengketakan dibakar orang tak dikenal; warga yang memprotes diancam akan dianiaya.', DATE_SUB(CURDATE(), INTERVAL 0 DAY), '16:00:00', 'Pinggiran Kota Medan, Sumatera Utara', 3.4801, 98.6213, 11, NULL, 'DISPATCHED', 'NORMAL', 0, NULL, NULL, DATE_ADD(NOW(), INTERVAL 14 HOUR), NULL, DATE_SUB(NOW(), INTERVAL 10 HOUR))
ON DUPLICATE KEY UPDATE `ticket_number`=VALUES(`ticket_number`), `title`=VALUES(`title`), `description`=VALUES(`description`),
    `reporter_id`=VALUES(`reporter_id`), `current_satker_id`=VALUES(`current_satker_id`), `assigned_investigator_id`=VALUES(`assigned_investigator_id`),
    `status`=VALUES(`status`), `urgency_level`=VALUES(`urgency_level`), `is_kapolda_attention`=VALUES(`is_kapolda_attention`),
    `kapolda_attention_at`=VALUES(`kapolda_attention_at`), `rejection_reason`=VALUES(`rejection_reason`),
    `sla_deadline`=VALUES(`sla_deadline`), `closed_at`=VALUES(`closed_at`), `created_at`=VALUES(`created_at`);

-- 9d. Timeline & SP2HP laporan tambahan (dibuat ulang setiap seeder dijalankan)
DELETE FROM `report_timeline` WHERE `report_id` BETWEEN 5 AND 24;
INSERT INTO `report_timeline` (`report_id`, `actor_id`, `status_from`, `status_to`, `title`, `public_notes`, `internal_notes`, `is_sp2hp`, `sp2hp_number`, `created_at`) VALUES
(5, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WHATSAPP_BOT.', 0, NULL, DATE_SUB(NOW(), INTERVAL 720 HOUR)),
(5, 21, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Dittipidsiber Bareskrim Polri', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Dittipidsiber Bareskrim Polri untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 714 HOUR)),
(5, 11, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Dimas Arya Saputra, S.Kom..', 'Sprintgas No: SP.Gas/105/09/2026/DITTIPIDSIBER.', 0, NULL, DATE_SUB(NOW(), INTERVAL 708 HOUR)),
(5, 10, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 702 HOUR)),
(5, 10, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', 'Penyidik telah mengajukan pemblokiran 3 rekening penampung ke bank terkait dan menganalisis file APK berbahaya.', NULL, 1, 'SP2HP/215/09/2026/DITTIPIDSIBER', DATE_SUB(NOW(), INTERVAL 564 HOUR)),
(5, 10, 'INVESTIGATING', 'CLOSED', 'Perkara Selesai', 'Tersangka pengendali jaringan APK ditangkap di Sidrap. Sebagian dana korban berhasil diselamatkan melalui pemblokiran rekening.', NULL, 1, 'SP2HP/216/09/2026/DITTIPIDSIBER', DATE_SUB(NOW(), INTERVAL 288 HOUR)),
(6, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui CALL_CENTER_110.', 0, NULL, DATE_SUB(NOW(), INTERVAL 600 HOUR)),
(6, 3, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Korlantas Polri', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Korlantas Polri untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 594 HOUR)),
(6, 27, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Ahmad Fauzi.', 'Sprintgas No: SP.Gas/106/09/2026/KORLANTAS.', 0, NULL, DATE_SUB(NOW(), INTERVAL 588 HOUR)),
(6, 13, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 582 HOUR)),
(6, 13, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', 'Olah TKP dan uji teknis kendaraan menunjukkan kegagalan sistem pengereman serta muatan melebihi batas (ODOL).', NULL, 1, 'SP2HP/218/09/2026/KORLANTAS', DATE_SUB(NOW(), INTERVAL 548 HOUR)),
(6, 13, 'INVESTIGATING', 'CLOSED', 'Perkara Selesai', 'Pengemudi dan pemilik armada telah ditetapkan sebagai tersangka. Berkas perkara dilimpahkan ke Kejaksaan.', NULL, 1, 'SP2HP/219/09/2026/KORLANTAS', DATE_SUB(NOW(), INTERVAL 480 HOUR)),
(7, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 504 HOUR)),
(7, 21, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Divpropam Polri', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Divpropam Polri untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 498 HOUR)),
(7, 28, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Maria Christina Tambunan, S.H..', 'Sprintgas No: SP.Gas/107/09/2026/DIVPROPAM.', 0, NULL, DATE_SUB(NOW(), INTERVAL 492 HOUR)),
(7, 14, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 486 HOUR)),
(7, 14, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', 'Tim Paminal telah memeriksa 4 saksi pemohon SIM dan mengamankan rekaman CCTV area Satpas.', NULL, 1, 'SP2HP/221/09/2026/DIVPROPAM', DATE_SUB(NOW(), INTERVAL 243 HOUR)),
(7, 2, 'INVESTIGATING', 'INVESTIGATING', 'Instruksi Khusus: Atensi Kapolri', 'Laporan Anda mendapatkan atensi khusus langsung dari Kapolri untuk penanganan prioritas cepat.', 'ATENSI KAPOLRI: Usut tuntas dugaan pungli Satpas ini dan laporkan hasil pemeriksaan kode etik. Tidak ada toleransi bagi oknum yang mencoreng institusi.', 0, NULL, DATE_SUB(NOW(), INTERVAL 40 HOUR)),
(8, 4, 'SUBMITTED', 'VERIFIED_SPKT', 'Eskalasi Laporan dari Media Sosial', 'Laporan dari media sosial telah divalidasi dan diterbitkan nomor registrasi LP-POLRI-202609-0004.', 'Konversi oleh tim Patroli Siber Divisi Humas.', 0, NULL, DATE_SUB(NOW(), INTERVAL 672 HOUR)),
(8, 3, 'VERIFIED_SPKT', 'DISPATCHED', 'Disposisi Perkara ke Dittipidnarkoba Bareskrim Polri', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Dittipidnarkoba Bareskrim Polri untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 666 HOUR)),
(8, 26, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Yosua Manurung.', 'Sprintgas No: SP.Gas/108/09/2026/DITTIPIDNARKOBA.', 0, NULL, DATE_SUB(NOW(), INTERVAL 660 HOUR)),
(8, 12, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 654 HOUR)),
(8, 12, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', 'Penyelidikan undercover mengidentifikasi 3 lokasi transaksi dan jaringan pemasok dari luar kota.', NULL, 1, 'SP2HP/224/09/2026/DITTIPIDNARKOBA', DATE_SUB(NOW(), INTERVAL 484 HOUR)),
(8, 12, 'INVESTIGATING', 'CLOSED', 'Perkara Selesai', 'Penggerebekan berhasil mengamankan 5 tersangka dan barang bukti sabu 312 gram. Pelajar yang terlibat dirujuk ke program rehabilitasi.', NULL, 1, 'SP2HP/225/09/2026/DITTIPIDNARKOBA', DATE_SUB(NOW(), INTERVAL 144 HOUR)),
(9, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 624 HOUR)),
(9, 21, 'SUBMITTED', 'REJECTED', 'Laporan Tidak Dapat Ditindaklanjuti', 'Berdasarkan kronologi, perkara merupakan sengketa wanprestasi (perdata) karena penjual masih beritikad menyerahkan barang. Pelapor disarankan menempuh mediasi atau gugatan perdata; laporan dapat diajukan kembali bila ditemukan unsur penipuan.', 'Hasil telaah SPKT.', 0, NULL, DATE_SUB(NOW(), INTERVAL 622 HOUR)),
(10, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui FACEBOOK.', 0, NULL, DATE_SUB(NOW(), INTERVAL 432 HOUR)),
(10, 3, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Polda Sumatera Utara', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Polda Sumatera Utara untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 426 HOUR)),
(10, 29, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Togar Sihombing.', 'Sprintgas No: SP.Gas/110/09/2026/POLDA-SUMUT.', 0, NULL, DATE_SUB(NOW(), INTERVAL 420 HOUR)),
(10, 20, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 414 HOUR)),
(10, 20, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', 'Patroli gabungan dijadwalkan setiap akhir pekan; 11 anggota geng motor teridentifikasi dari rekaman CCTV warga.', NULL, 1, 'SP2HP/230/09/2026/POLDA-SUMUT', DATE_SUB(NOW(), INTERVAL 308 HOUR)),
(10, 20, 'INVESTIGATING', 'CLOSED', 'Perkara Selesai', '7 pelaku penganiayaan telah ditangkap dan senjata tajam disita. Patroli rutin tetap dilanjutkan.', NULL, 1, 'SP2HP/231/09/2026/POLDA-SUMUT', DATE_SUB(NOW(), INTERVAL 96 HOUR)),
(11, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 42 HOUR)),
(11, 21, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Polda Jawa Tengah', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Polda Jawa Tengah untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 37 HOUR)),
(12, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WHATSAPP_BOT.', 0, NULL, DATE_SUB(NOW(), INTERVAL 38 HOUR)),
(12, 3, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Dittipidsiber Bareskrim Polri', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Dittipidsiber Bareskrim Polri untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 34 HOUR)),
(12, 11, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Dimas Arya Saputra, S.Kom..', 'Sprintgas No: SP.Gas/112/10/2026/DITTIPIDSIBER.', 0, NULL, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(12, 10, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(12, 10, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', 'Penyidik telah berkoordinasi dengan penyedia platform untuk penonaktifan akun pelaku. Mohon tidak mentransfer uang apa pun.', NULL, 1, 'SP2HP/236/10/2026/DITTIPIDSIBER', DATE_SUB(NOW(), INTERVAL 13 HOUR)),
(13, 4, 'SUBMITTED', 'VERIFIED_SPKT', 'Eskalasi Laporan dari Media Sosial', 'Laporan dari media sosial telah divalidasi dan diterbitkan nomor registrasi LP-POLRI-202610-0007.', 'Konversi oleh tim Patroli Siber Divisi Humas.', 0, NULL, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(13, 21, 'VERIFIED_SPKT', 'DISPATCHED', 'Disposisi Perkara ke Polda Jawa Timur', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Polda Jawa Timur untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 27 HOUR)),
(13, 18, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Eko Prasetyo.', 'Sprintgas No: SP.Gas/113/10/2026/POLDA-JATIM.', 0, NULL, DATE_SUB(NOW(), INTERVAL 24 HOUR)),
(13, 2, 'ASSIGNED', 'ASSIGNED', 'Instruksi Khusus: Atensi Kapolri', 'Laporan Anda mendapatkan atensi khusus langsung dari Kapolri untuk penanganan prioritas cepat.', 'ATENSI KAPOLRI: Tindak tegas tempat hiburan malam yang membiarkan peredaran narkoba. Koordinasikan dengan Pemkot untuk pencabutan izin.', 0, NULL, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(14, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui CALL_CENTER_110.', 0, NULL, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(14, 3, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Polda Jawa Timur', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Polda Jawa Timur untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(14, 18, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Eko Prasetyo.', 'Sprintgas No: SP.Gas/114/10/2026/POLDA-JATIM.', 0, NULL, DATE_SUB(NOW(), INTERVAL 16 HOUR)),
(14, 19, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 14 HOUR)),
(14, 19, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', 'Patroli dini hari telah dilakukan dan 14 sepeda motor tanpa kelengkapan diamankan.', NULL, 1, 'SP2HP/242/10/2026/POLDA-JATIM', DATE_SUB(NOW(), INTERVAL 7 HOUR)),
(15, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 16 HOUR)),
(15, 21, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Polda Metro Jaya', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Polda Metro Jaya untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 14 HOUR)),
(15, 16, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Siti Rahmawati.', 'Sprintgas No: SP.Gas/115/10/2026/POLDA-METRO.', 0, NULL, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(15, 17, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(15, 17, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', '4 pelajar telah diamankan untuk dimintai keterangan didampingi orang tua dan pihak sekolah.', NULL, 1, 'SP2HP/245/10/2026/POLDA-METRO', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(16, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 216 HOUR)),
(16, 3, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Polda Metro Jaya', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Polda Metro Jaya untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 210 HOUR)),
(16, 16, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Siti Rahmawati.', 'Sprintgas No: SP.Gas/116/10/2026/POLDA-METRO.', 0, NULL, DATE_SUB(NOW(), INTERVAL 204 HOUR)),
(16, 17, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 198 HOUR)),
(16, 17, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', 'Rekaman CCTV halte telah diamankan dan ciri-ciri kendaraan pelaku teridentifikasi.', NULL, 1, 'SP2HP/248/10/2026/POLDA-METRO', DATE_SUB(NOW(), INTERVAL 148 HOUR)),
(16, 17, 'INVESTIGATING', 'CLOSED', 'Perkara Selesai', 'Kedua pelaku tertangkap; ponsel pelapor telah dikembalikan dengan Berita Acara Penyerahan Barang Bukti.', NULL, 1, 'SP2HP/249/10/2026/POLDA-METRO', DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(17, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(18, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WHATSAPP_BOT.', 0, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(19, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(19, 21, 'SUBMITTED', 'VERIFIED_SPKT', 'Laporan Terverifikasi', 'Laporan telah diverifikasi dan sedang ditentukan satuan kerja penanganannya.', 'Menunggu disposisi.', 0, NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(20, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(20, 3, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Divpropam Polri', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Divpropam Polri untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 23 HOUR)),
(21, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui CALL_CENTER_110.', 0, NULL, DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(21, 21, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Polda Metro Jaya', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Polda Metro Jaya untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(21, 16, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Siti Rahmawati.', 'Sprintgas No: SP.Gas/121/10/2026/POLDA-METRO.', 0, NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(21, 2, 'ASSIGNED', 'ASSIGNED', 'Instruksi Khusus: Atensi Kapolri', 'Laporan Anda mendapatkan atensi khusus langsung dari Kapolri untuk penanganan prioritas cepat.', 'ATENSI KAPOLRI: Kejar dan tangkap pelaku perampokan bersenjata api ini dalam 2x24 jam. Libatkan Resmob dan Inafis.', 0, NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(22, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 50 HOUR)),
(22, 3, 'SUBMITTED', 'REJECTED', 'Laporan Tidak Dapat Ditindaklanjuti', 'Kronologi dan bukti belum lengkap (tidak ada tangkapan layar, nomor rekening penerima, maupun identitas korban transfer). Silakan lengkapi lalu ajukan laporan baru melalui portal.', 'Hasil telaah SPKT.', 0, NULL, DATE_SUB(NOW(), INTERVAL 48 HOUR)),
(23, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 96 HOUR)),
(23, 21, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Dittipidum Bareskrim Polri', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Dittipidum Bareskrim Polri untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 90 HOUR)),
(23, 6, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Surat Perintah Penugasan (Sprintgas) telah diterbitkan kepada penyidik Rian Prasetya, S.Tr.K..', 'Sprintgas No: SP.Gas/123/10/2026/DITTIPIDUM.', 0, NULL, DATE_SUB(NOW(), INTERVAL 84 HOUR)),
(23, 7, 'ASSIGNED', 'INVESTIGATING', 'Penyelidikan Dimulai', 'Penyidik pelaksana telah memulai penyelidikan dan pengumpulan keterangan.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 78 HOUR)),
(23, 7, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1', 'Penyidik telah meminta audit independen atas laporan keuangan koperasi tahun 2024–2026.', NULL, 1, 'SP2HP/269/10/2026/DITTIPIDUM', DATE_SUB(NOW(), INTERVAL 39 HOUR)),
(24, NULL, 'NEW', 'SUBMITTED', 'Laporan Diterima Sistem', 'Laporan pengaduan Anda telah terdaftar dan menunggu verifikasi kelayakan oleh Petugas SPKT Polri.', 'Laporan baru masuk melalui WEB_PORTAL.', 0, NULL, DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(24, 3, 'SUBMITTED', 'DISPATCHED', 'Disposisi Perkara ke Polda Sumatera Utara', 'Laporan telah diverifikasi oleh SPKT Polri dan didisposisikan ke Polda Sumatera Utara untuk tindak lanjut.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 9 HOUR));

-- 9e. Instruksi Atensi Kapolri (tiga status tindak lanjut)
INSERT INTO `kapolda_directives` (`id`, `report_id`, `created_by`, `directive_text`, `target_satker_id`, `response_notes`, `status`, `created_at`, `responded_at`) VALUES
(2, 7, 2, 'Usut tuntas dugaan pungli Satpas ini dan laporkan hasil pemeriksaan kode etik. Tidak ada toleransi bagi oknum yang mencoreng institusi.', 6, 'Siap Jenderal. Oknum terperiksa telah dibebastugaskan sementara; sidang kode etik dijadwalkan pekan depan.', 'RESOLVED', DATE_SUB(NOW(), INTERVAL 40 HOUR), DATE_SUB(NOW(), INTERVAL 16 HOUR)),
(3, 21, 2, 'Kejar dan tangkap pelaku perampokan bersenjata api ini dalam 2x24 jam. Libatkan Resmob dan Inafis.', 7, NULL, 'PENDING', DATE_SUB(NOW(), INTERVAL 4 HOUR), NULL),
(4, 13, 2, 'Tindak tegas tempat hiburan malam yang membiarkan peredaran narkoba. Koordinasikan dengan Pemkot untuk pencabutan izin.', 10, 'Siap Jenderal, tim gabungan sedang menyusun rencana penindakan bersama BNNP Jatim.', 'ACKNOWLEDGED', DATE_SUB(NOW(), INTERVAL 20 HOUR), DATE_SUB(NOW(), INTERVAL 12 HOUR))
ON DUPLICATE KEY UPDATE `directive_text`=VALUES(`directive_text`), `response_notes`=VALUES(`response_notes`),
    `status`=VALUES(`status`), `target_satker_id`=VALUES(`target_satker_id`), `responded_at`=VALUES(`responded_at`);

-- 9f. Ruang klarifikasi warga ↔ penyidik (dibuat ulang setiap seeder dijalankan)
DELETE FROM `case_messages` WHERE `report_id` BETWEEN 5 AND 24;
INSERT INTO `case_messages` (`report_id`, `sender_type`, `sender_id`, `sender_name`, `message_text`, `is_read`, `created_at`) VALUES
(12, 'POLICE', 10, 'Iptu Dimas Arya Saputra, S.Kom.', 'Selamat siang. Mohon jangan merespons ancaman pelaku dan simpan seluruh tangkapan layar percakapan.', 1, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(12, 'CITIZEN', NULL, 'Korban Sextortion (Disamarkan)', 'Baik Pak, pelaku menghubungi lagi dari nomor baru tadi pagi. Apa yang harus saya lakukan?', 1, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(12, 'POLICE', 10, 'Iptu Dimas Arya Saputra, S.Kom.', 'Blokir nomor tersebut dan kirimkan nomor barunya melalui ruang klarifikasi ini. Tim kami sedang menelusuri jaringannya.', 1, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(16, 'POLICE', 17, 'Bripka Siti Rahmawati', 'Ibu Dewi, mohon kesediaannya hadir ke Polda Metro Jaya untuk mengidentifikasi barang bukti ponsel.', 1, DATE_SUB(NOW(), INTERVAL 96 HOUR)),
(16, 'CITIZEN', NULL, 'Dewi Lestari', 'Siap Bu, saya bisa datang besok pukul 10.00. Terima kasih atas penanganannya.', 1, DATE_SUB(NOW(), INTERVAL 94 HOUR)),
(23, 'CITIZEN', NULL, 'Pengurus Koperasi Sejahtera', 'Pak penyidik, kami sudah menyiapkan salinan buku kas dan daftar pinjaman fiktif. Ke mana kami serahkan?', 1, DATE_SUB(NOW(), INTERVAL 30 HOUR)),
(23, 'POLICE', 7, 'Ipda Rian Prasetya, S.Tr.K.', 'Silakan serahkan langsung ke ruang penyidik Dittipidum Bareskrim pada jam kerja dengan membawa surat kuasa pengurus.', 1, DATE_SUB(NOW(), INTERVAL 26 HOUR));

-- 9g. Survei Kepuasan Masyarakat (SKM) kasus selesai
INSERT INTO `report_feedbacks` (`id`, `report_id`, `rating_stars`, `speed_score`, `fairness_score`, `comments`, `created_at`) VALUES
(2, 5, 4, 4, 5, 'Penyidik responsif dan rajin memberi kabar lewat SP2HP. Sebagian dana saya kembali, terima kasih.', DATE_SUB(NOW(), INTERVAL 240 HOUR)),
(3, 6, 5, 5, 5, 'Penanganan cepat dan transparan, keluarga korban mendapat kepastian hukum.', DATE_SUB(NOW(), INTERVAL 456 HOUR)),
(4, 8, 3, 2, 4, 'Hasilnya baik, tetapi kabar perkembangan sempat lama tidak diperbarui hampir dua minggu.', DATE_SUB(NOW(), INTERVAL 120 HOUR)),
(5, 16, 5, 5, 5, 'Ponsel saya kembali dalam seminggu. Pelayanan Polda Metro Jaya sangat memuaskan!', DATE_SUB(NOW(), INTERVAL 30 HOUR))
ON DUPLICATE KEY UPDATE `rating_stars`=VALUES(`rating_stars`), `speed_score`=VALUES(`speed_score`),
    `fairness_score`=VALUES(`fairness_score`), `comments`=VALUES(`comments`);

-- 9h. Sequence nomor tiket: tiket berikutnya tidak bentrok dengan nomor demo
INSERT INTO `ticket_sequences` (`period`, `last_seq`) VALUES ('202609', 6), ('202610', 18)
ON DUPLICATE KEY UPDATE `last_seq` = GREATEST(`last_seq`, VALUES(`last_seq`));

-- 10. HIERARKI SATUAN KEWILAYAHAN POLRI (POSKO): POLDA, POLRES & POLSEK
--     Sumber nama satuan: Wikipedia bahasa Indonesia (Kepolisian Daerah & halaman tiap Polda),
--     diakses 15 Oktober 2026. Polres lengkap untuk 5 Polda demo; Polsek contoh: Polrestabes Surabaya.
--     Kode satuan, alamat & data turunan bersifat DEMO; ganti dengan data resmi sebelum produksi.
INSERT INTO `satkers` (`id`, `parent_id`, `type`, `code`, `name`, `address`) VALUES
(12, 1, 'POLDA', 'POLDA-ACEH', 'Polda Aceh', 'Banda Aceh'),
(13, 1, 'POLDA', 'POLDA-SUMBAR', 'Polda Sumatera Barat', 'Padang'),
(14, 1, 'POLDA', 'POLDA-RIAU', 'Polda Riau', 'Pekanbaru'),
(15, 1, 'POLDA', 'POLDA-KEPRI', 'Polda Kepulauan Riau', 'Batam'),
(16, 1, 'POLDA', 'POLDA-JAMBI', 'Polda Jambi', 'Jambi'),
(17, 1, 'POLDA', 'POLDA-BENGKULU', 'Polda Bengkulu', 'Bengkulu'),
(18, 1, 'POLDA', 'POLDA-SUMSEL', 'Polda Sumatera Selatan', 'Palembang'),
(19, 1, 'POLDA', 'POLDA-BABEL', 'Polda Kepulauan Bangka Belitung', 'Kabupaten Bangka Tengah'),
(20, 1, 'POLDA', 'POLDA-LAMPUNG', 'Polda Lampung', 'Bandar Lampung'),
(21, 1, 'POLDA', 'POLDA-BANTEN', 'Polda Banten', 'Serang'),
(22, 1, 'POLDA', 'POLDA-DIY', 'Polda Daerah Istimewa Yogyakarta', 'Sleman'),
(23, 1, 'POLDA', 'POLDA-BALI', 'Polda Bali', 'Denpasar'),
(24, 1, 'POLDA', 'POLDA-NTB', 'Polda Nusa Tenggara Barat', 'Mataram'),
(25, 1, 'POLDA', 'POLDA-NTT', 'Polda Nusa Tenggara Timur', 'Kupang'),
(26, 1, 'POLDA', 'POLDA-KALBAR', 'Polda Kalimantan Barat', 'Pontianak'),
(27, 1, 'POLDA', 'POLDA-KALTENG', 'Polda Kalimantan Tengah', 'Palangka Raya'),
(28, 1, 'POLDA', 'POLDA-KALSEL', 'Polda Kalimantan Selatan', 'Banjarbaru'),
(29, 1, 'POLDA', 'POLDA-KALTIM', 'Polda Kalimantan Timur', 'Balikpapan'),
(30, 1, 'POLDA', 'POLDA-KALTARA', 'Polda Kalimantan Utara', 'Tanjung Selor'),
(31, 1, 'POLDA', 'POLDA-SULUT', 'Polda Sulawesi Utara', 'Manado'),
(32, 1, 'POLDA', 'POLDA-GORONTALO', 'Polda Gorontalo', 'Kabupaten Gorontalo'),
(33, 1, 'POLDA', 'POLDA-SULTENG', 'Polda Sulawesi Tengah', 'Palu'),
(34, 1, 'POLDA', 'POLDA-SULTRA', 'Polda Sulawesi Tenggara', 'Kendari'),
(35, 1, 'POLDA', 'POLDA-SULSEL', 'Polda Sulawesi Selatan', 'Makassar'),
(36, 1, 'POLDA', 'POLDA-SULBAR', 'Polda Sulawesi Barat', 'Mamuju'),
(37, 1, 'POLDA', 'POLDA-MALUT', 'Polda Maluku Utara', 'Sofifi'),
(38, 1, 'POLDA', 'POLDA-MALUKU', 'Polda Maluku', 'Ambon'),
(39, 1, 'POLDA', 'POLDA-PAPUA-BARAT-DAYA', 'Polda Papua Barat Daya', 'Kabupaten Sorong'),
(40, 1, 'POLDA', 'POLDA-PAPUA-BARAT', 'Polda Papua Barat', 'Manokwari'),
(41, 1, 'POLDA', 'POLDA-PAPUA-TENGAH', 'Polda Papua Tengah', 'Nabire'),
(42, 1, 'POLDA', 'POLDA-PAPUA', 'Polda Papua', 'Jayapura'),
(100, 7, 'POLRES', 'POLRES-METRO-JAKARTA-BARAT', 'Polres Metro Jakarta Barat', NULL),
(101, 7, 'POLRES', 'POLRES-METRO-JAKARTA-PUSAT', 'Polres Metro Jakarta Pusat', NULL),
(102, 7, 'POLRES', 'POLRES-METRO-JAKARTA-SELATAN', 'Polres Metro Jakarta Selatan', NULL),
(103, 7, 'POLRES', 'POLRES-METRO-JAKARTA-TIMUR', 'Polres Metro Jakarta Timur', NULL),
(104, 7, 'POLRES', 'POLRES-METRO-JAKARTA-UTARA', 'Polres Metro Jakarta Utara', NULL),
(105, 7, 'POLRES', 'POLRES-PELABUHAN-TANJUNG-PRIOK', 'Polres Pelabuhan Tanjung Priok', NULL),
(106, 7, 'POLRES', 'POLRES-KEPULAUAN-SERIBU', 'Polres Kepulauan Seribu', NULL),
(107, 7, 'POLRES', 'POLRES-METRO-TANGERANG-KOTA', 'Polres Metro Tangerang Kota', NULL),
(108, 7, 'POLRES', 'POLRES-TANGERANG-SELATAN', 'Polres Tangerang Selatan', NULL),
(109, 7, 'POLRES', 'POLRESTA-BANDARA-INTERNASIONAL-SOEKARNO-HATTA', 'Polresta Bandara Internasional Soekarno-Hatta', NULL),
(110, 7, 'POLRES', 'POLRES-METRO-BEKASI-KOTA', 'Polres Metro Bekasi Kota', NULL),
(111, 7, 'POLRES', 'POLRES-METRO-BEKASI', 'Polres Metro Bekasi', NULL),
(112, 7, 'POLRES', 'POLRES-METRO-DEPOK', 'Polres Metro Depok', NULL),
(113, 10, 'POLRES', 'POLRESTABES-SURABAYA', 'Polrestabes Surabaya', NULL),
(114, 10, 'POLRES', 'POLRESTA-BANYUWANGI', 'Polresta Banyuwangi', NULL),
(115, 10, 'POLRES', 'POLRESTA-MALANG-KOTA', 'Polresta Malang Kota', NULL),
(116, 10, 'POLRES', 'POLRESTA-SIDOARJO', 'Polresta Sidoarjo', NULL),
(117, 10, 'POLRES', 'POLRESTA-SUMENEP', 'Polresta Sumenep', NULL),
(118, 10, 'POLRES', 'POLRESTA-TUBAN', 'Polresta Tuban', NULL),
(119, 10, 'POLRES', 'POLRES-BANGKALAN', 'Polres Bangkalan', NULL),
(120, 10, 'POLRES', 'POLRES-BATU', 'Polres Batu', NULL),
(121, 10, 'POLRES', 'POLRES-BLITAR', 'Polres Blitar', NULL),
(122, 10, 'POLRES', 'POLRES-BLITAR-KOTA', 'Polres Blitar Kota', NULL),
(123, 10, 'POLRES', 'POLRES-BOJONEGORO', 'Polres Bojonegoro', NULL),
(124, 10, 'POLRES', 'POLRES-BONDOWOSO', 'Polres Bondowoso', NULL),
(125, 10, 'POLRES', 'POLRES-GRESIK', 'Polres Gresik', NULL),
(126, 10, 'POLRES', 'POLRES-JEMBER', 'Polres Jember', NULL),
(127, 10, 'POLRES', 'POLRES-JOMBANG', 'Polres Jombang', NULL),
(128, 10, 'POLRES', 'POLRES-KEDIRI', 'Polres Kediri', NULL),
(129, 10, 'POLRES', 'POLRES-KEDIRI-KOTA', 'Polres Kediri Kota', NULL),
(130, 10, 'POLRES', 'POLRES-LAMONGAN', 'Polres Lamongan', NULL),
(131, 10, 'POLRES', 'POLRES-LUMAJANG', 'Polres Lumajang', NULL),
(132, 10, 'POLRES', 'POLRES-MADIUN', 'Polres Madiun', NULL),
(133, 10, 'POLRES', 'POLRES-MADIUN-KOTA', 'Polres Madiun Kota', NULL),
(134, 10, 'POLRES', 'POLRES-MAGETAN', 'Polres Magetan', NULL),
(135, 10, 'POLRES', 'POLRES-MALANG', 'Polres Malang', NULL),
(136, 10, 'POLRES', 'POLRES-MOJOKERTO', 'Polres Mojokerto', NULL),
(137, 10, 'POLRES', 'POLRES-MOJOKERTO-KOTA', 'Polres Mojokerto Kota', NULL),
(138, 10, 'POLRES', 'POLRES-NGANJUK', 'Polres Nganjuk', NULL),
(139, 10, 'POLRES', 'POLRES-NGAWI', 'Polres Ngawi', NULL),
(140, 10, 'POLRES', 'POLRES-PACITAN', 'Polres Pacitan', NULL),
(141, 10, 'POLRES', 'POLRES-PAMEKASAN', 'Polres Pamekasan', NULL),
(142, 10, 'POLRES', 'POLRES-PASURUAN', 'Polres Pasuruan', NULL),
(143, 10, 'POLRES', 'POLRES-PASURUAN-KOTA', 'Polres Pasuruan Kota', NULL),
(144, 10, 'POLRES', 'POLRES-PELABUHAN-TANJUNG-PERAK', 'Polres Pelabuhan Tanjung Perak', NULL),
(145, 10, 'POLRES', 'POLRES-PONOROGO', 'Polres Ponorogo', NULL),
(146, 10, 'POLRES', 'POLRES-PROBOLINGGO', 'Polres Probolinggo', NULL),
(147, 10, 'POLRES', 'POLRES-PROBOLINGGO-KOTA', 'Polres Probolinggo Kota', NULL),
(148, 10, 'POLRES', 'POLRES-SAMPANG', 'Polres Sampang', NULL),
(149, 10, 'POLRES', 'POLRES-SITUBONDO', 'Polres Situbondo', NULL),
(150, 10, 'POLRES', 'POLRES-TRENGGALEK', 'Polres Trenggalek', NULL),
(151, 10, 'POLRES', 'POLRES-TULUNGAGUNG', 'Polres Tulungagung', NULL),
(152, 8, 'POLRES', 'POLRESTABES-BANDUNG', 'Polrestabes Bandung', NULL),
(153, 8, 'POLRES', 'POLRESTA-BANDUNG', 'Polresta Bandung', NULL),
(154, 8, 'POLRES', 'POLRESTA-BOGOR-KOTA', 'Polresta Bogor Kota', NULL),
(155, 8, 'POLRES', 'POLRESTA-CIREBON', 'Polresta Cirebon', NULL),
(156, 8, 'POLRES', 'POLRESTA-KARAWANG', 'Polresta Karawang', NULL),
(157, 8, 'POLRES', 'POLRES-BANJAR', 'Polres Banjar', NULL),
(158, 8, 'POLRES', 'POLRES-BOGOR', 'Polres Bogor', NULL),
(159, 8, 'POLRES', 'POLRES-CIAMIS', 'Polres Ciamis', NULL),
(160, 8, 'POLRES', 'POLRES-CIANJUR', 'Polres Cianjur', NULL),
(161, 8, 'POLRES', 'POLRES-CIMAHI', 'Polres Cimahi', NULL),
(162, 8, 'POLRES', 'POLRES-CIREBON-KOTA', 'Polres Cirebon Kota', NULL),
(163, 8, 'POLRES', 'POLRES-GARUT', 'Polres Garut', NULL),
(164, 8, 'POLRES', 'POLRES-INDRAMAYU', 'Polres Indramayu', NULL),
(165, 8, 'POLRES', 'POLRES-KUNINGAN', 'Polres Kuningan', NULL),
(166, 8, 'POLRES', 'POLRES-MAJALENGKA', 'Polres Majalengka', NULL),
(167, 8, 'POLRES', 'POLRES-PANGANDARAN', 'Polres Pangandaran', NULL),
(168, 8, 'POLRES', 'POLRES-PURWAKARTA', 'Polres Purwakarta', NULL),
(169, 8, 'POLRES', 'POLRES-SUBANG', 'Polres Subang', NULL),
(170, 8, 'POLRES', 'POLRES-SUKABUMI', 'Polres Sukabumi', NULL),
(171, 8, 'POLRES', 'POLRES-SUKABUMI-KOTA', 'Polres Sukabumi Kota', NULL),
(172, 8, 'POLRES', 'POLRES-SUMEDANG', 'Polres Sumedang', NULL),
(173, 8, 'POLRES', 'POLRES-TASIKMALAYA', 'Polres Tasikmalaya', NULL),
(174, 8, 'POLRES', 'POLRES-TASIKMALAYA-KOTA', 'Polres Tasikmalaya Kota', NULL),
(175, 9, 'POLRES', 'POLRESTABES-SEMARANG', 'Polrestabes Semarang', NULL),
(176, 9, 'POLRES', 'POLRES-SEMARANG', 'Polres Semarang', NULL),
(177, 9, 'POLRES', 'POLRES-KENDAL', 'Polres Kendal', NULL),
(178, 9, 'POLRES', 'POLRESTA-BANYUMAS', 'Polresta Banyumas', NULL),
(179, 9, 'POLRES', 'POLRES-BANJARNEGARA', 'Polres Banjarnegara', NULL),
(180, 9, 'POLRES', 'POLRES-PURBALINGGA', 'Polres Purbalingga', NULL),
(181, 9, 'POLRES', 'POLRESTA-BATANG', 'Polresta Batang', NULL),
(182, 9, 'POLRES', 'POLRES-PEKALONGAN', 'Polres Pekalongan', NULL),
(183, 9, 'POLRES', 'POLRES-PEKALONGAN-KOTA', 'Polres Pekalongan Kota', NULL),
(184, 9, 'POLRES', 'POLRES-PEMALANG', 'Polres Pemalang', NULL),
(185, 9, 'POLRES', 'POLRESTA-CILACAP', 'Polresta Cilacap', NULL),
(186, 9, 'POLRES', 'POLRES-KEBUMEN', 'Polres Kebumen', NULL),
(187, 9, 'POLRES', 'POLRES-PURWOREJO', 'Polres Purworejo', NULL),
(188, 9, 'POLRES', 'POLRESTA-MAGELANG', 'Polresta Magelang', NULL),
(189, 9, 'POLRES', 'POLRES-MAGELANG-KOTA', 'Polres Magelang Kota', NULL),
(190, 9, 'POLRES', 'POLRES-TEMANGGUNG', 'Polres Temanggung', NULL),
(191, 9, 'POLRES', 'POLRES-WONOSOBO', 'Polres Wonosobo', NULL),
(192, 9, 'POLRES', 'POLRESTA-SURAKARTA', 'Polresta Surakarta', NULL),
(193, 9, 'POLRES', 'POLRES-BOYOLALI', 'Polres Boyolali', NULL),
(194, 9, 'POLRES', 'POLRES-KARANGANYAR', 'Polres Karanganyar', NULL),
(195, 9, 'POLRES', 'POLRES-SRAGEN', 'Polres Sragen', NULL),
(196, 9, 'POLRES', 'POLRES-SUKOHARJO', 'Polres Sukoharjo', NULL),
(197, 9, 'POLRES', 'POLRES-WONOGIRI', 'Polres Wonogiri', NULL),
(198, 9, 'POLRES', 'POLRESTA-KLATEN', 'Polresta Klaten', NULL),
(199, 9, 'POLRES', 'POLRESTA-PATI', 'Polresta Pati', NULL),
(200, 9, 'POLRES', 'POLRES-DEMAK', 'Polres Demak', NULL),
(201, 9, 'POLRES', 'POLRES-GROBOGAN', 'Polres Grobogan', NULL),
(202, 9, 'POLRES', 'POLRES-JEPARA', 'Polres Jepara', NULL),
(203, 9, 'POLRES', 'POLRES-KUDUS', 'Polres Kudus', NULL),
(204, 9, 'POLRES', 'POLRES-REMBANG', 'Polres Rembang', NULL),
(205, 9, 'POLRES', 'POLRES-BLORA', 'Polres Blora', NULL),
(206, 9, 'POLRES', 'POLRES-BREBES', 'Polres Brebes', NULL),
(207, 9, 'POLRES', 'POLRES-TEGAL', 'Polres Tegal', NULL),
(208, 9, 'POLRES', 'POLRES-TEGAL-KOTA', 'Polres Tegal Kota', NULL),
(209, 9, 'POLRES', 'POLRESTA-SALATIGA', 'Polresta Salatiga', NULL),
(210, 11, 'POLRES', 'POLRESTABES-MEDAN', 'Polrestabes Medan', NULL),
(211, 11, 'POLRES', 'POLRESTA-DELI-SERDANG', 'Polresta Deli Serdang', NULL),
(212, 11, 'POLRES', 'POLRES-ASAHAN', 'Polres Asahan', NULL),
(213, 11, 'POLRES', 'POLRES-BATU-BARA', 'Polres Batu Bara', NULL),
(214, 11, 'POLRES', 'POLRES-BINJAI', 'Polres Binjai', NULL),
(215, 11, 'POLRES', 'POLRES-DAIRI', 'Polres Dairi', NULL),
(216, 11, 'POLRES', 'POLRES-HUMBANG-HASUNDUTAN', 'Polres Humbang Hasundutan', NULL),
(217, 11, 'POLRES', 'POLRES-KARO', 'Polres Karo', NULL),
(218, 11, 'POLRES', 'POLRES-LABUHANBATU', 'Polres Labuhanbatu', NULL),
(219, 11, 'POLRES', 'POLRES-LABUHANBATU-SELATAN', 'Polres Labuhanbatu Selatan', NULL),
(220, 11, 'POLRES', 'POLRES-LANGKAT', 'Polres Langkat', NULL),
(221, 11, 'POLRES', 'POLRES-MANDAILING-NATAL', 'Polres Mandailing Natal', NULL),
(222, 11, 'POLRES', 'POLRES-NIAS', 'Polres Nias', NULL),
(223, 11, 'POLRES', 'POLRES-NIAS-SELATAN', 'Polres Nias Selatan', NULL),
(224, 11, 'POLRES', 'POLRES-PADANG-LAWAS', 'Polres Padang Lawas', NULL),
(225, 11, 'POLRES', 'POLRES-PADANG-LAWAS-UTARA', 'Polres Padang Lawas Utara', NULL),
(226, 11, 'POLRES', 'POLRES-PADANG-SIDIMPUAN', 'Polres Padang Sidimpuan', NULL),
(227, 11, 'POLRES', 'POLRES-PAKPAK-BHARAT', 'Polres Pakpak Bharat', NULL),
(228, 11, 'POLRES', 'POLRES-PELABUHAN-BELAWAN', 'Polres Pelabuhan Belawan', NULL),
(229, 11, 'POLRES', 'POLRES-PEMATANGSIANTAR', 'Polres Pematangsiantar', NULL),
(230, 11, 'POLRES', 'POLRES-SAMOSIR', 'Polres Samosir', NULL),
(231, 11, 'POLRES', 'POLRES-SERDANG-BEDAGAI', 'Polres Serdang Bedagai', NULL),
(232, 11, 'POLRES', 'POLRES-SIBOLGA', 'Polres Sibolga', NULL),
(233, 11, 'POLRES', 'POLRES-SIMALUNGUN', 'Polres Simalungun', NULL),
(234, 11, 'POLRES', 'POLRES-TANJUNGBALAI', 'Polres Tanjungbalai', NULL),
(235, 11, 'POLRES', 'POLRES-TAPANULI-SELATAN', 'Polres Tapanuli Selatan', NULL),
(236, 11, 'POLRES', 'POLRES-TAPANULI-TENGAH', 'Polres Tapanuli Tengah', NULL),
(237, 11, 'POLRES', 'POLRES-TAPANULI-UTARA', 'Polres Tapanuli Utara', NULL),
(238, 11, 'POLRES', 'POLRES-TEBING-TINGGI', 'Polres Tebing Tinggi', NULL),
(239, 11, 'POLRES', 'POLRES-TOBA', 'Polres Toba', NULL),
(1000, 113, 'POLSEK', 'POLSEK-SBY-BENOWO', 'Polsek Benowo', NULL),
(1001, 113, 'POLSEK', 'POLSEK-SBY-BUBUTAN', 'Polsek Bubutan', NULL),
(1002, 113, 'POLSEK', 'POLSEK-SBY-DUKUH-PAKIS', 'Polsek Dukuh Pakis', NULL),
(1003, 113, 'POLSEK', 'POLSEK-SBY-GAYUNGAN', 'Polsek Gayungan', NULL),
(1004, 113, 'POLSEK', 'POLSEK-SBY-GENTENG', 'Polsek Genteng', NULL),
(1005, 113, 'POLSEK', 'POLSEK-SBY-GUBENG', 'Polsek Gubeng', NULL),
(1006, 113, 'POLSEK', 'POLSEK-SBY-GUNUNG-ANYAR', 'Polsek Gunung Anyar', NULL),
(1007, 113, 'POLSEK', 'POLSEK-SBY-JAMBANGAN', 'Polsek Jambangan', NULL),
(1008, 113, 'POLSEK', 'POLSEK-SBY-KARANG-PILANG', 'Polsek Karang Pilang', NULL),
(1009, 113, 'POLSEK', 'POLSEK-SBY-LAKARSANTRI', 'Polsek Lakarsantri', NULL),
(1010, 113, 'POLSEK', 'POLSEK-SBY-MULYOREJO', 'Polsek Mulyorejo', NULL),
(1011, 113, 'POLSEK', 'POLSEK-SBY-PAKAL', 'Polsek Pakal', NULL),
(1012, 113, 'POLSEK', 'POLSEK-SBY-RUNGKUT', 'Polsek Rungkut', NULL),
(1013, 113, 'POLSEK', 'POLSEK-SBY-SAWAHAN', 'Polsek Sawahan', NULL),
(1014, 113, 'POLSEK', 'POLSEK-SBY-SIMOKERTO', 'Polsek Simokerto', NULL),
(1015, 113, 'POLSEK', 'POLSEK-SBY-SUKOLILO', 'Polsek Sukolilo', NULL),
(1016, 113, 'POLSEK', 'POLSEK-SBY-SUKOMANUNGGAL', 'Polsek Sukomanunggal', NULL),
(1017, 113, 'POLSEK', 'POLSEK-SBY-TAMBAKSARI', 'Polsek Tambaksari', NULL),
(1018, 113, 'POLSEK', 'POLSEK-SBY-TANDES', 'Polsek Tandes', NULL),
(1019, 113, 'POLSEK', 'POLSEK-SBY-TEGALSARI', 'Polsek Tegalsari', NULL),
(1020, 113, 'POLSEK', 'POLSEK-SBY-TENGGILIS-MEJOYO', 'Polsek Tenggilis Mejoyo', NULL),
(1021, 113, 'POLSEK', 'POLSEK-SBY-WIYUNG', 'Polsek Wiyung', NULL),
(1022, 113, 'POLSEK', 'POLSEK-SBY-WONOCOLO', 'Polsek Wonocolo', NULL),
(1023, 113, 'POLSEK', 'POLSEK-SBY-WONOKROMO', 'Polsek Wonokromo', NULL)
ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`), `type`=VALUES(`type`), `code`=VALUES(`code`),
    `name`=VALUES(`name`), `address`=VALUES(`address`);

-- 10a. Tingkat & materialized path hierarki (dihitung ulang setiap seeder dijalankan)
UPDATE `satkers` SET `level` = CASE `type` WHEN 'POLSEK' THEN 1 WHEN 'POLRES' THEN 2 WHEN 'POLDA' THEN 3 ELSE 4 END;
UPDATE `satkers` SET `path` = CONCAT('/', `id`, '/') WHERE `parent_id` IS NULL;
UPDATE `satkers` c JOIN `satkers` p ON p.`id` = c.`parent_id` SET c.`path` = CONCAT(p.`path`, c.`id`, '/') WHERE p.`path` IS NOT NULL;
UPDATE `satkers` c JOIN `satkers` p ON p.`id` = c.`parent_id` SET c.`path` = CONCAT(p.`path`, c.`id`, '/') WHERE p.`path` IS NOT NULL;
UPDATE `satkers` c JOIN `satkers` p ON p.`id` = c.`parent_id` SET c.`path` = CONCAT(p.`path`, c.`id`, '/') WHERE p.`path` IS NOT NULL;

-- 11. AKUN DEMO MANAGEMENT POSKO (akun terpisah dari role SIGAP; tingkat mengikuti satker) — NAMA FIKTIF
--     Pusat: admin/operator/executive · Polda Jatim: admin/operator/executive · Polrestabes Surabaya: admin/operator/executive
--     Polsek Benowo & Gubeng (Surabaya) · pembanding lintas wilayah: Polda Metro Jaya, Polres Metro Jakarta Selatan, Polresta Sidoarjo
INSERT INTO `users` (`id`, `nrp`, `nik`, `name`, `email`, `phone`, `password_hash`, `role`, `satker_id`, `rank`, `position`, `is_active`) VALUES
(100, '80100101', NULL, 'Hariyanto Wibisono, S.Kom.', 'posko.admin.pusat@sigap.polri.go.id', '081311110100', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_ADMIN', 1, 'Kompol', 'Admin Posko Pusat', 1),
(101, '90100201', NULL, 'Nadia Kusumawati', 'posko.op.pusat@sigap.polri.go.id', '081311110101', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_OPERATOR', 1, 'Bripka', 'Operator Posko Pusat', 1),
(102, '72100301', NULL, 'Dr. Gunawan Prakoso, M.H.', 'posko.exec.pusat@sigap.polri.go.id', '081311110102', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_EXECUTIVE', 1, 'Irjen Pol', 'Executive Posko Nasional', 1),
(103, '82100401', NULL, 'Teguh Santoso', 'posko.admin.jatim@sigap.polri.go.id', '081311110103', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_ADMIN', 10, 'AKP', 'Admin Posko Polda Jawa Timur', 1),
(104, '91100501', NULL, 'Ratna Sari Dewi', 'posko.op.jatim@sigap.polri.go.id', '081311110104', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_OPERATOR', 10, 'Brigpol', 'Operator Posko Polda Jawa Timur', 1),
(105, '74100601', NULL, 'Agus Salim Nugroho, S.I.K.', 'posko.exec.jatim@sigap.polri.go.id', '081311110105', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_EXECUTIVE', 10, 'Brigjen Pol', 'Executive Posko Polda Jawa Timur', 1),
(106, '86100701', NULL, 'Fajar Hidayatullah', 'posko.admin.surabaya@sigap.polri.go.id', '081311110106', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_ADMIN', 113, 'Iptu', 'Admin Posko Polrestabes Surabaya', 1),
(107, '93100801', NULL, 'Dewi Anggraini', 'posko.op.surabaya@sigap.polri.go.id', '081311110107', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_OPERATOR', 113, 'Bripka', 'Operator Posko Polrestabes Surabaya', 1),
(108, '78100901', NULL, 'Yudi Permana, S.I.K.', 'posko.exec.surabaya@sigap.polri.go.id', '081311110108', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_EXECUTIVE', 113, 'Kombes Pol', 'Executive Posko Polrestabes Surabaya', 1),
(109, '95101001', NULL, 'Andika Pratama', 'posko.op.benowo@sigap.polri.go.id', '081311110109', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_OPERATOR', 1000, 'Briptu', 'Operator Posko Polsek Benowo', 1),
(110, '96101101', NULL, 'Intan Permatasari', 'posko.op.gubeng@sigap.polri.go.id', '081311110110', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_OPERATOR', 1005, 'Bripda', 'Operator Posko Polsek Gubeng', 1),
(111, '92101201', NULL, 'Rizky Ramadhan', 'posko.op.metro@sigap.polri.go.id', '081311110111', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_OPERATOR', 7, 'Brigpol', 'Operator Posko Polda Metro Jaya', 1),
(112, '94101301', NULL, 'Putri Ayuningtyas', 'posko.op.jaksel@sigap.polri.go.id', '081311110112', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_OPERATOR', 102, 'Briptu', 'Operator Posko Polres Metro Jakarta Selatan', 1),
(113, '93101401', NULL, 'Wahyu Kurniawan', 'posko.op.sidoarjo@sigap.polri.go.id', '081311110113', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_POSKO_OPERATOR', 116, 'Bripka', 'Operator Posko Polresta Sidoarjo', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `email`=VALUES(`email`), `role`=VALUES(`role`), `satker_id`=VALUES(`satker_id`),
    `rank`=VALUES(`rank`), `position`=VALUES(`position`), `is_active`=VALUES(`is_active`);

-- 12. JENIS LAPORAN POSKO (CONTOH; dapat diubah dari menu Jenis Laporan) & ISIANNYA
INSERT INTO `posko_report_types` (`id`, `code`, `name`, `kind`, `period_unit`, `deadline_offset_days`, `deadline_time`, `deadline_hours`, `requires_approval`, `allows_nil`, `applies_to_levels`, `description`, `sort_order`, `is_active`) VALUES
(1, 'LAP-HARIAN', 'Laporan Harian Posko', 'ROUTINE', 'DAY', 0, '23:59:00', NULL, 1, 1, '1,2,3', 'Situasi kamtibmas & kegiatan posko dalam satu hari; tenggat pukul 23.59 WIB hari yang sama.', 10, 1),
(2, 'LAP-MINGGUAN', 'Laporan Periodik Mingguan', 'PERIODIC', 'WEEK', 1, '12:00:00', NULL, 1, 1, '1,2,3', 'Rekap kegiatan & tren kejadian Senin–Minggu; tenggat Senin berikutnya pukul 12.00 WIB.', 20, 1),
(3, 'LAP-KEJADIAN-KHUSUS', 'Laporan Kejadian Khusus', 'SPECIAL_EVENT', 'NONE', 0, NULL, 6, 1, 0, '1,2,3', 'Kejadian menonjol yang perlu diketahui pimpinan; wajib dilaporkan maksimal 6 jam setelah kejadian.', 30, 1),
(4, 'LAP-INSIDENTIL', 'Laporan Insidentil', 'INCIDENTAL', 'NONE', 0, NULL, 24, 1, 0, '1,2,3', 'Bencana, unjuk rasa, kebakaran, atau insiden lain; maksimal 24 jam setelah kejadian.', 40, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `kind`=VALUES(`kind`), `period_unit`=VALUES(`period_unit`),
    `deadline_offset_days`=VALUES(`deadline_offset_days`), `deadline_time`=VALUES(`deadline_time`), `deadline_hours`=VALUES(`deadline_hours`),
    `applies_to_levels`=VALUES(`applies_to_levels`), `description`=VALUES(`description`), `sort_order`=VALUES(`sort_order`);

INSERT INTO `posko_report_fields` (`report_type_id`, `field_key`, `label`, `type`, `required`, `options`, `aggregate`, `unit_label`, `sort_order`) VALUES
(1, 'situasi_kamtibmas', 'Situasi kamtibmas', 'select', 1, 'Aman terkendali\nWaspada\nRawan', 'NONE', NULL, 10),
(1, 'jumlah_kejadian', 'Jumlah kejadian menonjol', 'number', 1, NULL, 'SUM', 'kejadian', 20),
(1, 'jumlah_laka_lantas', 'Jumlah kecelakaan lalu lintas', 'number', 1, NULL, 'SUM', 'kejadian', 30),
(1, 'personel_siaga', 'Personel siaga', 'number', 1, NULL, 'SUM', 'personel', 40),
(1, 'kegiatan_patroli', 'Kegiatan patroli', 'number', 0, NULL, 'SUM', 'kali', 50),
(2, 'total_kejadian', 'Total kejadian dalam seminggu', 'number', 1, NULL, 'SUM', 'kejadian', 10),
(2, 'tersangka_diamankan', 'Tersangka diamankan', 'number', 1, NULL, 'SUM', 'orang', 20),
(2, 'kegiatan_preventif', 'Kegiatan preventif (sambang, penyuluhan)', 'number', 1, NULL, 'SUM', 'kegiatan', 30),
(2, 'rata_waktu_respons', 'Rata-rata waktu respons aduan', 'number', 0, NULL, 'AVG', 'menit', 40),
(2, 'catatan_tren', 'Catatan tren & analisis', 'textarea', 0, NULL, 'NONE', NULL, 50),
(3, 'korban_meninggal', 'Korban meninggal', 'number', 1, NULL, 'SUM', 'orang', 10),
(3, 'korban_luka', 'Korban luka', 'number', 1, NULL, 'SUM', 'orang', 20),
(3, 'estimasi_kerugian', 'Estimasi kerugian', 'number', 0, NULL, 'SUM', 'Rp', 30),
(3, 'status_situasi', 'Status situasi', 'select', 1, 'Terkendali\nBelum terkendali', 'NONE', NULL, 40),
(4, 'jenis_insiden', 'Jenis insiden', 'select', 1, 'Bencana alam\nUnjuk rasa\nKebakaran\nKonflik sosial\nLainnya', 'NONE', NULL, 10),
(4, 'warga_terdampak', 'Warga terdampak', 'number', 1, NULL, 'SUM', 'jiwa', 20),
(4, 'personel_dikerahkan', 'Personel dikerahkan', 'number', 1, NULL, 'SUM', 'personel', 30),
(4, 'kebutuhan_logistik', 'Kebutuhan bantuan logistik', 'textarea', 0, NULL, 'NONE', NULL, 40)
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `type`=VALUES(`type`), `required`=VALUES(`required`), `options`=VALUES(`options`),
    `aggregate`=VALUES(`aggregate`), `unit_label`=VALUES(`unit_label`), `sort_order`=VALUES(`sort_order`);

-- 13. CONTOH LAPORAN POSKO (alur berjenjang Polsek → Polrestabes Surabaya → Polda Jatim). Waktu relatif terhadap NOW().
--     Laporan contoh dibuat ulang setiap seeder dijalankan (nomor PSK/... ber-urutan 9xxx = demo).
--     FOREIGN_KEY_CHECKS dimatikan selama seeder, jadi data turunan dihapus eksplisit (bukan via CASCADE).
DELETE FROM `posko_report_history` WHERE `report_id` BETWEEN 1 AND 99;
DELETE FROM `posko_report_versions` WHERE `report_id` BETWEEN 1 AND 99;
DELETE FROM `posko_report_values` WHERE `report_id` BETWEEN 1 AND 99;
DELETE FROM `posko_report_attachments` WHERE `report_id` BETWEEN 1 AND 99;
DELETE FROM `posko_reports` WHERE `id` BETWEEN 1 AND 99;
INSERT INTO `posko_reports` (`id`, `report_number`, `report_type_id`, `satker_id`, `holder_satker_id`, `period_start`, `period_end`, `period_key`, `event_time`, `region`, `situation_summary`, `description`, `obstacles`, `follow_up`, `assistance_needed`, `priority`, `status`, `version`, `deadline_at`, `submitted_at`, `approved_by`, `approved_at`, `forwarded_at`, `created_by`, `updated_by`) VALUES
-- 1: Polsek Benowo, harian kemarin — menunggu verifikasi Polrestabes Surabaya
(1, CONCAT('PSK/POLSEK-SBY-BENOWO/', DATE_FORMAT(CURDATE() - INTERVAL 1 DAY, '%Y%m'), '/9001'), 1, 1000, 113, CURDATE() - INTERVAL 1 DAY, CURDATE() - INTERVAL 1 DAY, CURDATE() - INTERVAL 1 DAY, NULL,
    'Kecamatan Benowo', 'Situasi aman terkendali; satu kejadian curanmor di permukiman Romokalisari.',
    'Patroli malam 3 kali di jalur Benowo–Romokalisari. Satu laporan curanmor ditindaklanjuti olah TKP oleh unit reskrim.',
    'Penerangan jalan di beberapa titik padam.', 'Koordinasi dengan kelurahan untuk perbaikan PJU.', NULL,
    'NORMAL', 'SUBMITTED', 1, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 23:59:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 21:40:00'), NULL, NULL, NULL, 109, 109),
-- 2: Polsek Benowo, harian hari ini — masih draft
(2, NULL, 1, 1000, NULL, CURDATE(), CURDATE(), CURDATE(), NULL,
    'Kecamatan Benowo', 'Draft laporan hari ini.', NULL, NULL, NULL, NULL,
    'NORMAL', 'DRAFT', 1, CONCAT(CURDATE(), ' 23:59:00'), NULL, NULL, NULL, NULL, 109, 109),
-- 3: Polsek Gubeng, kejadian khusus MENDESAK — menunggu verifikasi Polrestabes Surabaya
(3, CONCAT('PSK/POLSEK-SBY-GUBENG/', DATE_FORMAT(CURDATE(), '%Y%m'), '/9001'), 3, 1005, 113, NULL, NULL, NULL, NOW() - INTERVAL 5 HOUR,
    'Jl. Raya Gubeng', 'Kebakaran ruko dua lantai, api berhasil dipadamkan.',
    'Kebakaran ruko pukul dini hari diduga korsleting listrik. 4 unit damkar dikerahkan, lalu lintas dialihkan sementara.',
    'Akses mobil damkar sempit akibat parkir liar.', 'Penyelidikan penyebab bersama Inafis; penertiban parkir liar.',
    'Dukungan personel pengaturan lalu lintas dari Satlantas.', 'HIGH', 'SUBMITTED', 1, NOW() + INTERVAL 1 HOUR, NOW() - INTERVAL 3 HOUR, NULL, NULL, NULL, 110, 110),
-- 4: Polsek Gubeng, harian 2 hari lalu — disetujui & diteruskan Polrestabes ke Polda Jatim
(4, CONCAT('PSK/POLSEK-SBY-GUBENG/', DATE_FORMAT(CURDATE() - INTERVAL 2 DAY, '%Y%m'), '/9002'), 1, 1005, 10, CURDATE() - INTERVAL 2 DAY, CURDATE() - INTERVAL 2 DAY, CURDATE() - INTERVAL 2 DAY, NULL,
    'Kecamatan Gubeng', 'Situasi aman; pengamanan kegiatan car free day berjalan lancar.',
    'Pengamanan CFD dengan 12 personel, patroli dialogis di Taman Bungkul. Tidak ada kejadian menonjol.',
    NULL, NULL, NULL, 'NORMAL', 'FORWARDED', 1, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 23:59:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 20:15:00'),
    107, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 09:00:00'), 110, 107),
-- 5: Polsek Gubeng, harian 3 hari lalu — dikembalikan Polrestabes untuk perbaikan
(5, CONCAT('PSK/POLSEK-SBY-GUBENG/', DATE_FORMAT(CURDATE() - INTERVAL 3 DAY, '%Y%m'), '/9003'), 1, 1005, NULL, CURDATE() - INTERVAL 3 DAY, CURDATE() - INTERVAL 3 DAY, CURDATE() - INTERVAL 3 DAY, NULL,
    'Kecamatan Gubeng', 'Situasi aman terkendali.', 'Patroli rutin.', NULL, NULL, NULL,
    'NORMAL', 'NEEDS_REVISION', 1, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 23:59:00'), CONCAT(CURDATE() - INTERVAL 3 DAY, ' 22:05:00'), NULL, NULL, NULL, 110, 107);

INSERT INTO `posko_report_values` (`report_id`, `field_key`, `value_number`, `value_text`) VALUES
(1, 'situasi_kamtibmas', NULL, 'Aman terkendali'), (1, 'jumlah_kejadian', 1, NULL), (1, 'jumlah_laka_lantas', 0, NULL),
(1, 'personel_siaga', 18, NULL), (1, 'kegiatan_patroli', 3, NULL),
(2, 'situasi_kamtibmas', NULL, 'Aman terkendali'), (2, 'personel_siaga', 16, NULL),
(3, 'korban_meninggal', 0, NULL), (3, 'korban_luka', 1, NULL), (3, 'estimasi_kerugian', 350000000, NULL),
(3, 'status_situasi', NULL, 'Terkendali'),
(4, 'situasi_kamtibmas', NULL, 'Aman terkendali'), (4, 'jumlah_kejadian', 0, NULL), (4, 'jumlah_laka_lantas', 1, NULL),
(4, 'personel_siaga', 12, NULL), (4, 'kegiatan_patroli', 4, NULL),
(5, 'situasi_kamtibmas', NULL, 'Aman terkendali'), (5, 'jumlah_kejadian', 0, NULL), (5, 'jumlah_laka_lantas', 0, NULL),
(5, 'personel_siaga', 14, NULL);

-- Riwayat tindakan contoh (aktor: 107 Operator Polrestabes Surabaya, 109 Operator Benowo, 110 Operator Gubeng)
INSERT INTO `posko_report_history` (`report_id`, `actor_id`, `actor_satker_id`, `action`, `status_from`, `status_to`, `holder_from`, `holder_to`, `version`, `notes`, `created_at`) VALUES
(1, 109, 1000, 'create', NULL, 'DRAFT', NULL, NULL, 1, NULL, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 20:50:00')),
(1, 109, 1000, 'submit', 'DRAFT', 'SUBMITTED', NULL, 113, 1, NULL, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 21:40:00')),
(2, 109, 1000, 'create', NULL, 'DRAFT', NULL, NULL, 1, NULL, NOW() - INTERVAL 1 HOUR),
(3, 110, 1005, 'create', NULL, 'DRAFT', NULL, NULL, 1, NULL, NOW() - INTERVAL 4 HOUR),
(3, 110, 1005, 'submit', 'DRAFT', 'SUBMITTED', NULL, 113, 1, NULL, NOW() - INTERVAL 3 HOUR),
(4, 110, 1005, 'create', NULL, 'DRAFT', NULL, NULL, 1, NULL, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 19:30:00')),
(4, 110, 1005, 'submit', 'DRAFT', 'SUBMITTED', NULL, 113, 1, NULL, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 20:15:00')),
(4, 107, 113, 'approve', 'SUBMITTED', 'APPROVED', 113, 113, 1, 'Data sesuai.', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00')),
(4, 107, 113, 'forward', 'APPROVED', 'FORWARDED', 113, 10, 1, NULL, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 09:00:00')),
(5, 110, 1005, 'create', NULL, 'DRAFT', NULL, NULL, 1, NULL, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 21:30:00')),
(5, 110, 1005, 'submit', 'DRAFT', 'SUBMITTED', NULL, 113, 1, NULL, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 22:05:00')),
(5, 107, 113, 'return', 'SUBMITTED', 'NEEDS_REVISION', 113, NULL, 1, 'Uraian kegiatan terlalu singkat. Lengkapi lokasi patroli dan jumlah kegiatan patroli.', CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:10:00'));

SET FOREIGN_KEY_CHECKS = 1;
