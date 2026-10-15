# 🚔 WALKTHROUGH & PANDUAN APLIKASI SIGAP-POLDA
## Sistem Informasi & Pengaduan Masyarakat Terpadu POLDA

Sistem **SIGAP-POLDA** telah berhasil dibangun secara utuh (*end-to-end*) sesuai dengan spesifikasi PRD dan rencana implementasi di [TASK.md](file:///d:/xampp/htdocs/SIGAP-Polda/docs/TASK.md). Aplikasi dibangun menggunakan **PHP Native (tanpa framework)** berarsitektur **MVC murni**, database **MySQL/MariaDB**, styling **Tailwind CSS** bernuansa resmi Kepolisian Negara Republik Indonesia, serta atribut resmi Korps Bhayangkara (*Rastra Sewakotama* & *Polri Presisi*).

---

## 🌐 Akses URL Utama Aplikasi

Setelah Apache & MySQL di XAMPP aktif:

| Modul | URL Akses | Deskripsi |
|---|---|---|
| **Portal Publik Warga** | `http://localhost/SIGAP-Polda/` atau `http://localhost/SIGAP-Polda/public/` | Landing page publik, transparansi alur 4 langkah, dan hotline darurat 110. |
| **Form Buat Laporan** | `http://localhost/SIGAP-Polda/public/report/create` | Formulir multi-step aduan dengan live Leaflet GPS pinpoint, opsi Whistleblower, & upload bukti. |
| **Lacak Status & SP2HP** | `http://localhost/SIGAP-Polda/public/report/track` | Pelacakan progres investigasi mandiri dan unduh dokumen resmi SP2HP digital. |
| **Login Personil Backoffice** | `http://localhost/SIGAP-Polda/public/auth/login` | Login dinas dengan switcher 1-klik untuk 9 role pengujian. |
| **Dashboard Kapolda (Executive)** | `http://localhost/SIGAP-Polda/public/executive` | Command Center Situasional Kapolda, GIS Heatmap, metrik SLA, dan tombol Atensi Pimpinan. |
| **Meja Triage SPKT** | `http://localhost/SIGAP-Polda/public/spkt` | Verifikasi kelayakan laporan dan mesin disposisi cerdas ke Dit/Polres. |
| **Social Media Ingestion** | `http://localhost/SIGAP-Polda/public/social` | Workspace Humas & Siber untuk konversi 1-klik aduan viral (X, IG, TikTok, FB). |
| **Inbox Satuan Kerja / Kanit** | `http://localhost/SIGAP-Polda/public/satker` | Penerimaan disposisi dan penerbitan Surat Perintah Tugas (Sprintgas) ke Penyidik. |
| **Workspace Penyidik** | `http://localhost/SIGAP-Polda/public/investigator` | Lembar kerja penyidikan, pemisahan catatan intelijen vs publik, penerbitan SP2HP-E. |

---

## 🔑 Kredensial Akun Pengujian (Demo 9 Roles)

Semua akun dinas menggunakan kata sandi default: **`password123`**.  
*(Pada halaman login, tersedia tombol cepat 1-klik untuk berganti role tanpa perlu mengetik ulang).*

| Role | Nama Personel | NRP / Email Login | Wewenang Utama |
|---|---|---|---|
| **Kapolda (Executive)** | Irjen Pol. Dr. Karyoto, S.I.K. | `kapolda@sigap.polda.go.id` | GIS Heatmap kriminalitas, monitoring bottleneck SLA, dan tombol "Atensi Kapolda". |
| **Ka SPKT** | Aipda Hendra Gunawan | `spkt@sigap.polda.go.id` | Meja verifikasi berkas laporan masuk, tolak hoax, dan disposisi ke Dit/Polres. |
| **Humas / Siber** | Briptu Cindy Permata | `humas@sigap.polda.go.id` | Monitor feed media sosial dan 1-klik konversi postingan viral ke tiket resmi. |
| **Dirreskrimum (Kasatker)** | Kombes Pol. Wira Satya Triputra | `dirkrimum@sigap.polda.go.id` | Supervisi penanganan perkara tingkat direktorat dan persetujuan SP2HP. |
| **Kanit Jatanras** | Kompol Denny Arifin, S.H. | `kanit.krimum@sigap.polda.go.id` | Menerima disposisi dari SPKT dan menunjuk Penyidik Pelaksana (Sprintgas). |
| **Penyidik Pelaksana** | Ipda Rian Prasetya, S.Tr.K. | `penyidik.rian@sigap.polda.go.id` | Olah TKP, unggah bukti, terbitkan SP2HP digital, dan tutup perkara (BAP). |
| **Irwasda / Propam** | Kombes Pol. Raden Firman, S.I.K. | `irwasda@sigap.polda.go.id` | Audit trail kepatuhan, pengawasan dumas, dan catatan rahasia kedinasan. |
| **Superadmin IT** | Bripka Adhi Nugraha, S.Kom | `superadmin@sigap.polda.go.id` | Akses penuh master data, manajemen pengguna, dan audit log sistem. |
| **Pelapor (Warga)** | Budi Santoso | `budi.santoso95@gmail.com` | Pengajuan laporan, pelacakan mandiri, dan pengisian survei kepuasan (SKM). |

---

## 🔄 Contoh Skenario Uji Coba End-to-End

### Skenario 1: Warga Melaporkan Tindak Pidana & Lacak SP2HP
1. Buka `http://localhost/SIGAP-Polda/public/report/create`.
2. Isi formulir:
   - NIK: `3171012304950001`, Nama: `Budi Santoso`, WhatsApp: `081298765432`.
   - Centang opsi *"Rahasiakan Identitas Saya"* jika ingin menguji fitur **Whistleblower**.
   - Pilih Kategori: *Kriminal Umum*, Judul: *Pencurian Sepeda Motor di Depan Minimarket*.
   - Klik pada peta interaktif untuk menandai koordinat TKP.
   - Unggah bukti foto/dokumen.
3. Klik **Kirimkan Laporan Pengaduan**.
4. Sistem langsung menerbitkan **Nomor Tiket** (misal: `LP-POLDA-202610-0004`) dan **PIN Rahasia 6-Digit**.
5. Buka `http://localhost/SIGAP-Polda/public/report/track`, masukkan Nomor Tiket & PIN untuk melihat timeline penanganan dan unduh dokumen resmi SP2HP ber-watermark QR Code.

### Skenario 2: Aduan Viral Media Sosial Dikomandoi Kapolda
1. Login sebagai **Humas / Siber** via login cepat `http://localhost/SIGAP-Polda/public/auth/login`.
2. Buka menu **Social Media Ingestion**, lihat postingan mention viral dari X (Twitter).
3. Klik **Konversi ke Tiket Resmi**. Tiket baru terdaftar otomatis.
4. Login sebagai **Kapolda** (`kapolda@sigap.polda.go.id`), buka **Dashboard Eksekutif**.
5. Titik kejadian muncul di **Peta GIS Heatmap** berwarna kuning.
6. Klik tombol **"Beri Instruksi Atensi Kapolda"** dan pilih tiket tersebut.
7. Tiket berubah menjadi **Bintang Merah Berkedip** (*Kapolda Priority Flag*) dan mendapat prioritas 1x24 jam.
8. Login sebagai **Kanit** (`kanit.krimum@sigap.polda.go.id`), buka menu **Disposisi Satker** dan terbitkan Sprintgas ke **Ipda Rian Prasetya**.
9. Login sebagai **Penyidik Rian**, buka **Workspace Kasus**, input catatan kemajuan (SP2HP), dan selesaikan perkara.
10. Pelapor dapat langsung membuka pelacakan dan mengisi **Survei Kepuasan Masyarakat (SKM) Bintang 5**.

---

## 🏛️ Struktur Direktori MVC yang Telah Terpasang

```
SIGAP-Polda/
│
├── app/
│   ├── config/
│   │   ├── Config.php              # Konfigurasi Database, Base URL, Direktori
│   │   ├── Constants.php           # Status Code, Enums Kategori, Roles, Channels
│   │   └── Database.php            # Singleton PDO MySQL (Native Prepared Statements)
│   ├── core/
│   │   ├── App.php                 # Routing Engine & URL Dispatcher
│   │   ├── Controller.php          # Base Controller (render view, json, sanitization)
│   │   ├── Model.php               # Base Model (PDO Query Helper)
│   │   ├── Session.php             # Session Manager aman (CSRF, Anti-Hijacking)
│   │   └── Auth.php                # RBAC Guard Middleware (8+1 Roles)
│   ├── controllers/
│   │   ├── HomeController.php       # Landing Page Publik & Quick Stats
│   │   ├── AuthController.php       # Autentikasi Backoffice & Role Switcher
│   │   ├── ReportController.php     # Pengaduan Warga, Live Tracking & SP2HP Print
│   │   ├── SpktController.php       # Meja Triage SPKT & Disposisi Satker
│   │   ├── SocialMediaController.php # Omnichannel Social Media Ingestion
│   │   ├── SatkerController.php     # Inbox Satker & Sprintgas Kanit
│   │   ├── InvestigatorController.php # Workspace Penyelidikan & SP2HP-E
│   │   └── ExecutiveController.php  # Situational Command Center Kapolda & GIS Heatmap
│   ├── models/
│   │   ├── User.php
│   │   ├── Report.php
│   │   ├── SocialFeed.php
│   │   ├── Satker.php
│   │   └── Feedback.php
│   └── views/
│       ├── layouts/
│       │   ├── main.php            # Layout Publik Presisi Polri
│       │   ├── admin.php           # Layout Backoffice Operasional
│       │   └── executive.php       # Layout Situational Command Center Kapolda
│       ├── auth/
│       │   └── login.php           # Halaman Login Backoffice
│       ├── public/
│       │   ├── index.php           # Landing Page
│       │   ├── create_report.php   # Form Multi-Step Pengaduan & GPS Leaflet
│       │   ├── success.php         # Kartu Kredensial Tiket & PIN
│       │   ├── track.php           # Lacak Status & SP2HP Elektronik
│       │   └── sp2hp_print.php     # Dokumen Resmi SP2HP (A4 Printable)
│       ├── backoffice/
│       │   ├── spkt_triage.php     # Meja Verifikasi SPKT
│       │   ├── social_triage.php   # Triage Aduan Medsos (X/IG/TikTok)
│       │   ├── satker_inbox.php    # Inbox Satker & Penugasan
│       │   ├── investigator_list.php # Kasus Aktif Penyidik
│       │   └── case_detail.php     # Lembar Kerja Penyidik, Ruang Klarifikasi & Form SP2HP
│       └── executive/
│           └── dashboard.php       # Dashboard Kapolda, GIS Heatmap & Atensi Pimpinan
│
├── database/
│   ├── schema.sql                  # DDL MySQL 10 Tabel Relasional (Termasuk case_messages)
│   └── seeders.sql                 # Seeder Master Satker, 9 Akun Demo & Dialog Klarifikasi
│
├── docs/
│   ├── PRD_SIGAP_POLDA.md          # Dokumen PRD Resmi
│   ├── TASK.md                     # Checklist Rencana Implementasi
│   └── WALKTHROUGH.md              # Panduan Penggunaan Aplikasi
│
└── public/
    ├── index.php                   # Single Entry Point (PSR-4 Autoloader)
    ├── .htaccess                   # Rewrite Engine Apache
    └── assets/
        ├── css/custom-polri.css    # Styling Token Resmi Polri, Palet Marun/Gold & Ribbon Merah Putih
        └── img/
            ├── logo-polisi-masyarakat.png # Emblem Resmi "POLISI BERSAMA MASYARAKAT"
            ├── logo-polri.png      # Logo Emblem Resmi Alternatif
            ├── logo-polri.svg      # Lambang Tribrata POLRI "Rastra Sewakotama"
            ├── logo-presisi.svg    # Logo Resmi Program Kapolri "Polri Presisi"
            └── badge-kapolda.svg   # Emblem Bintang Dua & Tanda Atensi Kapolda
```

---

## 🤝 Fitur Baru: Ruang Klarifikasi Dua Arah (Polisi Bersama Masyarakat)
Sesuai filosofi emblem kemitraan warga dan kepolisian, sistem SIGAP-POLDA kini dilengkapi modul komunikasi dua arah terenkripsi:
1. **Sisi Warga (Pelapor):** Pada halaman [Lacak Status Tiket](file:///d:/xampp/htdocs/SIGAP-Polda/public/report/track), warga dapat langsung membaca pesan klarifikasi dari penyidik, mengirim jawaban, serta mengunggah file bukti susulan.
2. **Sisi Penyidik:** Pada [Lembar Kerja Kasus](file:///d:/xampp/htdocs/SIGAP-Polda/public/investigator/detail/1), penyidik dapat mengirimkan pertanyaan resmi atau undangan klarifikasi langsung ke pelapor.
3. **Pengawasan Pimpinan:** Percakapan tercatat dalam Berita Acara Perkara dan diawasi secara transparan oleh Kanit, Kasatker, dan Irwasda/Propam.

