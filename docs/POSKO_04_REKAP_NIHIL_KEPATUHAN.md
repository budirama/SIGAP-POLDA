# 📊 TAHAP 4 — REKAPITULASI BERJENJANG, LAPORAN NIHIL & KEPATUHAN

Rujukan: BRD Bab 2.2–2.3 (laporan sumber vs rekap, prinsip konsolidasi), 4.4 (rekapitulasi), 4.5 (monitoring kepatuhan). Bergantung pada [POSKO_03_WORKFLOW_BERJENJANG.md](POSKO_03_WORKFLOW_BERJENJANG.md).

**Status (15 Okt 2026): ✅ selesai** (kecuali uji beban P4-05, dipindah ke P6-06) — migrasi `2026_10_15_posko_04_rekap.sql`, test `tests/Integration/PoskoRekapTest.php` & `tests/Unit/PoskoComplianceTest.php`.

**Catatan implementasi:**
- Rekap disimpan sebagai laporan posko `is_recap = 1` sehingga memakai workflow, riwayat & versi Tahap 3. Route penyusunan: `/posko-rekap` (Polres ke atas).
- Sumber rekap unit U = laporan U sendiri yang sudah dikirim + untuk tiap unit anak: rekap anak (bila disetujui/diteruskan) atau laporan sumber anak yang disetujui/diteruskan — tidak ada hitung ganda di tingkat atas.
- Nilai agregat dihitung ulang otomatis saat rekap dikirim; isian pilihan/teks tidak diagregasi. AVG pada rekap bertingkat adalah rata-rata dari rata-rata (perlu dikonfirmasi bila dibutuhkan rata-rata tertimbang).
- Tenggat rekap = tenggat laporan sumber + 1 hari (jeda verifikasi unit).
- Nihil dinyatakan lewat tombol "Nyatakan Nihil" (jenis berperiode dengan `allows_nil`), alasan wajib, langsung dikirim ke induk untuk diverifikasi.
- Kepatuhan: `/posko-kepatuhan` (semua role posko), status Belum dibuat · Tidak masuk (lewat tenggat) · Draft · Dikirim · Dalam verifikasi · Perlu perbaikan · Disetujui · Nihil terkonfirmasi, plus penanda terlambat.

**Output tahap:** setiap tingkat dapat menyusun rekap wilayah yang dapat ditelusuri ke laporan sumber, membedakan laporan nihil dari laporan yang belum masuk, dan memantau kepatuhan pelaporan unit di bawahnya.

---

## 🔴 Prioritas Tinggi

### P4-01 — Rekapitulasi berjenjang
- [x] **Masalah:** belum ada rangkuman per Polres/Polda/Pusat.
- **Solusi:**
  - Tabel `posko_recaps (satker_id, report_type_id, period_start, period_end, status, narrative, analysis, created_by, version, needs_review)` + `posko_recap_sources (recap_id, report_id, report_version)` untuk jejak sumber.
  - **Nilai agregat dihitung otomatis** dari `posko_report_values` sesuai `aggregate` field (SUM/AVG/MAX) atas laporan sumber yang disetujui di wilayah tersebut; **narasi/analisis ditulis petugas** dan disimpan terpisah (BRD 4.4).
  - Rekap mengikuti workflow yang sama (kirim → verifikasi → teruskan) ke tingkat atasnya.
- **Kriteria selesai:**
  - [x] Test: angka rekap Polres = jumlah nilai laporan Polsek sumbernya.
  - [x] Setiap angka rekap dapat ditelusuri ke daftar laporan sumber & versinya (kriteria BRD 12.8).

### P4-02 — Penandaan rekap saat laporan sumber direvisi
- [x] **Masalah:** BRD 5.3 — revisi laporan sumber setelah rekap dibuat harus menandai rekap untuk ditinjau ulang.
- **Solusi:** saat P3-05 membuat versi baru, set `needs_review = 1` pada seluruh rekap yang merujuk laporan tersebut (rekursif ke rekap tingkat atas) + notifikasi ke penyusun rekap.
- **Kriteria selesai:**
  - [x] Test rantai: revisi laporan Polsek menandai rekap Polres **dan** rekap Polda yang memuatnya.

### P4-03 — Laporan nihil terkonfirmasi
- [x] **Masalah:** BRD 2.3.4–2.3.5 & 4.5 — laporan yang belum masuk tidak boleh dianggap nihil; nihil harus dinyatakan eksplisit oleh petugas berwenang.
- **Solusi:** aksi **Nyatakan Nihil** untuk jenis dengan `allows_nil = 1`: membuat laporan berstatus `SUBMITTED` dengan flag `is_nil = 1`, wajib alasan singkat, tercatat pelaku & waktu; tetap melalui verifikasi.
- **Kriteria selesai:**
  - [x] Dashboard & rekap membedakan "Nihil terkonfirmasi" dari "Belum dibuat" (kriteria BRD 12.9).
  - [x] Sistem tidak pernah membuat status nihil secara otomatis.

---

## 🟠 Prioritas Sedang

### P4-04 — Kewajiban lapor & monitoring kepatuhan
- [x] **Solusi:**
  - Tabel/fungsi `posko_obligations`: kombinasi unit × jenis × periode yang **wajib** dilaporkan (dibangkitkan dari jenis aktif & `applies_to_levels`, unit aktif saja).
  - Status kepatuhan per kewajiban (BRD 4.5): **Belum dibuat**, **Draft**, **Dikirim**, **Dalam verifikasi**, **Perlu perbaikan**, **Disetujui**, **Terlambat**, **Nihil terkonfirmasi**.
  - "Terlambat" dihitung dari `deadline_at` vs `submitted_at` (atau waktu sekarang bila belum dikirim).
  - Halaman `/posko/compliance`: matriks unit di bawahnya × periode, dengan warna status dan filter jenis/periode.
- **Kriteria selesai:**
  - [x] Test: unit yang tidak mengirim sampai tenggat tampil "Terlambat"/"Belum dibuat", bukan nihil.
  - [x] Unit nonaktif tidak dihitung sebagai wajib lapor.

### P4-05 — Kinerja query rekap & kepatuhan
- [x] **Solusi:** indeks pada `(satker_id, report_type_id, period_start)`, pemanfaatan `satkers.path`; bila diperlukan, tabel ringkasan (materialized summary) yang diperbarui saat transisi status (BRD 6.3).
- **Kriteria selesai:**
  - [ ] Halaman kepatuhan tingkat Pusat dengan data demo skala nasional (± 5.000 unit × 30 periode) termuat p95 ≤ 3 detik (BRD 10). *Belum diuji — dipindah ke uji beban P6-06; saat ini kueri memakai indeks `uq_posko_report_period_recap` & tampilan per tingkat (drill-down), bukan seluruh unit sekaligus.*
