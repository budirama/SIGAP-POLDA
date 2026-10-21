-- =============================================================================
-- MIGRASI: SISTEM "SATU NOMOR, SATU JEJAK" (ONE POLICE SERVICE) v1.3
-- Tanggal: 18 Oktober 2026
-- Standar: MySQL 5.7+ / MariaDB 10.4+ InnoDB Engine (UTF8MB4)
-- =============================================================================

USE `sigap_polda`;

-- 1. TABEL UTAMA INDUK PELACAKAN GLOBAL (GLOBAL CASE TRACKER)
CREATE TABLE IF NOT EXISTS `ops_global_tracks` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tracking_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Format: POL-YYYYMM-KANAL-XXXXX',
    `security_pin_hash` VARCHAR(255) NOT NULL COMMENT 'Hash 6-digit PIN akses publik pelapor',
    `source_channel` ENUM(
        '110_CALL',
        'SPKT_LOKET',
        'WEB_PORTAL',
        'SUPERAPP',
        'DUMAS_PROPAM',
        'CYBER_CRIME'
    ) NOT NULL DEFAULT 'WEB_PORTAL',
    `current_status` ENUM(
        'INCOMING',
        'DISPATCHED',
        'VERIFIED_SPKT',
        'INVESTIGATION',
        'RESOLVED',
        'CLOSED',
        'REJECTED'
    ) NOT NULL DEFAULT 'INCOMING',
    `priority_level` ENUM('P1_EMERGENCY', 'P2_URGENT', 'P3_ROUTINE') NOT NULL DEFAULT 'P3_ROUTINE',
    `incident_category` VARCHAR(100) NOT NULL,
    `incident_summary` TEXT NOT NULL,
    `incident_lat` DECIMAL(10, 8) NULL,
    `incident_lng` DECIMAL(11, 8) NULL,
    `incident_address` TEXT NULL,
    `citizen_name` VARCHAR(150) NOT NULL,
    `citizen_nik_encrypted` VARBINARY(256) NULL COMMENT 'Enkripsi AES-256 demi UU PDP',
    `citizen_phone` VARCHAR(30) NOT NULL,
    `is_whistleblower` TINYINT(1) NOT NULL DEFAULT 0,
    `is_vulnerable_group` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Perempuan, Anak (PPA), Lansia, Difabel',
    `current_satker_id` INT NOT NULL,
    `assigned_officer_nrp` VARCHAR(50) NULL,
    `external_reference_no` VARCHAR(100) NULL COMMENT 'No LP e-MP / ID CAD 110 / Dumas Presisi',
    `sla_deadline` DATETIME NULL,
    `is_stalled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Perkara macet / melewati batas SLA',
    `closed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ops_track_num` (`tracking_number`),
    INDEX `idx_ops_status_satker` (`current_status`, `current_satker_id`),
    INDEX `idx_ops_sla_stalled` (`sla_deadline`, `is_stalled`),
    INDEX `idx_ops_created` (`created_at`),
    CONSTRAINT `fk_ops_satker` FOREIGN KEY (`current_satker_id`) REFERENCES `satkers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. TABEL ASYNCHRONOUS EVENT BUS (TRANSACTIONAL OUTBOX)
