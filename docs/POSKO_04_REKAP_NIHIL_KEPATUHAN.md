# 📊 TAHAP 4 — REKAPITULASI BERJENJANG, LAPORAN NIHIL & KEPATUHAN

Rujukan: BRD Bab 2.2–2.3 (laporan sumber vs rekap, prinsip konsolidasi), 4.4 (rekapitulasi), 4.5 (monitoring kepatuhan). Bergantung pada [POSKO_03_WORKFLOW_BERJENJANG.md](POSKO_03_WORKFLOW_BERJENJANG.md).

**Output tahap:** setiap tingkat dapat menyusun rekap wilayah yang dapat ditelusuri ke laporan sumber, membedakan laporan nihil dari laporan yang belum masuk, dan memantau kepatuhan pelaporan unit di bawahnya.

---

## 🔴 Prioritas Tinggi

### P4-01 — Rekapitulasi berjenjang
- [ ] **Masalah:** belum ada rangkuman per Polres/Polda/Pusat.
- **Solusi:**
  - Tabel `posko_recaps (satker_id, report_type_id, period_start, period_end, status, narrative, analysis, created_by, version, needs_review)` + `posko_recap_sources (recap_id, report_id, report_version)` untuk jejak sumber.
  - **Nilai agregat dihitung otomatis** dari `posko_report_values` sesuai `aggregate` field (SUM/AVG/MAX) atas laporan sumber yang disetujui di wilayah tersebut; **narasi/analisis ditulis petugas** dan disimpan terpisah (BRD 4.4).
  - Rekap mengikuti workflow yang sama (kirim → verifikasi → teruskan) ke tingkat atasnya.
- **Kriteria selesai:**
  - [ ] Test: angka rekap Polres = jumlah nilai laporan Polsek sumbernya.
  - [ ] Setiap angka rekap dapat ditelusuri ke daftar laporan sumber & versinya (kriteria BRD 12.8).

### P4-02 — Penandaan rekap saat laporan sumber direvisi
- [ ] **Masalah:** BRD 5.3 — revisi laporan sumber setelah rekap dibuat harus menandai rekap untuk ditinjau ulang.
- **Solusi:** saat P3-05 membuat versi baru, set `needs_review = 1` pada seluruh rekap yang merujuk laporan tersebut (rekursif ke rekap tingkat atas) + notifikasi ke penyusun rekap.
- **Kriteria selesai:**
  - [ ] Test rantai: revisi laporan Polsek menandai rekap Polres **dan** rekap Polda yang memuatnya.

### P4-03 — Laporan nihil terkonfirmasi
- [ ] **Masalah:** BRD 2.3.4–2.3.5 & 4.5 — laporan yang belum masuk tidak boleh dianggap nihil; nihil harus dinyatakan eksplisit oleh petugas berwenang.
- **Solusi:** aksi **Nyatakan Nihil** untuk jenis dengan `allows_nil = 1`: membuat laporan berstatus `SUBMITTED` dengan flag `is_nil = 1`, wajib alasan singkat, tercatat pelaku & waktu; tetap melalui verifikasi.
- **Kriteria selesai:**
  - [ ] Dashboard & rekap membedakan "Nihil terkonfirmasi" dari "Belum dibuat" (kriteria BRD 12.9).
  - [ ] Sistem tidak pernah membuat status nihil secara otomatis.

---

## 🟠 Prioritas Sedang

### P4-04 — Kewajiban lapor & monitoring kepatuhan
- [ ] **Solusi:**
  - Tabel/fungsi `posko_obligations`: kombinasi unit × jenis × periode yang **wajib** dilaporkan (dibangkitkan dari jenis aktif & `applies_to_levels`, unit aktif saja).
  - Status kepatuhan per kewajiban (BRD 4.5): **Belum dibuat**, **Draft**, **Dikirim**, **Dalam verifikasi**, **Perlu perbaikan**, **Disetujui**, **Terlambat**, **Nihil terkonfirmasi**.
  - "Terlambat" dihitung dari `deadline_at` vs `submitted_at` (atau waktu sekarang bila belum dikirim).
  - Halaman `/posko/compliance`: matriks unit di bawahnya × periode, dengan warna status dan filter jenis/periode.
- **Kriteria selesai:**
  - [ ] Test: unit yang tidak mengirim sampai tenggat tampil "Terlambat"/"Belum dibuat", bukan nihil.
  - [ ] Unit nonaktif tidak dihitung sebagai wajib lapor.

### P4-05 — Kinerja query rekap & kepatuhan
- [ ] **Solusi:** indeks pada `(satker_id, report_type_id, period_start)`, pemanfaatan `satkers.path`; bila diperlukan, tabel ringkasan (materialized summary) yang diperbarui saat transisi status (BRD 6.3).
- **Kriteria selesai:**
  - [ ] Halaman kepatuhan tingkat Pusat dengan data demo skala nasional (± 5.000 unit × 30 periode) termuat p95 ≤ 3 detik (BRD 10).
