# 🚔 SIGAP-POLDA
### *Sistem Integrasi Pengaduan & Dumas Terpadu Polda*
> **"POLISI BERSAMA MASYARAKAT • MELINDUNGI, MENGAYOMI DAN MELAYANI"**  
> *Polda Presisi: Prediktif, Responsibilitas, dan Transparansi Berkeadilan.*

---

## 📖 Ringkasan Proyek
**SIGAP-POLDA** adalah aplikasi komprehensif *case management* penanganan pengaduan masyarakat (*Dumas*) terintegrasi satu pintu untuk Kepolisian Daerah (Polda). Sistem ini menghubungkan portal pengaduan warga, ingestion aduan media sosial (X/Twitter, Instagram, TikTok), meja verifikasi SPKT, lembar kerja penyidikan tingkat Satker/Polres, penerbitan SP2HP Elektronik berkala, ruang komunikasi klarifikasi dua arah warga-penyidik, hingga Situational Command Center & GIS Heatmap bagi Kapolda.

---

## 🧱 Tech Stack
* **Backend:** PHP Native (Murni tanpa framework pihak ketiga)
* **Arsitektur:** MVC (*Model, View, Controller*) dengan URL Rewrite Engine
* **Database:** MySQL / MariaDB (Prepared Statements & Transaksional PDO)
* **Frontend:** Tailwind CSS, Font Awesome 6, Leaflet.js (GIS Map & Pinpoint GPS), Chart.js
* **Keamanan:** RBAC (*Role-Based Access Control* 9 Level), Session Hijacking Protection, CSRF Token Protection, Whistleblower Privacy Mode
* **Aset Identitas:** Lambang resmi *Polisi Bersama Masyarakat*, *Tribrata Rastra Sewakotama*, dan *Polri Presisi*

---

## 🌟 Fitur Utama

1. **Portal Publik Warga (Citizen Portal):**
   - Formulir pengaduan multi-step dengan live GPS Pinpoint (Leaflet.js).
   - Opsi *Whistleblower Protection* (kerahasiaan data pelapor terjamin).
   - Live Tracking Status Perkara berbasis Nomor Tiket & 6-Digit PIN Keamanan.
   - Ruang Klarifikasi & Komunikasi Dua Arah Warga-Penyidik (*Citizen-Police Direct Clarification*).
   - Unduh & Pratinjau Cetak Surat Pemberitahuan Perkembangan Hasil Penyelidikan (SP2HP-E).
   - Survei Kepuasan Masyarakat (SKM) bintang 1–5 terintegrasi.

2. **Omnichannel Social Media Ingestion (Workspace Humas & Siber):**
   - Monitoring aduan dan mention viral dari platform X/Twitter, Instagram, Facebook, dan TikTok.
   - Fitur *1-Click Convert to Official Police Ticket*.

3. **Meja Verifikasi & Disposisi SPKT (Command Center):**
   - Triase berkas aduan masyarakat: Verifikasi bukti, penolakan hoax/SP3 dini, dan disposisi cerdas ke Satuan Kerja (Ditreskrimum, Ditreskrimsus, Ditlantas, Ditresnarkoba, Bidpropam, Polres jajaran).

4. **Inbox Satker & Penugasan Kanit:**
   - Penerimaan disposisi dan penerbitan Surat Perintah Tugas (*Sprintgas*) resmi ke Penyidik Pelaksana.

5. **Workspace Lembar Kerja Penyidik:**
   - Pencatatan ganda: Catatan publik (SP2HP terkirim ke pelapor) vs Catatan rahasia kedinasan (*intelligence notes*).
   - Ruang klarifikasi interaktif bersama warga pelapor.
   - Penerbitan SP2HP Elektronik format resmi (A1/A2/A3/A4/A5).
   - Penutupan perkara dengan Berita Acara (BAP).

6. **Executive Dashboard Kapolda (Situational Command Center):**
   - Peta GIS Heatmap sebaran tindak pidana real-time.
   - Metrik KPI: *Clearance Rate*, Waktu Respons Rata-Rata, Watchlist Satker Bottleneck SLA.
   - Tombol Komando: *"Beri Atensi Kapolda"* (prioritas bintang merah dengan instruksi taktis tertulis).

---

## 🗂️ Struktur Direktori Proyek

```text
SIGAP-POLDA/
├── app/
│   ├── config/             # Konfigurasi Database PDO, URL, & Enums Constants
│   ├── controllers/        # Controllers (Auth, Home, Report, Spkt, Satker, Investigator, Executive, Social)
│   ├── core/               # MVC Engine (App, Controller, Model, Session, Auth)
│   ├── models/             # Models (Report, User, SocialFeed, Feedback)
│   └── views/              # Views (auth, backoffice, executive, public, layouts)
├── database/
│   ├── migrations/         # Migrasi SQL untuk database yang sudah terpasang
│   ├── schema.sql          # DDL Skema Relasional MySQL (12 Tabel)
│   └── seeders.sql         # Data Seeder Satker, Akun Pengujian 9 Roles, & Contoh Kasus
├── docs/
│   ├── PRD_SIGAP_POLDA.md  # Dokumen Spesifikasi Kebutuhan Produk (PRD)
│   ├── TASK.md             # Checklist Tahapan Implementasi
│   └── WALKTHROUGH.md      # Panduan Pengoperasian & Verifikasi Sistem
├── tests/                  # Test runner tanpa dependensi (Unit & Integration HTTP)
├── public/
│   ├── assets/             # CSS Tema Resmi Polri, Logo Polisi Bersama Masyarakat, Icon
│   ├── .htaccess           # Apache URL Rewriting
│   └── index.php           # Front Controller / Single Entry Point
├── storage/
│   └── uploads/            # Unggahan Bukti Digital (privat, diunduh via /file/* dengan kontrol akses)
├── .gitignore
└── README.md
```