CREATE TABLE IF NOT EXISTS `ops_event_streams` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tracking_number` VARCHAR(50) NOT NULL,
    `event_name` VARCHAR(100) NOT NULL COMMENT 'e.g. 110_DISPATCHED, SPKT_ACCEPTED, SP2HP_ISSUED, SLA_BREACHED',
    `payload_data` JSON NOT NULL,
    `actor_nrp_or_system` VARCHAR(50) NOT NULL,
    `actor_role` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ops_event_track` (`tracking_number`),
    INDEX `idx_ops_event_name_time` (`event_name`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. TABEL AUDIT LOG AKSES DATA PRIBADI (KEPATUHAN UU PDP & ANTI-KEBOCORAN)
CREATE TABLE IF NOT EXISTS `ops_audit_pdp_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tracking_number` VARCHAR(50) NOT NULL,
    `viewer_nrp` VARCHAR(50) NOT NULL,
    `viewer_satker_id` INT NOT NULL,
    `accessed_data` VARCHAR(255) NOT NULL COMMENT 'NIK, NAMA_KORBAN, NO_HP, STATEMENT',
    `justification` VARCHAR(255) NOT NULL COMMENT 'Alasan dinas resmi membuka data pribadi pelapor',
    `is_out_of_jurisdiction` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Anomali personil membuka perkara satker lain',
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ops_pdp_viewer` (`viewer_nrp`),
    INDEX `idx_ops_pdp_track` (`tracking_number`),
    INDEX `idx_ops_pdp_anomaly` (`is_out_of_jurisdiction`, `accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. TABEL MODUL 2: TRIASE & DISPATCH 110 (COMPUTER-AIDED DISPATCH)
CREATE TABLE IF NOT EXISTS `ops_dispatch_110` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tracking_number` VARCHAR(50) NOT NULL UNIQUE,
    `caller_phone` VARCHAR(30) NOT NULL,
    `caller_name` VARCHAR(150) NULL,
    `urgency_level` ENUM('P1_EMERGENCY', 'P2_URGENT', 'P3_ROUTINE') DEFAULT 'P1_EMERGENCY',
    `unit_callsign` VARCHAR(50) NULL COMMENT 'Nomor lambung mobil patroli / regu Samapta',
    `patrol_vehicle_type` ENUM('SAMAPTA_CAR', 'LANTAS_MOTOR', 'BHABIN_MOBILE', 'WATER_PATROL') DEFAULT 'SAMAPTA_CAR',
    `officer_nrp` VARCHAR(50) NULL,
    `call_received_at` DATETIME NOT NULL,
    `dispatched_at` DATETIME NULL,
    `arrived_at` DATETIME NULL,
    `response_time_seconds` INT NULL COMMENT 'Total detik dari panggilan ditutup hingga tiba di TKP',
    `status` ENUM('CALL_RECEIVED', 'DISPATCHED', 'EN_ROUTE', 'ON_SCENE', 'RESOLVED_ONSITE', 'ESCALATED_SPKT') DEFAULT 'CALL_RECEIVED',
    `scene_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ops_dispatch_status` (`status`),
    INDEX `idx_ops_dispatch_response` (`response_time_seconds`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. TABEL MODUL 1 & 4: CASE MANAGEMENT & SP2HP OTOMATIS
CREATE TABLE IF NOT EXISTS `ops_case_milestones` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tracking_number` VARCHAR(50) NOT NULL,
    `stage_code` ENUM('A1', 'A2', 'A3', 'A4', 'A5') NOT NULL COMMENT 'A1 Penerimaan, A2 Perkembangan Lidik, A3 Sidik, A4 JPU, A5 Selesai',
    `title` VARCHAR(200) NOT NULL,
    `description_public` TEXT NOT NULL,
    `description_internal` TEXT NULL,
    `officer_nrp` VARCHAR(50) NOT NULL,
    `jpu_coordination_status` VARCHAR(100) NULL COMMENT 'BERKAS_TAHAP_1, P19_PETUNJUK, P21_LENGKAP, PELIMPAHAN',
    `evidence_hashes` JSON NULL COMMENT 'Hash SHA-256 digital chain of custody',
    `qr_verification_hash` VARCHAR(64) NOT NULL,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ops_milestone_track` (`tracking_number`),
    INDEX `idx_ops_milestone_stage` (`stage_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. TABEL MODUL 6: ADUAN PENIPUAN DARING & BLOKIR CEPAT (ANTISIBER OJK)
CREATE TABLE IF NOT EXISTS `ops_cyber_fraud` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tracking_number` VARCHAR(50) NOT NULL,
    `bank_name` VARCHAR(100) NOT NULL,
    `account_number` VARCHAR(50) NOT NULL,
    `account_holder_name` VARCHAR(150) NOT NULL,
    `fraud_scheme` ENUM('ONLINE_SHOP', 'INVESTMENT_SCAM', 'APK_PHISHING', 'IMPERSONATION', 'LOTTERY_SCAM') NOT NULL,
    `loss_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `freeze_status` ENUM('PENDING_SUBMISSION', 'REQUESTED_TO_BANK', 'FROZEN_CONFIRMED', 'REJECTED_BY_BANK') DEFAULT 'PENDING_SUBMISSION',
    `freeze_requested_at` DATETIME NULL,
    `freeze_confirmed_at` DATETIME NULL,
    `cluster_tag` VARCHAR(100) NULL COMMENT 'Tag identifikasi sindikat rekening terhubung',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ops_fraud_acc` (`account_number`),
    INDEX `idx_ops_fraud_freeze` (`freeze_status`),
    INDEX `idx_ops_fraud_cluster` (`cluster_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. TABEL MODUL 7: PETA KERAWANAN & CATATAN SOLUSI BHABINKAMTIBMAS
CREATE TABLE IF NOT EXISTS `ops_bhabin_resolutions` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tracking_number` VARCHAR(50) NOT NULL UNIQUE,
    `bhabin_nrp` VARCHAR(50) NOT NULL,
    `village_name` VARCHAR(150) NOT NULL,
    `satker_id` INT NOT NULL,
    `conflict_category` VARCHAR(100) NOT NULL COMMENT 'Sengketa Batas, Keributan Warga, Utang Piutang, Tipiring',
    `parties_involved` TEXT NOT NULL,
    `mediation_outcome` TEXT NOT NULL,
    `is_peacefully_resolved` TINYINT(1) NOT NULL DEFAULT 1,
    `is_ceremonial_rejected` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Sistem tolak jika hanya foto seremonial',
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ops_bhabin_satker` (`satker_id`, `is_peacefully_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. TABEL MODUL 5: DUMAS TERPADU & PERINGATAN DINI INTEGRITAS
CREATE TABLE IF NOT EXISTS `ops_dumas_cases` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `tracking_number` VARCHAR(50) NOT NULL UNIQUE,
    `reported_satker_id` INT NOT NULL,
    `reported_service_loket` VARCHAR(100) NULL COMMENT 'Loket SIM, Loket SKCK, Unit Reskrim, SPKT',
    `allegation_type` ENUM('PUNGLI', 'PEMERASAN', 'PENOLAKAN_LAPORAN', 'PENYIDIKAN_MANGKRAK', 'AROGANSI') NOT NULL,
    `is_whistleblower_protected` TINYINT(1) DEFAULT 1,
    `risk_score` TINYINT NOT NULL DEFAULT 1 COMMENT '1-5 Skor Indeks Resiko',
    `investigation_status` ENUM('NEW', 'VERIFIED_ITWAS', 'INVESTIGATED_PROPAM', 'SANCTION_RECOMMENDED', 'CLOSED_DISPROVEN') DEFAULT 'NEW',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ops_dumas_satker` (`reported_satker_id`, `allegation_type`),
    INDEX `idx_ops_dumas_risk` (`risk_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
