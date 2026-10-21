-- Akun demo satuan fungsi untuk alur tiket disposisi (nama fiktif, sandi: password123)
-- Jalankan setelah migrasi 2026_10_21_satker_fungsi_ticketing.sql:
--   mysql -uroot sigap_polda < database/seeders_tiket_satker.sql
-- Idempoten: baris dengan NRP yang sama dilewati.

INSERT IGNORE INTO `users` (`nrp`, `name`, `email`, `phone`, `password_hash`, `role`, `satker_id`, `rank`, `position`)
SELECT v.nrp, v.name, v.email, v.phone, '$2y$10$qnlaf/DOh0vbZBtPBuRjkOTZJm/fghNF2D8e4xBwuD7iTervauH.O', v.role, s.id, v.rnk, v.pos
FROM (
    SELECT '98102101' AS nrp, 'Hartono Wibisono, S.I.K., M.H.' AS name, 'dirreskrimum.metro@sigap.polri.go.id' AS email, '081211112101' AS phone,
           'ROLE_SATKER_HEAD' AS role, 'POLDA-METRO-DITRESKRIMUM' AS code, 'Kombes Pol' AS rnk, 'Dirreskrimum Polda Metro Jaya' AS pos UNION ALL
    SELECT '98102102', 'Rudi Hermawan, S.I.K.', 'kasubdit.jatanras.metro@sigap.polri.go.id', '081211112102',
           'ROLE_SATKER_ADMIN', 'POLDA-METRO-JATANRAS', 'AKBP', 'Kasubdit Jatanras Ditreskrimum Polda Metro Jaya' UNION ALL
    SELECT '98102103', 'Nugroho Prasetyo, S.H.', 'kanit.jatanras.metro@sigap.polri.go.id', '081211112103',
           'ROLE_INVESTIGATOR', 'POLDA-METRO-JATANRAS', 'Iptu', 'Penyidik Subdit Jatanras Ditreskrimum Polda Metro Jaya' UNION ALL
    SELECT '98102104', 'Sari Puspitasari, S.I.K.', 'kasubdit.gakkum.metro@sigap.polri.go.id', '081211112104',
           'ROLE_SATKER_ADMIN', 'POLDA-METRO-DITLANTAS', 'AKBP', 'Kasubdit Gakkum Ditlantas Polda Metro Jaya' UNION ALL
    SELECT '98102105', 'Agus Salim Harahap, S.H.', 'kasatreskrim.jakbar@sigap.polri.go.id', '081211112105',
           'ROLE_SATKER_ADMIN', 'POLRES-METRO-JAKARTA-BARAT-SATRESKRIM', 'AKBP', 'Kasat Reskrim Polres Metro Jakarta Barat' UNION ALL
    SELECT '98102106', 'Dewi Kartika, S.H.', 'penyidik.reskrim.jakbar@sigap.polri.go.id', '081211112106',
           'ROLE_INVESTIGATOR', 'POLRES-METRO-JAKARTA-BARAT-SATRESKRIM', 'Ipda', 'Penyidik Satreskrim Polres Metro Jakarta Barat' UNION ALL
    SELECT '98102107', 'Bambang Sutrisno', 'kasatlantas.jakbar@sigap.polri.go.id', '081211112107',
           'ROLE_SATKER_ADMIN', 'POLRES-METRO-JAKARTA-BARAT-SATLANTAS', 'Kompol', 'Kasat Lantas Polres Metro Jakarta Barat'
) v
JOIN `satkers` s ON s.code = v.code;
