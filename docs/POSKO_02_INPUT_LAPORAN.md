# 📝 TAHAP 2 — JENIS LAPORAN & INPUT LAPORAN POSKO

Rujukan: BRD Bab 4.2 (modul input), 7.3 (halaman input), 9.3 (integritas). Bergantung pada [POSKO_01_ORGANISASI_ROLE.md](POSKO_01_ORGANISASI_ROLE.md).

**Output tahap:** operator posko dapat membuat, menyimpan draft, melampirkan berkas, mempratinjau, dan mengirim laporan sesuai jenis & periodenya.

---

## 🔴 Prioritas Tinggi

### P2-01 — Master jenis laporan yang dapat dikonfigurasi
- [ ] **Masalah:** BRD mewajibkan jenis laporan dapat dikonfigurasi (rutin harian, periodik, kejadian khusus, insidentil).
- **Solusi:** tabel `posko_report_types`:
  - `code`, `name`, `kind` (`ROUTINE` / `PERIODIC` / `SPECIAL_EVENT` / `INCIDENTAL`), `period_unit` (`DAY` / `WEEK` / `MONTH` / `NONE`), `deadline_rule` (mis. `+0D 23:59`, `+1W MON 12:00`), `requires_approval` (asumsi A4), `allows_nil` (boleh nihil), `is_active`.
  - Berlaku untuk tingkat mana saja (`applies_to_levels`, mis. Polsek & Polres).
  - Halaman konfigurasi untuk System Administrator / Admin Pusat.
- **Kriteria selesai:**
  - [ ] 4 jenis contoh di seeder (asumsi A2).
  - [ ] Jenis nonaktif tidak dapat dipilih untuk laporan baru, tetapi laporan lamanya tetap tampil.

### P2-02 — Definisi isian formulir per jenis (form builder sederhana)
- [ ] **Masalah:** "data kuantitatif sesuai format laporan" berbeda per jenis.
- **Solusi:** tabel `posko_report_fields` per jenis: `key`, `label`, `type` (`number` / `text` / `textarea` / `select` / `date`), `required`, `options`, `aggregate` (`SUM` / `AVG` / `MAX` / `NONE` — dipakai rekap Tahap 4), `sort_order`. Nilai disimpan di `posko_report_values (report_id, field_key, value_number, value_text)` — **bukan** JSON bebas — agar rekap dapat dihitung dengan SQL.
- **Kriteria selesai:**
  - [ ] Validasi server sesuai tipe & wajib isi; angka negatif/bukan angka ditolak.
  - [ ] Mengubah definisi field tidak merusak laporan lama (field lama tetap terbaca; field baru kosong).

### P2-03 — Laporan posko: data umum, draft & penomoran
- [ ] **Masalah:** belum ada entitas laporan posko.
- **Solusi:** tabel `posko_reports` memuat data umum BRD 4.2: `report_number`, `report_type_id`, `satker_id` (unit pelapor), `period_start`, `period_end` / `event_time`, `region` (wilayah kejadian), `situation_summary`, `description`, `obstacles` (kendala), `follow_up` (tindak lanjut), `assistance_needed` + `escalation_level`, `created_by`, `status`, `submitted_at`, `deadline_at`, `version`, `reference_ticket` (opsional, nomor aduan SIGAP — asumsi A6).
  - Status awal `DRAFT`; draft hanya terlihat & dapat diubah oleh unit pembuat.
  - **Nomor laporan dibuat saat dikirim**, atomik dan unik, format `PSK/{KODE-UNIT}/{YYYYMM}/{NNNN}` (pola `ticket_sequences`).
  - Satu laporan rutin per (unit, jenis, periode): dicegah dengan unique key `(satker_id, report_type_id, period_start)` untuk jenis berperiode.
- **Kriteria selesai:**
  - [ ] Dua draft untuk periode yang sama ditolak dengan pesan jelas.
  - [ ] Nomor tidak bentrok pada pengiriman bersamaan (test paralel sederhana).

### P2-04 — Halaman input laporan
- [ ] **Solusi:** `/posko/reports/create` & `/posko/reports/{id}/edit` (BRD 7.3): identitas & nomor (setelah kirim), pilih jenis, pilih periode, formulir dinamis, unggah lampiran, **Simpan Draft**, **Pratinjau**, **Kirim**. Isian tetap ada bila validasi gagal (pola `_old_input` SIGAP).
- **Kriteria selesai:**
  - [ ] Kirim memvalidasi seluruh isian wajib; draft boleh belum lengkap.
  - [ ] Pratinjau menampilkan laporan persis seperti yang akan diterima tingkat atas.

---

## 🟠 Prioritas Sedang

### P2-05 — Lampiran laporan posko
- [ ] **Solusi:** tabel `posko_report_attachments`, memakai `App\Core\Upload` (penyimpanan privat, deteksi MIME dari isi, nama acak, batas ukuran). Unduhan lewat route berotorisasi `PoskoAccess::canView` dan tercatat di audit.
- **Kriteria selesai:**
  - [ ] Akses langsung ke file & unduhan lintas wilayah ditolak (test).

### P2-06 — Periode aktif & tenggat
- [ ] **Solusi:** layanan `PoskoSchedule` menghitung periode aktif dan `deadline_at` dari `deadline_rule` jenis laporan (zona waktu WIB, sesuai koneksi DB +07:00).
- **Kriteria selesai:**
  - [ ] Unit test perhitungan tenggat harian/mingguan/bulanan, termasuk pergantian bulan & tahun kabisat.

### P2-07 — Daftar laporan unit sendiri
- [ ] **Solusi:** `/posko/reports` dengan filter jenis, periode, status; paginasi (pakai partial `_pagination.php`); hanya unit dalam `scopeSatkerIds()`.
- **Kriteria selesai:**
  - [ ] Filter & paginasi mempertahankan parameter; data di luar wilayah tidak pernah tampil.