---

## 🚀 Panduan Instalasi Lokal (XAMPP / Apache + MySQL)

### 1. Clone Repositori
Tempatkan proyek di dalam folder `htdocs` server web lokal:
```bash
cd /xampp/htdocs/
git clone https://github.com/budirama/SIGAP-POLDA.git
```

### 2. Import Database MySQL
1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin/`) atau MySQL CLI.
2. Buat database bernama `sigap_polda`:
   ```sql
   CREATE DATABASE sigap_polda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import file skema dan seeder secara berurutan:
   - `database/schema.sql`
   - `database/seeders.sql`

### 3. Konfigurasi Environment (`.env`)
Salin `.env.example` menjadi `.env` di root proyek, lalu sesuaikan nilainya:
```bash
cp .env.example .env
```
```ini
APP_ENV=development   # development: detail error + Quick Login uji coba aktif
DB_HOST=127.0.0.1
DB_NAME=sigap_polda
DB_USER=root
DB_PASS=
```
> ⚠️ Untuk server produksi gunakan `APP_ENV=production` (default bila `.env` tidak ada) — detail error disembunyikan dan Quick Login dinonaktifkan. File `.env` tidak di-commit.

Bila database sudah terpasang dari versi sebelumnya, jalankan migrasi di `database/migrations/` (file `.sql` via MySQL, file `.php` via `php <file>`).

### 4. Menjalankan Test Otomatis
Test runner PHP CLI tanpa dependensi (unit test + integration test HTTP):
```bash
php tests/run.php              # semua test
php tests/run.php unit         # unit test saja (tanpa server/DB)
php tests/run.php integration  # integration test HTTP
```
Integration test membutuhkan Apache & MySQL berjalan, database berisi data seeder, dan `.env` dengan `APP_ENV=development`. Data uji (judul berawalan `ZZTEST_`) dibersihkan otomatis. URL dapat diubah via env `TEST_BASE_URL` (default `http://localhost/SIGAP-Polda/public`).

### 5. Buka di Browser
* **Portal Warga:** `http://localhost/SIGAP-Polda/` atau `http://localhost/SIGAP-Polda/public/`
* **Portal Personil Backoffice:** `http://localhost/SIGAP-Polda/public/auth/login`

---

## 🔑 Akun Uji Coba Pengujian (Demo 9 Roles)
Semua akun pengujian menggunakan kata sandi default: **`password123`**.  
*(Saat `APP_ENV=development`, halaman login menyediakan switcher 1-klik untuk berganti role secara instan).*

| Role | Akun Login | Deskripsi Wewenang |
|---|---|---|
| **Kapolda (Executive)** | `kapolda@sigap.polda.go.id` | GIS Heatmap kriminalitas, bottleneck SLA satker, tombol atensi pimpinan |
| **Ka SPKT** | `spkt@sigap.polda.go.id` | Meja triase verifikasi berkas, tolak hoax, disposisi ke Dit/Polres |
| **Humas / Siber** | `humas@sigap.polda.go.id` | Radar medsos viral (X, IG, TikTok), 1-klik konversi ke tiket resmi |
| **Dirreskrimum (Kasatker)** | `dirkrimum@sigap.polda.go.id` | Supervisi penanganan perkara direktorat dan persetujuan SP2HP |
| **Kanit Jatanras** | `kanit.krimum@sigap.polda.go.id` | Menerima disposisi dari SPKT dan menerbitkan Sprintgas ke Penyidik |
| **Penyidik Pelaksana** | `penyidik.rian@sigap.polda.go.id` | Olah TKP, ruang klarifikasi dua arah, terbitkan SP2HP-E, tutup perkara BAP |
| **Irwasda / Propam** | `irwasda@sigap.polda.go.id` | Audit trail kepatuhan, pengawasan dumas, akses catatan intelijen |
| **Superadmin IT** | `superadmin@sigap.polda.go.id` | Akses menyeluruh data master, manajemen pengguna & sistem |
| **Masyarakat (Pelapor)** | `budi.santoso@gmail.com` | Akses portal tracking tiket dan ruang klarifikasi mandiri |

---

## 📜 Lisensi & Atribusi
Hak Cipta © 2026 Kepolisian Daerah Republik Indonesia.  
Dikembangkan untuk penguatan pelayanan publik Presisi dan Kemitraan Polisi Bersama Masyarakat.
