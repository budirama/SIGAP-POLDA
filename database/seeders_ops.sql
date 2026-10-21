-- =============================================================================
-- SEED DATA: SISTEM "SATU NOMOR, SATU JEJAK" (ONE POLICE SERVICE) v1.3
-- Mengisi data dasar (Baseline) dan data uji coba pilot di 5 Polres percontohan
-- =============================================================================

USE `sigap_polda`;

-- 1. SEED DATA TRACKING UTAMA (ops_global_tracks)
-- Hash bcrypt '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O' = PIN '123456'
INSERT INTO `ops_global_tracks` 
(`id`, `tracking_number`, `security_pin_hash`, `source_channel`, `current_status`, `priority_level`,
 `incident_category`, `incident_summary`, `incident_lat`, `incident_lng`, `incident_address`,
 `citizen_name`, `citizen_phone`, `is_whistleblower`, `is_vulnerable_group`,
 `current_satker_id`, `assigned_officer_nrp`, `external_reference_no`, `sla_deadline`, `is_stalled`, `created_at`) 
VALUES
-- Kasus 1: Panggilan 110 Darurat (P1)
(1, 'POL-202610-110-8924A', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O',
 '110_CALL', 'DISPATCHED', 'P1_EMERGENCY', 'CURAT_RUMAH', 
 'Pencurian dengan pemberatan di perumahan warga, pelaku terduga masih di sekitar lokasi',
 -6.22350000, 106.80990000, 'Jl. Kemang Raya No. 12, Mampang Prapatan, Jakarta Selatan',
 'Rahmat Hidayat', '081298760001', 0, 0, 7, '91040701', 'CAD-110-8812', DATE_ADD(NOW(), INTERVAL 4 HOUR), 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),

-- Kasus 2: Loket SPKT Ramah Korban (Jalur PPA & Proxy Entry)
(2, 'POL-202610-SPKT-7712B', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O',
 'SPKT_LOKET', 'VERIFIED_SPKT', 'P2_URGENT', 'KDRT_PPA',
 'Kekerasan dalam rumah tangga terhadap perempuan dan anak bawah umur (Jalur Ramah Korban PPA)',
 -6.24150000, 106.79980000, 'Kelurahan Gandaria Utara, Kebayoran Baru',
 'Siti Rahmawati (Didampingi Petugas)', '081399880002', 0, 1, 1, '88030301', 'LP/B/452/X/2026/SPKT', DATE_ADD(NOW(), INTERVAL 20 HOUR), 0, DATE_SUB(NOW(), INTERVAL 5 HOUR)),

-- Kasus 3: Perkara e-MP Aktif dengan SP2HP A1, A2, A3
(3, 'POL-202610-WEB-4102C', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O',
 'WEB_PORTAL', 'INVESTIGATION', 'P3_ROUTINE', 'PENGGELAPAN_KENDARAAN',
 'Dugaan penggelapan kendaraan bermotor roda empat rental yang tidak dikembalikan',
 -6.94020000, 107.66920000, 'Kecamatan Buahbatu, Kota Bandung',
 'Agus Supriatna', '081511220003', 0, 0, 8, '91040701', 'LP/B/109/X/2026/DITRESKRIMUM', DATE_ADD(NOW(), INTERVAL 14 DAY), 0, DATE_SUB(NOW(), INTERVAL 8 DAY)),

