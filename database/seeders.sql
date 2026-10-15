-- =============================================================================
-- SEEDERS: SIGAP-POLDA INITIAL DATA & DEMO ACCOUNTS
-- Default Password untuk semua akun dinas: password123
-- =============================================================================

USE `sigap_polda`;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. SEED DATA SATKER
INSERT INTO `satkers` (`id`, `parent_id`, `type`, `code`, `name`, `address`, `phone`, `latitude`, `longitude`) VALUES
(1, NULL, 'POLDA', 'POLDA-HQ', 'Markas Kepolisian Daerah (Command Center)', 'Jl. Jenderal Sudirman Kav. 55', '021-5234000', -6.22350000, 106.80990000),
(2, 1, 'DIREKTORAT', 'DIT-KRIMUM', 'Ditreskrimum (Direktorat Reserse Kriminal Umum)', 'Gedung Utama Lt. 3 Mapolda', '021-5234101', -6.22355000, 106.81000000),
(3, 1, 'DIREKTORAT', 'DIT-KRIMSUS', 'Ditreskrimsus & Siber (Kriminal Khusus)', 'Gedung Promoter Lt. 4 Mapolda', '021-5234102', -6.22360000, 106.81010000),
(4, 1, 'DIREKTORAT', 'DIT-NARKOBA', 'Ditresnarkoba (Direktorat Reserse Narkoba)', 'Gedung B Lt. 2 Mapolda', '021-5234103', -6.22365000, 106.81020000),
(5, 1, 'DIREKTORAT', 'DIT-LANTAS', 'Ditlantas (Direktorat Lalu Lintas)', 'Jl. MT Haryono Kav. 37-38', '021-5234104', -6.24180000, 106.84850000),
(6, 1, 'DIREKTORAT', 'BID-PROPAM', 'Bidpropam (Profesi & Pengamanan Personil)', 'Gedung Pelayanan Terpadu Lt. 2', '021-5234105', -6.22340000, 106.80980000),
(7, 1, 'POLRES', 'POLRES-JAKPUS', 'Polres Metro Jakarta Pusat', 'Jl. Garuda No. 2 Kemayoran', '021-4246811', -6.16010000, 106.85200000),
(8, 1, 'POLRES', 'POLRES-JAKSEL', 'Polres Metro Jakarta Selatan', 'Jl. Wijaya II No. 42 Kebayoran Baru', '021-7206012', -6.24780000, 106.80150000),
(9, 1, 'POLRES', 'POLRES-JAKBAR', 'Polres Metro Jakarta Barat', 'Jl. Daan Mogot KM 11', '021-5660123', -6.15580000, 106.75540000),
(10, 1, 'POLRES', 'POLRES-JAKTIM', 'Polres Metro Jakarta Timur', 'Jl. Matraman Raya No. 224', '021-8191478', -6.21550000, 106.86310000),
(11, 1, 'POLRES', 'POLRES-JAKUT', 'Polres Metro Jakarta Utara', 'Jl. Yos Sudarso No. 1', '021-4393101', -6.12150000, 106.89200000)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 2. SEED KATEGORI PERKARA
INSERT INTO `report_categories` (`id`, `code`, `name`, `default_satker_type`, `sla_hours`, `icon`, `description`) VALUES
(1, 'CAT-KRIMINAL-UMUM', 'Kriminal Umum (Curanmor, Curat, Curas, Penipuan)', 'DIREKTORAT', 48, 'shield-alert', 'Pencurian, kekerasan fisik, pemerasan, dan penggelapan'),
(2, 'CAT-SIBER-KHUSUS', 'Kejahatan Siber & Kejahatan Finansial Khusus', 'DIREKTORAT', 48, 'cpu', 'Penipuan online, kebocoran data, pemerasan siber, pornografi anak'),
(3, 'CAT-NARKOBA', 'Peredaran & Penyalahgunaan Narkoba', 'DIREKTORAT', 24, 'alert-triangle', 'Informasi peredaran narkotika, tempat pesta sabu/inex'),
(4, 'CAT-LANTAS', 'Lalu Lintas (Kemacetan Ekstrem, Lakalantas, Pungli)', 'DIREKTORAT', 12, 'truck', 'Pelanggaran lalu lintas jalan raya, laka maut, aksi balap liar'),
(5, 'CAT-PROPAM', 'Pengaduan Perilaku Anggota Polri (Dumas Propam)', 'DIREKTORAT', 72, 'user-x', 'Pungli oleh personil, arogansi kewenangan, kekerasan oknum'),
(6, 'CAT-KAMTIBMAS', 'Gangguan Keamanan & Ketertiban Lingkungan (Tawuran/Miras)', 'POLRES', 24, 'users', 'Tawuran warga, geng motor, keresahan ketertiban umum')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 3. SEED AKUN PENGGUNA RESMI (9 ROLES KEPOLISIAN & MASYARAKAT)
-- Hash bcrypt '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O' = password123
INSERT INTO `users` (`id`, `nrp`, `nik`, `name`, `email`, `phone`, `password_hash`, `role`, `satker_id`, `rank`, `position`) VALUES
-- Superadmin IT
(1, '90010101', NULL, 'Bripka Adhi Nugraha, S.Kom', 'superadmin@sigap.polda.go.id', '081211110001', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SUPERADMIN', 1, 'Bripka', 'Administrator Sistem TIK Polda'),
-- Kapolda (Executive)
(2, '70050201', NULL, 'Irjen Pol. Dr. Karyoto, S.I.K., M.H.', 'kapolda@sigap.polda.go.id', '081211110002', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_EXECUTIVE', 1, 'Irjen Pol', 'Kepala Kepolisian Daerah'),
-- Operator SPKT / Command Center
(3, '88030301', NULL, 'Aipda Hendra Gunawan', 'spkt@sigap.polda.go.id', '081211110003', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SPKT_OPERATOR', 1, 'Aipda', 'Operator Verifikator SPKT Polda'),
-- Tim Humas / Siber Medsos
(4, '92080401', NULL, 'Briptu Cindy Permata', 'humas@sigap.polda.go.id', '081211110004', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_CYBER_HUMAS', 1, 'Briptu', 'Petugas Patroli Siber & Humas Medsos'),
-- Kasatker / Dirreskrimum (Pimpinan Satker)
(5, '75020501', NULL, 'Kombes Pol. Wira Satya Triputra', 'dirkrimum@sigap.polda.go.id', '081211110005', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_HEAD', 2, 'Kombes Pol', 'Direktur Reserse Kriminal Umum'),
-- Kanit Satker Ditreskrimum (Admin Satker)
(6, '83090601', NULL, 'Kompol Denny Arifin, S.H.', 'kanit.krimum@sigap.polda.go.id', '081211110006', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_SATKER_ADMIN', 2, 'Kompol', 'Kanit 1 Subdit Jatanras Ditreskrimum'),
-- Penyidik Pelaksana / Petugas Lapangan
(7, '91040701', NULL, 'Ipda Rian Prasetya, S.Tr.K.', 'penyidik.rian@sigap.polda.go.id', '081211110007', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INVESTIGATOR', 2, 'Ipda', 'Penyidik Pembantu Unit Reskrim'),
-- Pengawas Internal (Irwasda / Propam)
(8, '78010801', NULL, 'Kombes Pol. Raden Firman, S.I.K.', 'irwasda@sigap.polda.go.id', '081211110008', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_INTERNAL_AFFAIRS', 6, 'Kombes Pol', 'Auditor Kepolisian Itwasda Polda'),
-- Warga Pelapor Terverifikasi
(9, NULL, '3171012304950001', 'Budi Santoso (Masyarakat)', 'budi.santoso95@gmail.com', '081298765432', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', 'ROLE_CITIZEN', NULL, NULL, 'Wiraswasta')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 4. SEED SAMPLE SOCIAL MEDIA FEEDS (MENTIONS & VIRAL POSTS)
INSERT INTO `social_feeds` (`id`, `platform`, `post_id_str`, `author_username`, `author_name`, `author_avatar`, `post_content`, `post_url`, `media_urls`, `sentiment`, `status`, `converted_report_id`, `created_at`) VALUES
(1, 'TWITTER_X', 'tw_1846192837461', 'doni_pratama', 'Doni Pratama', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100', 'Mohon atensinya @HumasPolda! Barusan terjadi aksi begal motor bersenjata tajam di flyover Pesing Jakbar. Korban luka sabetan dan motor Beat hitam dibawa kabur!', 'https://x.com/doni_pratama/status/1846192837461', '["https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=600"]', 'NEGATIVE_URGENT', 'CONVERTED_TO_TICKET', 1, DATE_SUB(NOW(), INTERVAL 14 HOUR)),
(2, 'INSTAGRAM', 'ig_9482710492819', 'jakartaviral.id', 'Jakarta Viral', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=100', 'Viral di Instagram aksi gerombolan debt collector menghadang mobil keluarga di Jl. Sudirman depan Senayan City dan merampas kunci secara paksa! @poldametrojaya', 'https://instagram.com/p/DB1829aBcd/', '["https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600"]', 'NEGATIVE_URGENT', 'NEW', NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 'TIKTOK', 'tt_7384910284719', 'antonio_news', 'Antonio Warga', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100', 'Ada balap liar tiap malam jam 2 dini hari di kawasan Antasari Jaksel, knalpot brong sangat bising dan mengancam pengguna jalan lain tolong ditertibkan Pak Polisi.', 'https://tiktok.com/@antonio_news/video/7384910284719', NULL, 'NEGATIVE_URGENT', 'NEW', NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR))
ON DUPLICATE KEY UPDATE `author_username`=VALUES(`author_username`);

-- 5. SEED SAMPLE REPORTS (CONTOH KASUS SELESAI, SEDANG PROSES & ATENSI KAPOLDA)
INSERT INTO `reports` (`id`, `ticket_number`, `security_pin`, `source_channel`, `social_feed_id`, `social_post_url`, `reporter_id`, `is_anonymous`, `reporter_name_raw`, `reporter_nik_raw`, `reporter_phone`, `category_id`, `title`, `description`, `incident_date`, `incident_time`, `incident_location`, `latitude`, `longitude`, `current_satker_id`, `assigned_investigator_id`, `status`, `urgency_level`, `is_kapolda_attention`, `kapolda_attention_at`, `sla_deadline`, `created_at`) VALUES
-- Kasus 1: Atensi Kapolda (Begal Pesing dari Medsos X)
(1, 'LP-POLDA-202610-0001', '819203', 'TWITTER_X', 1, 'https://x.com/doni_pratama/status/1846192837461', 9, 0, 'Doni Pratama', '3171012304950001', '081298765432', 1, 'Aksi Curas Pembegalan Bersenjata Tajam di Flyover Pesing', 'Terjadi pembegalan terhadap pemotor Honda Beat hitam B 4521 TZA oleh 4 pelaku boncengan 2 motor menggunakan celurit. Korban mengalami luka di lengan kiri dan saat ini dirawat di RS Sumber Waras.', CURDATE(), '01:30:00', 'Flyover Pesing, Daan Mogot, Grogol Petamburan, Jakarta Barat', -6.15780000, 106.77250000, 2, 7, 'INVESTIGATING', 'HIGH', 1, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR), DATE_SUB(NOW(), INTERVAL 14 HOUR)),

-- Kasus 2: Penipuan Investasi Online (Kriminal Khusus)
(2, 'LP-POLDA-202610-0002', '459102', 'WEB_PORTAL', NULL, NULL, 9, 1, 'Siti Nurhaliza (Disamarkan)', '3175029384710002', '081388776655', 2, 'Penipuan Investasi Trading Robot Berkedok Arisan Online Rp 150 Juta', 'Terlapor atas nama inisial AR menjanjikan keuntungan 25% per bulan melalui aplikasi trading fiktif. Setelah dana ditransfer Rp 150.000.000, kontak terlapor diblokir.', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '14:00:00', 'Kelapa Gading, Jakarta Utara', -6.16050000, 106.90800000, 3, NULL, 'DISPATCHED', 'NORMAL', 0, NULL, DATE_ADD(NOW(), INTERVAL 48 HOUR), DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Kasus 3: Kasus Selesai Berita Acara (Curanmor Kemayoran)
(3, 'LP-POLDA-202610-0003', '201948', 'WEB_PORTAL', NULL, NULL, 9, 0, 'Budi Santoso', '3171012304950001', '081298765432', 1, 'Pencurian Motor Vario di Parkiran Minimarket Kemayoran', 'Sepeda motor Honda Vario 160 putih B 6789 PQR dicuri saat pelapor berbelanja selama 10 menit. Rekaman CCTV minimarket jelas memperlihatkan wajah 2 pelaku kunci T.', DATE_SUB(CURDATE(), INTERVAL 5 DAY), '19:45:00', 'Jl. Garuda No. 12, Kemayoran, Jakarta Pusat', -6.16200000, 106.84500000, 7, 7, 'CLOSED', 'NORMAL', 0, NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY))
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 6. SEED TIMELINE & SP2HP DOKUMEN
INSERT INTO `report_timeline` (`id`, `report_id`, `actor_id`, `status_from`, `status_to`, `title`, `public_notes`, `internal_notes`, `is_sp2hp`, `sp2hp_number`, `created_at`) VALUES
-- Kasus 1 Timelines
(1, 1, 4, 'NEW', 'VERIFIED_SPKT', 'Eskalasi Laporan dari Medsos X', 'Laporan yang diviralkan di akun media sosial X telah divalidasi dan diterbitkan Nomor Registrasi Resmi LP-POLDA-202610-0001.', 'Hasil koordinasi dengan akun @doni_pratama. Rekaman video dan TKP valid.', 0, NULL, DATE_SUB(NOW(), INTERVAL 13 HOUR)),
(2, 1, 3, 'VERIFIED_SPKT', 'DISPATCHED', 'Disposisi Perkara ke Ditreskrimum Polda', 'Laporan telah didisposisikan ke Direktorat Reserse Kriminal Umum (Subdit Jatanras) untuk pengejaran pelaku pembegalan.', 'Disposisi atensi cepat mengingat korban luka bacok.', 0, NULL, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(3, 1, 6, 'DISPATCHED', 'ASSIGNED', 'Penunjukan Penyidik Pelaksana', 'Kanit 1 Jatanras telah menerbitkan Surat Perintah Penugasan (Sprintgas) kepada Ipda Rian Prasetya.', 'Sprintgas No: SP.Gas/108/X/2026/Ditreskrimum.', 0, NULL, DATE_SUB(NOW(), INTERVAL 11 HOUR)),
(4, 1, 2, 'ASSIGNED', 'INVESTIGATING', 'Instruksi Khusus "Atensi Kapolda"', 'Kapolda memberikan instruksi prioritas tinggi kepada Ditreskrimum untuk mengungkap dan menangkap pelaku dalam waktu 1x24 jam.', 'ATENSI KAPOLDA: Koordinasi dengan CCTV Dishub dan Polsek Cengkareng, amankan barang bukti dan segera lumpuhkan pelaku kriminal jalanan yang meresahkan.', 0, NULL, DATE_SUB(NOW(), INTERVAL 9 HOUR)),
(5, 1, 7, 'INVESTIGATING', 'INVESTIGATING', 'Penerbitan SP2HP Elektronik Ke-1 (A1)', 'Penyidik telah melakukan olah TKP di Flyover Pesing, mengamankan rekaman CCTV Dishub di perempatan Daan Mogot, dan memeriksa 2 saksi mata pedagang sekitar.', 'Identitas 2 motor pelaku (Nmax biru dan Beat hitam tanpa plat) telah terdeteksi mengarah ke wilayah Kalideres. Tim opsnal sedang bergerak.', 1, 'SP2HP/012/X/RES.1.8/2026/Ditreskrimum', DATE_SUB(NOW(), INTERVAL 4 HOUR)),

-- Kasus 3 (Kasus Selesai)
(6, 3, 3, 'SUBMITTED', 'VERIFIED_SPKT', 'Verifikasi Berkas Laporan', 'Laporan diterima dan diverifikasi oleh SPKT Polda.', NULL, 0, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7, 3, 7, 'VERIFIED_SPKT', 'CLOSED', 'Kasus Selesai & Pelaku Berhasil Ditangkap', 'Tim Reskrim berhasil menangkap kedua pelaku curanmor beserta barang bukti sepeda motor Vario milik korban dalam keadaan utuh. Motor telah diserahkan kembali dengan Berita Acara Pinjam Pakai.', 'Pelaku residivis a.n Jaka dan Dani diamankan di Kampung Muara Kamal.', 1, 'SP2HP/Final/10/RES.1.8/2026', DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 7. SEED DIRECTIVE KAPOLDA
INSERT INTO `kapolda_directives` (`id`, `report_id`, `created_by`, `directive_text`, `target_satker_id`, `response_notes`, `status`, `created_at`, `responded_at`) VALUES
(1, 1, 2, 'Tangkap komplotan begal bersenjata tajam Pesing ini dalam 1x24 jam. Jangan ada kompromi terhadap aksi kekerasan jalanan di ibukota!', 2, 'Siap Jenderal, Subdit Jatanras Unit 1 telah bergerak mengidentifikasi jalur pelarian pelaku ke arah barat.', 'ACKNOWLEDGED', DATE_SUB(NOW(), INTERVAL 9 HOUR), DATE_SUB(NOW(), INTERVAL 8 HOUR))
ON DUPLICATE KEY UPDATE `directive_text`=VALUES(`directive_text`);

-- 8. SEED FEEDBACK SKM (SURVEI KEPUASAN)
INSERT INTO `report_feedbacks` (`id`, `report_id`, `rating_stars`, `speed_score`, `fairness_score`, `comments`, `created_at`) VALUES
(1, 3, 5, 5, 5, 'Luar biasa respons cepat kepolisian! Motor saya bisa kembali dalam 4 hari tanpa dipungut biaya sepeser pun. Sangat apresiasi keterbukaan SP2HP digitalnya.', DATE_SUB(NOW(), INTERVAL 18 HOUR))
ON DUPLICATE KEY UPDATE `rating_stars`=VALUES(`rating_stars`);

SET FOREIGN_KEY_CHECKS = 1;
