# 🛡️ TASK LIST: HARDENING & PERBAIKAN PASCA-REVIEW (SIGAP-POLDA)

Lanjutan hasil code review 15 Oktober 2026. Setiap task dikerjakan di branch terpisah → commit → Pull Request → merge ke `main`.

## ✅ Sudah Selesai

| PR | Cakupan |
|---|---|
| [#1](https://github.com/budirama/SIGAP-POLDA/pull/1) | Quick Login khusus development, wajib PIN di portal publik, otorisasi kasus per Penyidik/Satker, perbaikan SPKT reject, CSRF, rate limit PIN |
| [#2](https://github.com/budirama/SIGAP-POLDA/pull/2) | Konfigurasi `.env`, sembunyikan detail error di production, blokir akses web ke file sensitif, amankan akun warga (NIK & password) |

---

## 📋 Task

### T1 — Router: alias rute, 404 yang benar & whitelist action
- [ ] **Masalah:**
  - URL `/social` mencari `SocialController` (tidak ada), sehingga router diam-diam jatuh ke `HomeController`. **Seluruh modul Media Sosial tidak dapat diakses**: login Humas mendarat di beranda, dan form `/social/convert`, `/social/dismiss`, `/social/simulate` berakhir 404.
  - Controller yang tidak dikenal tidak menghasilkan 404.
  - `method_exists` mengizinkan pemanggilan method warisan/protected (`render`, `json`, `redirect`, ...) yang berujung error 500.
  - Parameter yang kurang/salah tipe (mis. `/investigator/detail` atau `/investigator/detail/abc`) menghasilkan error 500.
- **Solusi:** peta alias rute (`social` → `SocialMediaController`), 404 untuk controller tak dikenal, action hanya method `public` non-static yang dideklarasikan di controller itu sendiri (bukan `__*`), validasi jumlah & tipe parameter `int`.
- **Kriteria selesai:** `/social` membuka Omnichannel Medsos untuk Humas; `/xyz`, `/report/render`, `/investigator/detail`, `/investigator/detail/abc` → 404.

### T2 — `.htaccess` portabel (tanpa `RewriteBase` hardcode)
- [ ] **Masalah:** `RewriteBase /SIGAP-Polda/public/` membuat aplikasi rusak bila dipasang di folder/domain lain.
- **Solusi:** hapus `RewriteBase`; rewrite relatif terhadap direktori `public/`.
- **Kriteria selesai:** semua rute tetap berfungsi di `http://localhost/SIGAP-Polda/public/`.

### T3 — Nomor tiket atomik (anti-bentrok)
- [ ] **Masalah:** nomor tiket dari `COUNT(*) + 1` → dua laporan bersamaan mendapat nomor sama dan gagal di constraint UNIQUE; nomor juga bisa berulang bila ada laporan yang dihapus.
- **Solusi:** tabel `ticket_sequences` per periode `YYYYMM` dengan increment atomik `INSERT ... ON DUPLICATE KEY UPDATE last_seq = LAST_INSERT_ID(last_seq + 1)` di dalam transaksi pembuatan laporan + migrasi inisialisasi dari nomor tiket tertinggi yang sudah ada.
- **Kriteria selesai:** laporan paralel menghasilkan nomor unik berurutan; tiket baru melanjutkan nomor tertinggi yang ada.

### T4 — Escape di output, bukan di input
- [ ] **Masalah:** `Controller::post()/get()` menjalankan `htmlspecialchars` sebelum data disimpan, lalu view meng-escape lagi → teks seperti `Tom & Jerry` tampil `Tom &amp; Jerry`; password yang mengandung `& < > " '` tidak cocok dengan hash-nya.
- **Solusi:** `post()/get()` hanya `trim`; pastikan seluruh output di view memakai `htmlspecialchars`.
- **Kriteria selesai:** input dengan karakter khusus tersimpan apa adanya di DB dan tampil benar sekali-escape; tidak ada output user-controlled tanpa escape.
- **Catatan:** data lama hasil input warga yang sudah tersimpan dalam bentuk ter-escape tidak dikonversi otomatis (data seed tidak terdampak).

### T5 — File bukti privat (tidak dapat diakses publik)
- [ ] **Masalah:** lampiran bukti & klarifikasi disimpan di `public/uploads/` dengan nama mudah ditebak (`BUKTI_<timestamp>_<4 digit>`) → dapat diunduh siapa pun tanpa login/PIN. Validasi hanya berdasarkan ekstensi, tanpa batas ukuran.
- **Solusi:** simpan di `storage/uploads/` (di luar web root, diblokir `.htaccess`), nama file acak 128-bit, validasi MIME (`finfo`) + batas ukuran, dan unduh lewat controller `/file/attachment/{id}` & `/file/message/{id}` yang memeriksa hak akses (personil berwenang atau pelapor dengan PIN terverifikasi). Migrasi memindahkan file lama.
- **Kriteria selesai:** akses langsung ke file → 403/404; unduh lewat controller hanya berhasil bagi yang berhak.

### T6 — Akurasi statistik dashboard
- [ ] **Masalah:** rata-rata rating SKM bernilai palsu `4.8` bila belum ada feedback; hitungan laporan medsos tidak memasukkan Facebook.
- **Solusi:** kembalikan `null` dan tampilkan `–` bila belum ada data; hitung semua kanal medsos dari konstanta.
- **Kriteria selesai:** tanpa data feedback beranda menampilkan `–`; laporan Facebook ikut terhitung.

### T7 — Test otomatis
- [ ] **Masalah:** belum ada test sama sekali.
- **Solusi:** test runner PHP CLI tanpa dependensi (`tests/run.php`): unit test (router, `.env` parser) dan integration test HTTP terhadap server lokal untuk alur akses (PIN, rate limit, otorisasi kasus, CSRF, file privat, routing).
- **Kriteria selesai:** `php tests/run.php` hijau di XAMPP lokal dengan data seeder.
