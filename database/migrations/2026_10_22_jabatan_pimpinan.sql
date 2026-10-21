-- Penyesuaian pangkat & jabatan akun demo pimpinan agar sesuai struktur organisasi Polri
-- Jalankan: mysql -uroot sigap_polda < database/migrations/2026_10_22_jabatan_pimpinan.sql
-- Identitas login (email, NRP, no. HP) & role TIDAK diubah; nama tetap fiktif.
-- Nama, pangkat & jabatan pejabat sebenarnya diisi operator lewat menu Profil Pejabat (superadmin).
-- Idempoten: aman dijalankan ulang.

UPDATE `users` SET `rank` = 'Komjen Pol', `position` = 'Inspektur Pengawasan Umum (Irwasum) Polri'
WHERE `email` = 'itwasum@sigap.polri.go.id';

UPDATE `users` SET `position` = 'Asisten Kapolri Bidang Operasi (Asops Kapolri)'
WHERE `email` = 'posko.exec.pusat@sigap.polri.go.id';

UPDATE `users` SET `position` = 'Wakapolda Jawa Timur'
WHERE `email` = 'posko.exec.jatim@sigap.polri.go.id';

UPDATE `users` SET `position` = 'Kapolrestabes Surabaya'
WHERE `email` = 'posko.exec.surabaya@sigap.polri.go.id';

UPDATE `users` SET `position` = 'Kanit 1 Subdit III Jatanras Dittipidum Bareskrim Polri'
WHERE `email` = 'kanit.tipidum@sigap.polri.go.id';