-- Kasus 4: Kejahatan Siber Finansial & Blokir Cepat OJK
(4, 'POL-202610-CYB-3310D', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O',
 'CYBER_CRIME', 'INVESTIGATION', 'P2_URGENT', 'PENIPUAN_DARING',
 'Penipuan modus undangan pernikahan APK Phishing yang menguras rekening korban',
 -7.33340000, 112.73150000, 'Wonokromo, Kota Surabaya',
 'Dewi Lestari', '081822330004', 0, 0, 3, '92080401', 'OJK-FREEZE-BCA991', DATE_ADD(NOW(), INTERVAL 18 HOUR), 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Kasus 5: Dumas Integritas (Whistleblower Terlindungi)
(5, 'POL-202610-DMS-1049E', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O',
 'DUMAS_PROPAM', 'INCOMING', 'P2_URGENT', 'DUMAS_PROPAM',
 'Laporan dugaan permintaan imbalan liar (pungli) di loket pengurusan surat dinas oleh oknum',
 -6.24140000, 106.79970000, 'Loket Pelayanan Terpadu',
 'Whistleblower Terlindungi', '081933440005', 1, 0, 6, '78010801', 'PROPAM-WBS-2026-08', DATE_ADD(NOW(), INTERVAL 48 HOUR), 0, DATE_SUB(NOW(), INTERVAL 6 HOUR)),

-- Kasus 6: Problem Solving Bhabinkamtibmas (Restorative Justice)
(6, 'POL-202610-BHB-5521F', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O',
 'SPKT_LOKET', 'RESOLVED', 'P3_ROUTINE', 'PROBLEM_SOLVING_BHABIN',
 'Mediasi sengketa batas pekarangan rumah antara keluarga Bpk. Joko dan Bpk. Suharjo berakhir mufakat damai kekeluargaan',
 -6.99360000, 110.42110000, 'Kelurahan Candisari, Semarang',
 'Keluarga Bpk. Joko & Bpk. Suharjo', '082155660006', 0, 0, 9, '88030301', 'ADR-BHABIN-2026-14', NULL, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),

-- Kasus 7: Kasus Mangkrak (Untuk Transparansi Defisit Halaman 41)
(7, 'POL-202610-DEF-9901G', '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O',
 'WEB_PORTAL', 'INVESTIGATION', 'P3_ROUTINE', 'PENYEROBOTAN_LAHAN',
 'Perkara sengketa lahan belum diterbitkan SP2HP A2 melewati batas SLA 14 hari',
 -6.24150000, 106.79980000, 'Kecamatan Tebet, Jakarta Selatan',
 'Hendrik Wijaya', '081277880007', 0, 0, 7, '91040701', 'LP/B/210/IX/2026/RESKRIM', DATE_SUB(NOW(), INTERVAL 3 DAY), 1, DATE_SUB(NOW(), INTERVAL 25 DAY))
ON DUPLICATE KEY UPDATE `current_status`=VALUES(`current_status`), `is_stalled`=VALUES(`is_stalled`);

-- 2. SEED DISPATCH 110 (ops_dispatch_110)
INSERT INTO `ops_dispatch_110`
(`id`, `tracking_number`, `caller_phone`, `caller_name`, `urgency_level`, `unit_callsign`, `patrol_vehicle_type`,
 `officer_nrp`, `call_received_at`, `dispatched_at`, `arrived_at`, `response_time_seconds`, `status`, `scene_notes`)
VALUES
(1, 'POL-202610-110-8924A', '081298760001', 'Rahmat Hidayat', 'P1_EMERGENCY', 'PATROLI-SAMAPTA-01', 'SAMAPTA_CAR',
 '91040701', DATE_SUB(NOW(), INTERVAL 120 MINUTE), DATE_SUB(NOW(), INTERVAL 118 MINUTE), DATE_SUB(NOW(), INTERVAL 109 MINUTE),
 540, 'ON_SCENE', 'Petugas patroli tiba di TKP dalam 9 menit. Mengamankan lokasi dan meminta keterangan saksi lingkungan.')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`), `response_time_seconds`=VALUES(`response_time_seconds`);

-- 3. SEED MILESTONES SP2HP (ops_case_milestones)
INSERT INTO `ops_case_milestones`
(`id`, `tracking_number`, `stage_code`, `title`, `description_public`, `description_internal`, `officer_nrp`, `jpu_coordination_status`, `qr_verification_hash`, `issued_at`)
VALUES
(1, 'POL-202610-WEB-4102C', 'A1', 'Penerimaan Laporan & Registrasi Perkara',
 'Laporan telah resmi diterima dan diregistrasikan ke dalam Sistem Manajemen Penyidikan Polri (e-MP).',
 'Penyelidik ditunjuk Ipda Rian Prasetya dengan Sprint.Lidik/88/X/2026/Reskrim.', '91040701', NULL,
 'qr_a1_hash_verification_polri_4102c_step1', DATE_SUB(NOW(), INTERVAL 7 DAY)),

(2, 'POL-202610-WEB-4102C', 'A2', 'Pemberitahuan Perkembangan Hasil Penyelidikan',
 'Penyelidik telah melakukan klarifikasi terhadap 3 orang saksi dan mengamankan bukti rekaman CCTV.',
 'Ditemukan indikasi pelaku menyewa kendaraan menggunakan KTP palsu. Sedang dilakukan pelacakan GPS.', '91040701', NULL,
 'qr_a2_hash_verification_polri_4102c_step2', DATE_SUB(NOW(), INTERVAL 4 DAY)),

(3, 'POL-202610-WEB-4102C', 'A3', 'Peningkatan Status ke Tahap Penyidikan',
 'Gelar perkara telah dilaksanakan. Status perkara resmi ditingkatkan ke tahap penyidikan.',
 'SPDP telah dikirimkan ke Kejaksaan Negeri Bandung.', '91040701', 'BERKAS_TAHAP_1',
 'qr_a3_hash_verification_polri_4102c_step3', DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 4. SEED CYBER FRAUD (ops_cyber_fraud)
INSERT INTO `ops_cyber_fraud`
(`id`, `tracking_number`, `bank_name`, `account_number`, `account_holder_name`, `fraud_scheme`, `loss_amount`,
 `freeze_status`, `freeze_requested_at`, `freeze_confirmed_at`, `cluster_tag`)
VALUES
(1, 'POL-202610-CYB-3310D', 'BANK_BCA', '8820192841', 'DONI KURNIAWAN', 'APK_PHISHING', 18500000.00,
 'FROZEN_CONFIRMED', DATE_SUB(NOW(), INTERVAL 22 HOUR), DATE_SUB(NOW(), INTERVAL 20 HOUR), 'CLUSTER-REK-2841')
ON DUPLICATE KEY UPDATE `freeze_status`=VALUES(`freeze_status`);

-- 5. SEED BHABINKAMTIBMAS RESOLUTIONS (ops_bhabin_resolutions)
INSERT INTO `ops_bhabin_resolutions`
(`id`, `tracking_number`, `bhabin_nrp`, `village_name`, `satker_id`, `conflict_category`, `parties_involved`,
 `mediation_outcome`, `is_peacefully_resolved`, `is_ceremonial_rejected`)
VALUES
(1, 'POL-202610-BHB-5521F', '88030301', 'Candisari, Semarang', 9, 'Sengketa Batas Pekarangan',
 'Bpk. Joko Susilo & Bpk. Suharjo Pambudi',
 'Dilakukan pengukuran ulang bersama perangkat kelurahan dan disepakati patok batas permanen tanpa tuntutan hukum lanjutan.',
 1, 0)
ON DUPLICATE KEY UPDATE `is_peacefully_resolved`=VALUES(`is_peacefully_resolved`);

-- 6. SEED DUMAS CASES (ops_dumas_cases)
INSERT INTO `ops_dumas_cases`
(`id`, `tracking_number`, `reported_satker_id`, `reported_service_loket`, `allegation_type`, `is_whistleblower_protected`, `risk_score`, `investigation_status`)
VALUES
(1, 'POL-202610-DMS-1049E', 7, 'Loket SIM Satpas', 'PUNGLI', 1, 3, 'VERIFIED_ITWAS')
ON DUPLICATE KEY UPDATE `investigation_status`=VALUES(`investigation_status`);

-- 7. SEED EVENT STREAM BUS (ops_event_streams)
INSERT INTO `ops_event_streams`
(`tracking_number`, `event_name`, `payload_data`, `actor_nrp_or_system`, `actor_role`, `ip_address`, `created_at`)
VALUES
('POL-202610-110-8924A', 'CASE_REGISTERED', '{"channel":"110_CALL","priority":"P1_EMERGENCY"}', 'SYSTEM', 'CALL_CENTER_110', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 120 MINUTE)),
('POL-202610-110-8924A', '110_DISPATCHED', '{"unit":"PATROLI-SAMAPTA-01"}', '88030301', 'ROLE_SPKT_OPERATOR', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 118 MINUTE)),
('POL-202610-110-8924A', '110_ARRIVED_ON_SCENE', '{"response_time_seconds":540}', '91040701', 'ROLE_INVESTIGATOR', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 109 MINUTE)),
('POL-202610-WEB-4102C', 'SP2HP_ISSUED', '{"stage":"A1"}', '91040701', 'ROLE_INVESTIGATOR', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 7 DAY)),
('POL-202610-WEB-4102C', 'SP2HP_ISSUED', '{"stage":"A2"}', '91040701', 'ROLE_INVESTIGATOR', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('POL-202610-WEB-4102C', 'SP2HP_ISSUED', '{"stage":"A3"}', '91040701', 'ROLE_INVESTIGATOR', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('POL-202610-CYB-3310D', 'CYBER_FREEZE_REQUESTED', '{"bank":"BANK_BCA","account":"8820192841"}', '92080401', 'ROLE_CYBER_HUMAS', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 22 HOUR));
