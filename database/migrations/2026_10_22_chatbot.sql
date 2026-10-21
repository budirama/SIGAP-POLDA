-- Chatbot Konsultasi (Asisten Virtual SIGAP) di atas kanal Live Chat
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_22_chatbot.sql
-- Aman dijalankan ulang (idempoten). Isi jawaban dapat diubah lewat menu Basis Pengetahuan Chatbot.

-- 1. Status BOT pada percakapan & pengirim BOT pada pesan
ALTER TABLE `chat_conversations` MODIFY `status` ENUM('BOT', 'WAITING', 'ACTIVE', 'CLOSED') NOT NULL DEFAULT 'WAITING';
ALTER TABLE `chat_messages` MODIFY `sender_type` ENUM('VISITOR', 'AGENT', 'SYSTEM', 'BOT') NOT NULL;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat_conversations' AND COLUMN_NAME = 'is_urgent');
SET @sql := IF(@col = 0,
    "ALTER TABLE `chat_conversations`
        ADD COLUMN `is_urgent` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Terdeteksi kata darurat oleh chatbot' AFTER `status`,
        ADD COLUMN `bot_misses` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pertanyaan yang tidak dipahami chatbot' AFTER `is_urgent`,
        ADD COLUMN `handoff_at` DATETIME NULL COMMENT 'Waktu diserahkan dari chatbot ke petugas' AFTER `bot_misses`",
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chat_messages' AND COLUMN_NAME = 'meta');
SET @sql := IF(@col = 0,
    "ALTER TABLE `chat_messages` ADD COLUMN `meta` TEXT NULL COMMENT 'JSON tombol cepat & tautan untuk pesan chatbot' AFTER `body`",
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. BASIS PENGETAHUAN (intent → jawaban)
CREATE TABLE IF NOT EXISTS `chatbot_intents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(40) NOT NULL UNIQUE,
    `label` VARCHAR(60) NOT NULL COMMENT 'Teks tombol cepat',
    `keywords` TEXT NOT NULL COMMENT 'Kata/frasa kunci dipisah koma',
    `answer` TEXT NOT NULL,
    `link_url` VARCHAR(255) NULL COMMENT 'Path internal (/report/create) atau URL https',
    `link_label` VARCHAR(60) NULL,
    `quick_replies` VARCHAR(255) NULL COMMENT 'Kode intent lanjutan dipisah koma',
    `action` ENUM('NONE', 'HANDOFF', 'EMERGENCY') NOT NULL DEFAULT 'NONE',
    `show_in_menu` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 100,
    `hit_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PERTANYAAN YANG BELUM TERJAWAB (bahan perbaikan basis pengetahuan)
CREATE TABLE IF NOT EXISTS `chatbot_misses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NULL,
    `question` VARCHAR(1000) NOT NULL,
    `resolved` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_misses_open` (`resolved`, `id`),
    FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ISI AWAL BASIS PENGETAHUAN (tidak menimpa jawaban yang sudah diubah admin)
INSERT IGNORE INTO `chatbot_intents`
(`code`, `label`, `keywords`, `answer`, `link_url`, `link_label`, `quick_replies`, `action`, `show_in_menu`, `sort_order`) VALUES
('DARURAT', 'Keadaan darurat',
 'darurat, emergency, minta tolong, tolong saya, sedang terjadi, dibegal, pembegalan, ditodong, penodongan, dirampok, perampokan, diculik, penculikan, pembunuhan, dibunuh, kebakaran, ditusuk, ditembak, pemerkosaan, diperkosa, bunuh diri, kecelakaan parah, sekarat, sos',
 'Jika Anda atau orang lain sedang dalam bahaya, SEGERA hubungi Call Center 110 (gratis, 24 jam). Saya sudah meneruskan percakapan ini ke petugas Command Center sebagai PRIORITAS. Tetap di tempat aman dan sampaikan lokasi Anda sejelas mungkin.',
 'tel:110', 'Telepon 110', NULL, 'EMERGENCY', 0, 1),

('PETUGAS', 'Bicara dengan petugas',
 'petugas, operator, admin, manusia, orang asli, bicara, bicara dengan polisi, hubungi polisi, cs, customer service, live agent',
 'Baik, percakapan Anda saya teruskan ke petugas Command Center SPKT. Mohon tunggu sebentar, petugas akan segera membalas di jendela ini.',
 NULL, NULL, NULL, 'HANDOFF', 1, 90),

('SALAM', 'Halo',
 'halo, hai, hallo, hi, selamat pagi, selamat siang, selamat sore, selamat malam, assalamualaikum, permisi, pagi, siang, malam',
 'Halo, saya Asisten Virtual SIGAP Polri. Silakan pilih topik di bawah atau tuliskan pertanyaan Anda.',
 NULL, NULL, 'CARA_LAPOR,CEK_STATUS,HILANG_TEMU,PETUGAS', 'NONE', 0, 100),

('CARA_LAPOR', 'Cara membuat laporan',
 'cara lapor, cara melapor, cara melaporkan, buat laporan, bikin laporan, membuat laporan, laporan, lapor, lapor polisi, laporan polisi, pengaduan, aduan',
 'Anda dapat membuat laporan secara online tanpa datang ke kantor polisi:\n1. Buka formulir Pengaduan Online.\n2. Isi identitas (NIK, nama, nomor HP), kategori, kronologi, dan lokasi kejadian.\n3. Lampirkan bukti foto/video bila ada.\n4. Simpan Nomor Tiket dan PIN 6 digit untuk memantau perkembangan.\nIdentitas pelapor dapat dirahasiakan (whistleblower).',
 '/report/create', 'Buka formulir laporan', 'CEK_STATUS,WHISTLEBLOWER,PETUGAS', 'NONE', 1, 10),

('CEK_STATUS', 'Cek status laporan',
 'cek status, status laporan, cek laporan, laporan saya, status, lacak, tracking, perkembangan laporan, sp2hp, sudah sampai mana, tiket saya, nomor tiket, progres laporan',
 'Status laporan dapat dipantau di halaman Lacak SP2HP dengan Nomor Tiket (LP-POLRI-…) dan PIN 6 digit. Di sana Anda juga dapat berkomunikasi dengan penyidik dan mencetak SP2HP. Untuk laporan Kehilangan dan Temuan (HT-…), gunakan halaman Cek Status Kehilangan dan Temuan.',
 '/report/track', 'Lacak laporan', 'HILANG_TEMU,PETUGAS', 'NONE', 1, 20),

('HILANG_TEMU', 'Kehilangan / menemukan barang',
 'hilang, kehilangan, dicuri, kecurian, menemukan, temuan, barang hilang, dompet hilang, hp hilang, handphone hilang, ktp hilang, dokumen hilang, sktlk, surat kehilangan, surat keterangan hilang, hewan hilang, kucing hilang, anjing hilang',
 'Laporkan kehilangan atau temuan (kendaraan, orang, barang berharga, dokumen, hewan) di layanan Kehilangan dan Temuan. Sistem otomatis mencocokkan laporan kehilangan dengan temuan. Setelah laporan kehilangan diverifikasi petugas, Anda dapat mencetak Surat Keterangan Tanda Lapor Kehilangan (SKTLK).',
 '/hilangtemu', 'Buka Kehilangan dan Temuan', 'KENDARAAN_HILANG,ORANG_HILANG,PETUGAS', 'NONE', 1, 30),

('KENDARAAN_HILANG', 'Kendaraan hilang/dicuri',
 'motor hilang, mobil hilang, kendaraan hilang, curanmor, motor dicuri, mobil dicuri, kendaraan dicuri, pencurian motor, motor, mobil, kendaraan, dicuri, kecurian, bpkb, plat nomor',
 'Untuk kendaraan yang dicuri, buat laporan polisi (kategori Kriminal Umum) dan daftarkan juga di Kehilangan dan Temuan dengan plat nomor, nomor rangka, dan nomor mesin agar dapat dicocokkan bila kendaraan ditemukan. Siapkan STNK/BPKB sebagai bukti kepemilikan. Jika pencurian disertai kekerasan (begal), pilih kategori Kejahatan dengan Kekerasan.',
 '/hilangtemu/lapor?type=LOST&category=KENDARAAN', 'Lapor kendaraan hilang', 'CARA_LAPOR,PETUGAS', 'NONE', 0, 31),

('ORANG_HILANG', 'Orang hilang',
 'orang hilang, anak hilang, keluarga hilang, belum pulang, tidak pulang, hilang kontak, lansia hilang, orang tua hilang, pencarian orang',
 'Untuk orang hilang, Anda TIDAK perlu menunggu 1x24 jam. Segera hubungi 110 atau datang ke SPKT terdekat, terutama bila menyangkut anak, lansia, atau orang dengan kondisi khusus. Daftarkan juga di Kehilangan dan Temuan dengan foto terbaru, ciri fisik, dan pakaian terakhir.',
 '/hilangtemu/lapor?type=LOST&category=ORANG', 'Lapor orang hilang', 'PETUGAS', 'NONE', 0, 32),

('SKCK', 'SKCK',
 'skck, surat keterangan catatan kepolisian, catatan kepolisian, surat kelakuan baik, kelakuan baik, berkelakuan baik',
 'SKCK dapat diajukan secara online melalui laman resmi SKCK Polri, kemudian diambil di Polres/Polsek sesuai pilihan. Siapkan KTP, Kartu Keluarga, akta lahir/ijazah, pas foto, dan sidik jari (bila diminta). Persyaratan dan biaya mengikuti ketentuan resmi yang berlaku.',
 'https://skck.polri.go.id', 'Laman SKCK Polri', 'SIM,PETUGAS', 'NONE', 1, 40),

('SIM', 'SIM',
 'sim, surat izin mengemudi, perpanjang sim, perpanjangan sim, buat sim, bikin sim, sim hilang, ujian sim, satpas, sim online, sinar',
 'Pembuatan SIM baru dilayani di Satpas Polres. Perpanjangan SIM dapat dilakukan secara online melalui aplikasi resmi Korlantas Polri atau di gerai/Satpas. Bila SIM hilang, sertakan surat keterangan kehilangan dari kepolisian. Persyaratan dan biaya mengikuti ketentuan resmi Korlantas.',
 'https://korlantas.polri.go.id', 'Laman Korlantas', 'HILANG_TEMU,PETUGAS', 'NONE', 1, 41),

('STNK', 'STNK / pajak kendaraan',
 'stnk, pajak kendaraan, pajak motor, pajak mobil, samsat, balik nama, mutasi kendaraan, plat nomor baru, tnkb',
 'Pengesahan STNK dan pembayaran pajak kendaraan dilayani Samsat. Pengesahan tahunan dapat dilakukan secara online melalui aplikasi resmi Samsat (sesuai ketersediaan di provinsi Anda), sedangkan balik nama dan mutasi dilakukan di kantor Samsat.',
 NULL, NULL, 'SIM,PETUGAS', 'NONE', 0, 42),

('PENIPUAN_ONLINE', 'Penipuan online',
 'penipuan, ditipu, tertipu, penipuan online, scam, investasi bodong, transfer, rekening penipu, akun palsu, phishing, peretasan, diretas, hack, akun dibajak, pinjol, pinjaman online, sextortion, pemerasan online',
 'Jika menjadi korban penipuan online:\n1. Segera hubungi bank Anda untuk meminta pemblokiran rekening tujuan transfer.\n2. Simpan bukti: tangkapan layar percakapan, bukti transfer, nomor rekening, dan akun pelaku.\n3. Buat laporan polisi kategori Kejahatan Siber dan lampirkan bukti tersebut.\nJangan mengirim uang tambahan kepada pihak yang menjanjikan dana kembali.',
 '/report/create', 'Laporkan penipuan', 'CARA_LAPOR,PETUGAS', 'NONE', 1, 50),

('KDRT_PPA', 'KDRT / perempuan & anak',
 'kdrt, kekerasan dalam rumah tangga, dipukul suami, dipukul istri, pukul, dipukul, memukul, aniaya, dianiaya, suami, istri, kekerasan anak, kekerasan seksual, pelecehan, pelecehan seksual, bullying, perundungan, ppa, perempuan dan anak',
 'Kasus kekerasan terhadap perempuan dan anak ditangani Unit PPA dengan pendampingan ramah korban. Bila Anda dalam bahaya saat ini, hubungi 110. Anda dapat melapor online maupun datang ke SPKT; identitas korban dilindungi. Saya juga dapat menghubungkan Anda dengan petugas sekarang.',
 '/report/create', 'Buat laporan', 'PETUGAS,DARURAT', 'NONE', 0, 51),

('NARKOBA', 'Informasi narkoba',
 'narkoba, sabu, ganja, ekstasi, pil koplo, bandar, pengedar, pesta narkoba, obat terlarang, narkotika',
 'Informasi peredaran narkoba dapat Anda laporkan secara rahasia melalui formulir laporan (kategori Narkoba) dengan opsi identitas dirahasiakan. Sertakan lokasi, waktu, dan ciri pelaku bila diketahui. Jangan mendekati atau mengonfrontasi pelaku sendiri.',
 '/report/create', 'Lapor secara rahasia', 'WHISTLEBLOWER,PETUGAS', 'NONE', 0, 52),

('PROPAM', 'Adukan oknum polisi',
 'oknum, oknum polisi, polisi nakal, pungli, pungutan liar, suap, polisi minta uang, arogan, propam, aduan anggota, pelanggaran anggota',
 'Pengaduan terhadap perilaku anggota Polri (pungli, arogansi, penyalahgunaan wewenang) ditangani Propam. Buat laporan kategori Pengaduan Perilaku Anggota Polri dan sertakan waktu, lokasi, serta identitas/ciri anggota bila diketahui. Identitas pelapor dapat dirahasiakan.',
 '/report/create', 'Buat pengaduan', 'WHISTLEBLOWER,PETUGAS', 'NONE', 0, 53),

('LAKA_LANTAS', 'Kecelakaan lalu lintas',
 'kecelakaan, tabrakan, laka, lakalantas, ditabrak, menabrak, tabrak lari, kecelakaan lalu lintas',
 'Bila ada korban luka, hubungi 110 dan layanan ambulans segera, amankan lokasi, dan jangan memindahkan korban luka berat. Untuk laporan kecelakaan (termasuk tabrak lari) dan klaim asuransi, buat laporan di Satlantas atau melalui formulir laporan kategori Lalu Lintas.',
 '/report/create', 'Buat laporan', 'DARURAT,PETUGAS', 'NONE', 0, 54),

('IZIN_KERAMAIAN', 'Izin keramaian',
 'izin keramaian, izin acara, izin konser, izin pertunjukan, izin kegiatan, surat izin keramaian, pemberitahuan kegiatan, izin demo, unjuk rasa',
 'Izin/pemberitahuan keramaian (acara, konser, kegiatan massa, unjuk rasa) diajukan ke Polsek/Polres setempat (fungsi Intelkam) sebelum kegiatan, sesuai skala acara. Siapkan surat permohonan, identitas penanggung jawab, rincian acara, dan rekomendasi pihak terkait bila diperlukan.',
 NULL, NULL, 'PETUGAS', 'NONE', 0, 60),

('WHISTLEBLOWER', 'Lapor secara rahasia',
 'rahasia, rahasiakan, anonim, anonymous, identitas dirahasiakan, takut, takut melapor, ketahuan, whistleblower, tanpa nama, dilindungi',
 'Saat membuat laporan online, aktifkan opsi "Rahasiakan identitas saya". Identitas Anda hanya dapat dilihat petugas berwenang dan tidak ditampilkan kepada pihak lain.',
 '/report/create', 'Buat laporan rahasia', 'CARA_LAPOR,PETUGAS', 'NONE', 0, 61),

('KONTAK', 'Kontak & jam layanan',
 'kontak, alamat, nomor telepon, jam layanan, jam buka, buka jam berapa, kantor polisi terdekat, polsek terdekat, spkt, call center',
 'Call Center 110 dan SPKT melayani 24 jam. Live chat ini juga dipantau petugas Command Center. Untuk kunjungan langsung, datangi SPKT Polsek/Polres terdekat dengan membawa KTP.',
 'tel:110', 'Telepon 110', 'CARA_LAPOR,PETUGAS', 'NONE', 0, 70),

('TERIMA_KASIH', 'Terima kasih',
 'terima kasih, makasih, thanks, thank you, oke terima kasih, sudah cukup, cukup',
 'Sama-sama. Terima kasih telah menghubungi Polri. Jika masih ada yang ingin ditanyakan, silakan tulis kembali atau pilih topik di bawah.',
 NULL, NULL, 'CARA_LAPOR,CEK_STATUS,PETUGAS', 'NONE', 0, 99);
