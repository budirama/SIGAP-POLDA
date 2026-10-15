# 📈 TAHAP 5 — DASHBOARD PER TINGKAT, EKSPOR & NOTIFIKASI

Rujukan: BRD Bab 4.1 (dashboard posko), 4.6 (notifikasi), 4.7 (laporan & ekspor), 7.2 & 7.5 (rancangan dashboard). Bergantung pada [POSKO_04_REKAP_NIHIL_KEPATUHAN.md](POSKO_04_REKAP_NIHIL_KEPATUHAN.md).

**Status (15 Okt 2026): 🟡 sebagian** — P5-01, P5-02, P5-05, P5-06 selesai (migrasi `2026_10_15_posko_05_notifikasi.sql`, test `tests/Integration/PoskoDashboardTest.php`); P5-03 cetak & P5-04 ekspor belum.

**Output tahap:** pimpinan dan operator di setiap tingkat memperoleh gambaran situasi yang akurat, dapat menelusuri hingga unit terbawah, mengekspor laporan resmi, dan menerima pengingat tepat waktu.

---

## 🔴 Prioritas Tinggi

### P5-01 — Dashboard posko per tingkat
- [x] **Solusi:** `/posko` (BRD 4.1, 7.2) menampilkan, sesuai wilayah pengguna:
  - Nama & tingkat posko, periode aktif.
  - Kartu: harus dibuat, sudah dikirim, menunggu verifikasi, perlu perbaikan, terlambat.
  - Status pelaporan unit di bawahnya (ringkas dari P4-04).
  - Laporan terbaru, laporan terlambat, laporan yang memerlukan tindakan pengguna ini.
  - Daftar kejadian penting (eskalasi P3-06) & grafik tren pelaporan per waktu.
- **Kriteria selesai:**
  - [x] Angka kartu sama dengan hitungan langsung di database untuk wilayah tersebut (test).
  - [x] Grafik mengikuti pedoman visualisasi proyek (warna konsisten tema, terbaca di HP).

### P5-02 — Dashboard Pusat & drill-down
- [x] **Solusi:** tampilan Executive 1/Pusat (BRD 7.5): status pelaporan seluruh Polda, rekap per wilayah, perbandingan antarperiode, laporan terlambat & prioritas tinggi, tren nasional, **drill-down** Pusat → Polda → Polres → Polsek. Executive 2 (Polda) & 3 (Polres) memakai tampilan yang sama dengan cakupan wilayahnya.
- **Kriteria selesai:**
  - [x] Drill-down berhenti di batas wilayah pengguna; URL drill-down ke wilayah lain ditolak.

---

## 🟠 Prioritas Sedang

### P5-03 — Filter, pencarian & cetak
- [ ] **Solusi:** filter tanggal, periode, wilayah, jenis laporan, status di daftar laporan & rekap (BRD 4.7); tampilan cetak dengan template dokumen resmi (kop, nomor laporan, tanggal, sumber data, pejabat terkait sesuai kewenangan).
- **Kriteria selesai:**
  - [ ] Hasil cetak memuat nomor laporan, periode, unit sumber, dan penanda versi.

### P5-04 — Ekspor PDF & Excel
- [ ] **Solusi:**
  - Excel: format `.xlsx` (mis. PhpSpreadsheet via Composer, atau generator XML SpreadsheetML tanpa dependensi bila Composer belum dipakai — putuskan saat implementasi).
  - PDF: dari template cetak (mis. Dompdf), atau "Simpan sebagai PDF" dari halaman cetak sebagai tahap awal.
  - Ekspor **selalu** menerapkan filter & `scopeSatkerIds()` yang sama dengan tampilan; ekspor dicatat di audit (`posko.export`) — termasuk ekspor data sensitif (BRD 4.8).
- **Kriteria selesai:**
  - [ ] Test: isi ekspor = isi tampilan untuk filter yang sama dan tidak memuat data di luar wilayah (kriteria BRD 12.10).

### P5-05 — Notifikasi dalam aplikasi
- [x] **Solusi:** tabel `notifications (user_id, type, title, body, link, read_at, created_at)`; ikon lonceng di header dengan jumlah belum dibaca (polling ringan ber-header `X-SIGAP-Poll` agar tidak memperpanjang sesi idle). Pemicu (BRD 4.6): tenggat mendekat, terlambat, laporan baru dikirim, dikembalikan, disetujui, kejadian prioritas tinggi, diteruskan ke tingkat berikutnya.
- **Kriteria selesai:**
  - [x] Setiap pemicu menghasilkan notifikasi hanya untuk penerima yang berwenang.

### P5-06 — Notifikasi email & pengingat terjadwal
- [x] **Solusi:** pengiriman email via SMTP dari `.env` (asumsi A7) lewat antrean tabel `notification_outbox` agar request pengguna tidak menunggu SMTP; skrip `cron`/Task Scheduler `php scripts/posko_reminders.php` untuk pengingat tenggat & keterlambatan.
- **Kriteria selesai:**
  - [x] Skrip aman dijalankan berulang (tidak mengirim pengingat ganda untuk periode yang sama).
  - [x] Kegagalan SMTP tercatat & dicoba ulang, tidak menghentikan proses lain.
