# 🔐 TAHAP 6 — REST API, KEAMANAN LANJUTAN, OPERASIONAL & UAT

Rujukan: BRD Bab 6 (arsitektur), 8 (API), 9 (keamanan), 10 (NFR), 11 Tahap 5 (UAT), 12 (kriteria penerimaan). Bergantung pada Tahap 1–5 (lihat [POSKO_00_ROADMAP.md](POSKO_00_ROADMAP.md)).

**Output tahap:** sistem siap UAT dan pilot: API terdokumentasi, keamanan teruji lintas organisasi, backup teruji, dan seluruh kriteria penerimaan BRD Bab 12 terverifikasi.

---

## 🔴 Prioritas Tinggi

### P6-01 — REST API v1
- [ ] **Solusi:** endpoint konseptual BRD Bab 8 di bawah `/api/v1/`:

  | Method | Endpoint | Fungsi |
  |---|---|---|
  | POST | `/api/v1/auth/login` | Autentikasi, menerbitkan token |
  | GET | `/api/v1/organizations` | Organisasi sesuai kewenangan |
  | GET / POST | `/api/v1/reports` | Daftar / buat laporan |
  | GET / PATCH | `/api/v1/reports/{id}` | Detail / ubah laporan yang diizinkan |
  | POST | `/api/v1/reports/{id}/submit` | Kirim laporan |
  | POST | `/api/v1/reports/{id}/review` | Setujui / kembalikan |
  | GET | `/api/v1/dashboard/summary` | Ringkasan dashboard |
  | GET | `/api/v1/reports/summary` | Data rekapitulasi |

  - Router saat ini berbasis `controller/action` dan belum mengenal method HTTP/PATCH: tambahkan router API terpisah dengan pencocokan method + pola path.
  - Autentikasi token (Bearer, disimpan **ter-hash** di DB, kedaluwarsa, dapat dicabut); tanpa cookie sesi, jadi tanpa CSRF.
  - Memakai ulang `PoskoAccess`, validasi, dan state machine yang sama dengan UI (tidak ada logika bisnis ganda). Rate limit per token lewat `Throttle`.
  - Format galat JSON seragam (`{"error": {...}}`), paginasi standar.
- **Kriteria selesai:**
  - [ ] Dokumentasi OpenAPI (`docs/api/posko-v1.yaml`).
  - [ ] Test API: akses lintas wilayah ditolak, transisi tidak sah ditolak, token kedaluwarsa ditolak.

### P6-02 — Uji akses lintas organisasi menyeluruh
- [ ] **Solusi:** suite test yang, untuk setiap route UI & API posko, mencoba akses dari akun unit lain/tingkat lain dan memastikan ditolak (BRD 9.1, 10, 12.11).
- **Kriteria selesai:**
  - [ ] Seluruh route posko tercakup (daftar route dibandingkan otomatis dengan daftar yang diuji).

---

## 🟠 Prioritas Sedang

### P6-03 — Pemulihan akses (lupa kata sandi)
- [ ] **Masalah:** BRD 7.1 meminta pemulihan akses; saat ini belum ada.
- **Solusi:** reset lewat email dengan token sekali pakai (hash di DB, kedaluwarsa 30 menit, throttle per akun & IP), pesan seragam untuk akun ada/tidak ada; pemaksaan ganti sandi setelah reset oleh admin (P1-04). SSO disiapkan sebagai titik integrasi saja (asumsi A8).
- **Kriteria selesai:**
  - [ ] Token tidak dapat dipakai dua kali; seluruh sesi lama pengguna dicabut setelah reset.

### P6-04 — Audit lanjutan & perlindungan log
- [ ] **Solusi:** pastikan seluruh daftar BRD 4.8 tercatat (login/logout, buat, ubah isi, kirim, setujui/tolak, kembalikan, ubah status, unduh/ekspor data sensitif). Log audit tidak dapat diubah dari aplikasi; pertimbangkan hash berantai antar-baris untuk deteksi perubahan langsung di DB.
- **Kriteria selesai:**
  - [ ] Checklist BRD 4.8 tercentang dengan test per aksi.

### P6-05 — Backup & pemulihan teruji
- [ ] **Solusi:** skrip backup terjadwal (`mysqldump` + arsip `storage/uploads`), retensi sesuai kebijakan, dan **uji pemulihan** ke database terpisah yang terdokumentasi (BRD 9.2, 10, 12.12). RPO/RTO diisi setelah ditetapkan instansi.
- **Kriteria selesai:**
  - [ ] Prosedur pemulihan di `docs/OPERASIONAL_BACKUP.md` telah dijalankan sekali dan hasilnya dicatat.

### P6-06 — Kinerja, keamanan pra-produksi & lingkungan
- [ ] **Solusi:**
  - Uji beban skenario realistis (target p95 ≤ 3 detik, BRD 10).
  - Pemindaian keamanan & review kode modul posko.
  - Lingkungan terpisah: development, staging/UAT, produksi (BRD 6.2).
  - Ganti Tailwind CDN dengan CSS hasil build untuk produksi (tercatat di luar cakupan hardening sebelumnya).
- **Kriteria selesai:**
  - [ ] Laporan hasil uji beban & temuan keamanan beserta tindak lanjutnya.

---

## 🧪 Checklist Kriteria Penerimaan (BRD Bab 12)

| # | Kriteria | Diverifikasi oleh |
|---|---|---|
| 1 | Pengguna hanya mengakses data sesuai kewenangan | P1-03, P6-02 |
| 2 | Polsek dapat membuat & mengirim laporan | P2-03, P2-04 |
| 3 | Polres melihat laporan Polsek di bawahnya | P1-03, P3-03 |
| 4 | Polda mengakses laporan Polres di wilayahnya | P1-03, P3-03 |
| 5 | Pusat memonitor laporan seluruh Polda | P5-02 |
| 6 | Laporan dapat dikembalikan & dikirim ulang | P3-01, P3-03 |
| 7 | Status & histori perubahan tersimpan benar | P3-02, P3-05 |
| 8 | Rekap dapat ditelusuri ke laporan sumber | P4-01 |
| 9 | Dashboard membedakan masuk, terlambat, belum dibuat, nihil | P4-03, P4-04, P5-01 |
| 10 | Ekspor sesuai filter & hak akses | P5-04 |
| 11 | Akses lintas organisasi tak berwenang ditolak | P6-02 |
| 12 | Backup & pemulihan telah diuji | P6-05 |

- [ ] Seluruh 12 kriteria terverifikasi pada lingkungan UAT.
- [ ] Pelatihan operator & pilot pada organisasi terpilih (BRD Tahap 5) terjadwal.
