# 🔁 TAHAP 3 — WORKFLOW BERJENJANG, REVISI & ESKALASI

Rujukan: BRD Bab 4.3 (verifikasi), Bab 5 (workflow, aturan perubahan, laporan mendesak), Bab 9.3 (integritas). Bergantung pada [POSKO_02_INPUT_LAPORAN.md](POSKO_02_INPUT_LAPORAN.md).

**Status (15 Okt 2026): ✅ selesai** — migrasi `2026_10_15_posko_03_workflow.sql`, test `tests/Integration/PoskoWorkflowTest.php`.

**Keputusan & catatan implementasi:**
- **Eskalasi tetap berjenjang** (keputusan pemilik produk 15 Okt 2026): laporan mendesak (prioritas tinggi/kritis) tidak melompati tingkat. Laporan ditandai *MENDESAK*, selalu di urutan teratas antrean unit induk langsung, diteruskan per tingkat tanpa menunggu rekap rutin, dan setiap penerimaan tercatat (aksi `acknowledge`, sekali per unit per versi; meneruskan otomatis mencatat penerimaan).
- Kolom `holder_satker_id` menandai unit yang sedang memegang laporan. Verifikasi (mulai/setujui) hanya oleh **unit induk langsung**; tingkat di atasnya dapat menerima, meneruskan, atau mengembalikan laporan yang sudah diteruskan.
- Laporan dikembalikan (`NEEDS_REVISION`) kembali ke unit pembuat; kirim ulang selalu ke induk langsung lagi.
- Revisi atas laporan disetujui/diteruskan menaikkan versi dan wajib diverifikasi ulang; snapshot isi tersimpan setiap pengiriman (`posko_report_versions`) dan dapat dibandingkan di `/posko-laporan/versions/{id}`.
- Pembatalan hanya untuk draft/perlu perbaikan, alasan wajib, tidak menghapus data, dan melepas kunci periode (`period_key`) agar periode yang sama dapat dilaporkan ulang.
- Perbaikan bug: penyimpanan laporan tidak lagi gagal saat hanya isian angka yang berubah (sebelumnya bergantung pada jumlah baris terubah MySQL).

**Output tahap:** laporan dapat mengalir Polsek → Polres → Polda → Pusat, dikembalikan untuk perbaikan dan dikirim ulang, dengan histori dan versi yang lengkap.

---

## 🔴 Prioritas Tinggi

### P3-01 — State machine status laporan posko
- [x] **Masalah:** perlu transisi status yang sah dan atomik (BRD 9.3: status tidak boleh berubah hanya lewat manipulasi frontend).
- **Solusi:** status `DRAFT` → `SUBMITTED` → `IN_REVIEW` → (`NEEDS_REVISION` → `SUBMITTED`) → `APPROVED` → `FORWARDED`; plus `CANCELLED` (pembatalan tercatat, menggantikan hapus).
  - Tabel transisi tunggal di `App\Models\PoskoReport::TRANSITIONS` + pemeriksaan role/tingkat dari `PoskoAccess`.
  - Setiap transisi = `UPDATE ... WHERE id = ? AND status IN (...)` dalam transaksi + baris riwayat.
  - Jenis dengan `requires_approval = 0` boleh diteruskan langsung dari `SUBMITTED` dengan status "Diteruskan tanpa persetujuan" (asumsi A4).
- **Kriteria selesai:**
  - [x] Test: transisi tidak sah (mis. `DRAFT` → `APPROVED`, atau menyetujui laporan unit di luar wilayah) ditolak.
  - [x] Test: dua pemeriksa yang menyetujui bersamaan hanya menghasilkan satu persetujuan.

### P3-02 — Riwayat tindakan (histori pemeriksaan)
- [x] **Solusi:** tabel `posko_report_history (report_id, actor_id, actor_satker_id, action, status_from, status_to, notes, version, created_at)`; tampil sebagai timeline di halaman detail & verifikasi (BRD 4.3, 7.4). Identitas pemeriksa & waktu wajib tercatat.
- **Kriteria selesai:**
  - [x] Setiap transisi P3-01 menghasilkan tepat satu baris riwayat.
  - [x] Riwayat tidak dapat diubah/dihapus dari aplikasi.

### P3-03 — Halaman verifikasi
- [x] **Solusi:** `/posko/review` (BRD 7.4): daftar laporan masuk dari unit di bawahnya, filter wilayah/periode/status, tampilan isi + lampiran, catatan pemeriksaan, tombol **Setujui** / **Kembalikan untuk Perbaikan** (catatan wajib saat mengembalikan), histori tindakan.
- **Kriteria selesai:**
  - [x] Operator Polres hanya melihat laporan Polsek di bawahnya; Operator Polda hanya laporan Polres di wilayahnya (sesuai matriks BRD 3.2).
  - [x] Laporan yang dikembalikan kembali dapat diubah pembuatnya dan dikirim ulang (kriteria penerimaan BRD 12.6).

### P3-04 — Meneruskan ke tingkat atas
- [x] **Solusi:** aksi **Teruskan** oleh unit yang menyetujui; laporan sumber tetap utuh dengan identitas unit asal (BRD 2.3.1–2.3.2). Tingkat Pusat tidak memiliki aksi teruskan.
- **Kriteria selesai:**
  - [x] Laporan Polsek yang diteruskan Polres terlihat di Polda **dengan** jejak Polsek → Polres.

---

## 🟠 Prioritas Sedang

### P3-05 — Revisi & versi laporan
- [x] **Masalah:** BRD 5.3 — laporan terkirim tidak boleh diubah diam-diam; laporan disetujui memakai mekanisme revisi; versi sebelumnya disimpan.
- **Solusi:**
  - Tabel `posko_report_versions (report_id, version, snapshot_json, changed_by, reason, created_at)`; snapshot dibuat setiap kali laporan dikirim/dikirim ulang/direvisi.
  - Laporan `APPROVED`/`FORWARDED` hanya dapat diubah lewat **Ajukan Revisi** (alasan wajib) → versi baru `SUBMITTED` untuk diverifikasi ulang.
  - Halaman banding antarversi (perbedaan per field).
- **Kriteria selesai:**
  - [x] Test: mengubah laporan terkirim tanpa jalur revisi ditolak.
  - [x] Test: revisi menyimpan versi lama utuh dan menaikkan nomor versi.

### P3-06 — Jalur eskalasi laporan mendesak
- [x] **Masalah:** BRD 5.4 — kejadian penting perlu jalur cepat tanpa menunggu rekap rutin.
- **Solusi:** laporan jenis `SPECIAL_EVENT` / `INCIDENTAL` dengan prioritas `HIGH` / `CRITICAL` dapat **dieskalasi langsung** ke Polda atau Pusat. Wajib: identitas sumber, waktu kejadian & pelaporan, prioritas, **pihak penerima eskalasi** (tercatat), dan status "menunggu laporan formal" sampai jalur berjenjang selesai.
- **Kriteria selesai:**
  - [x] Eskalasi muncul seketika di dashboard tingkat tujuan dan tercatat di riwayat & audit.
  - [x] Laporan eskalasi tetap harus melalui verifikasi berjenjang untuk menjadi formal.

### P3-07 — Audit aksi workflow
- [x] **Solusi:** `Audit::log` untuk `posko.report.create`, `.update`, `.submit`, `.approve`, `.return`, `.forward`, `.revise`, `.cancel`, `.escalate`, `.attachment.download` (BRD 4.8).
- **Kriteria selesai:**
  - [x] Halaman audit yang ada dapat memfilter aksi `posko.*`.
