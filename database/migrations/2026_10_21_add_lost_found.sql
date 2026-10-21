-- Modul Hilang & Temu: laporan kehilangan / temuan kendaraan, orang, barang berharga, dokumen, hewan
-- Jalankan pada database yang sudah ada: mysql -uroot sigap_polda < database/migrations/2026_10_21_add_lost_found.sql

-- 1. LAPORAN HILANG / TEMU (satu baris per objek)
CREATE TABLE IF NOT EXISTS `lost_found_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NULL UNIQUE COMMENT 'Format HT-YYYYMM-NNNNN',
    `security_pin_hash` VARCHAR(255) NOT NULL,
    `report_type` ENUM('LOST', 'FOUND') NOT NULL,
    `item_category` ENUM('KENDARAAN', 'ORANG', 'BARANG_BERHARGA', 'DOKUMEN', 'HEWAN', 'LAINNYA') NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,

    -- Ciri pengenal (kendaraan / barang / dokumen)
    `brand` VARCHAR(100) NULL COMMENT 'Merk / tipe / jenis',
    `color` VARCHAR(50) NULL,
    `plate_number` VARCHAR(20) NULL COMMENT 'Disimpan ternormalisasi: huruf/angka kapital tanpa spasi',
    `chassis_number` VARCHAR(40) NULL,
    `engine_number` VARCHAR(40) NULL,
    `serial_number` VARCHAR(60) NULL COMMENT 'IMEI / nomor seri / nomor dokumen',

    -- Orang hilang / ditemukan
    `person_name` VARCHAR(100) NULL,
    `person_age` TINYINT UNSIGNED NULL,
    `person_gender` ENUM('L', 'P') NULL,

    -- Hewan
    `animal_species` VARCHAR(60) NULL,

    `event_date` DATE NOT NULL COMMENT 'Tanggal hilang / ditemukan',
    `event_location` VARCHAR(255) NOT NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `custody_location` VARCHAR(255) NULL COMMENT 'Tempat penyimpanan barang temuan (mis. SPKT Polres X)',

    `reporter_name` VARCHAR(100) NOT NULL,
    `reporter_phone` VARCHAR(20) NOT NULL,
    `reporter_nik` VARCHAR(16) NULL,
    `created_by_officer_id` INT NULL COMMENT 'Diisi bila diinput petugas di loket SPKT',

    `status` ENUM('PENDING', 'PUBLISHED', 'MATCHED', 'RESOLVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    `verified_by` INT NULL,
    `verified_at` DATETIME NULL,
    `resolved_at` DATETIME NULL,
    `officer_note` TEXT NULL COMMENT 'Alasan penolakan / catatan serah terima',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_lf_public` (`status`, `report_type`, `item_category`),
    INDEX `idx_lf_plate` (`plate_number`),
    INDEX `idx_lf_chassis` (`chassis_number`),
    INDEX `idx_lf_engine` (`engine_number`),
    INDEX `idx_lf_serial` (`serial_number`),
    FOREIGN KEY (`created_by_officer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. FOTO OBJEK (disimpan privat, disajikan lewat controller dengan kontrol akses)
CREATE TABLE IF NOT EXISTS `lost_found_photos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size_kb` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`item_id`) REFERENCES `lost_found_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. PENCOCOKAN OTOMATIS LAPORAN HILANG ↔ TEMUAN
CREATE TABLE IF NOT EXISTS `lost_found_matches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lost_item_id` INT NOT NULL,
    `found_item_id` INT NOT NULL,
    `score` TINYINT UNSIGNED NOT NULL,
    `reasons` VARCHAR(255) NULL,
    `status` ENUM('SUGGESTED', 'CONFIRMED', 'DISMISSED') NOT NULL DEFAULT 'SUGGESTED',
    `reviewed_by` INT NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_lf_pair` (`lost_item_id`, `found_item_id`),
    FOREIGN KEY (`lost_item_id`) REFERENCES `lost_found_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`found_item_id`) REFERENCES `lost_found_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. KLAIM KEPEMILIKAN BARANG TEMUAN
CREATE TABLE IF NOT EXISTS `lost_found_claims` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `claimant_name` VARCHAR(100) NOT NULL,
    `claimant_phone` VARCHAR(20) NOT NULL,
    `claimant_nik` VARCHAR(16) NOT NULL,
    `proof_description` TEXT NOT NULL COMMENT 'Ciri khusus yang hanya diketahui pemilik',
    `proof_file_path` VARCHAR(255) NULL COMMENT 'Bukti kepemilikan: BPKB/STNK/nota/foto',
    `status` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    `reviewed_by` INT NULL,
    `reviewed_at` DATETIME NULL,
    `review_note` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_lfc_item` (`item_id`, `status`),
    FOREIGN KEY (`item_id`) REFERENCES `lost_found_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
