# 📋 IMPLEMENTATION PLAN & TASK LIST (SIGAP-POLDA)
## Sistem Informasi & Pengaduan Masyarakat Terpadu POLDA

---

## 🎯 Goal Description
Membangun sistem pengaduan masyarakat (*case management system*) **SIGAP-POLDA** secara *end-to-end* tanpa framework (PHP Native MVC murni), database MySQL, styling Tailwind CSS bernuansa Korps Kepolisian Republik Indonesia (Navy Blue `#0A192F` / `#1E3A8A` & Gold Brass `#D97706`), lengkap dengan atribut/logo resmi POLRI (Rastra Sewakotama & Presisi). Sistem menangani pelaporan omnichannel (Web Portal, WhatsApp OTP, dan Social Media Ingestion), alur triage SPKT, penugasan Satker/Penyidik, penerbitan SP2HP Elektronik, serta Dashboard Executive Kapolda (GIS Heatmap & Atensi Pimpinan).

---

## ⚠️ User Review Required
> [!IMPORTANT]
> **Atribut Visual & Logo POLRI:**
> Sistem menyertakan aset visual logo resmi Kepolisian Negara Republik Indonesia (*Rastra Sewakotama*) dan tagline *Polri Presisi (Prediktif, Responsibilitas, Transparansi Berkeadilan)* dalam format Vector SVG murni yang tajam, elegan, dan bekerja secara offline di lingkungan lokal (XAMPP).

> [!NOTE]
> **Kredensial Default Seeder untuk 9 Roles:**
> Untuk mempermudah pengujian alur *end-to-end*, sistem dilengkapi seeder akun dinas untuk masing-masing role (Superadmin, Kapolda, Ka SPKT, Kasatker, Kanit, Penyidik, Humas/Siber, Irwasda/Propam, dan Warga).

---

## 🗂️ Proposed Changes & Task Breakdown

### 🎨 Phase 1: Brand Assets & Visual Identity POLRI
*Menyiapkan identitas visual Korps Bhayangkara & arsitektur antarmuka.*
- [x] **[NEW] `public/assets/img/logo-polri.svg`**: Logo Lambang Tribrata POLRI *Rastra Sewakotama* beresolusi tinggi (perisai, bintang 3 emas, burung tiung emas, dan pita semboyan).
- [x] **[NEW] `public/assets/img/logo-presisi.svg`**: Logo resmi Program Kapolri *Polri Presisi*.
- [x] **[NEW] `public/assets/img/badge-kapolda.svg`**: Atribut bintang emas & tanda atensi pimpinan (*Kapolda's Special Attention Flag*).
- [x] **[NEW] `public/assets/css/custom-polri.css`**: Tailwind config & token warna khusus (Polri Navy, Gold Brass, Glassmorphism Command Center, Card Glowing Alerts).

---

### 🏛️ Phase 2: Core MVC Engine & Database Migration
*Membangun pondasi arsitektur PHP Native MVC tanpa framework.*
- [x] **[NEW] `database/schema.sql`**: Skema DDL MySQL lengkap untuk 9 tabel utama (`users`, `satkers`, `social_feeds`, `reports`, `report_attachments`, `report_timeline`, `kapolda_directives`, `report_feedbacks`) beserta Foreign Keys & Indexing.
- [x] **[NEW] `database/seeders.sql`**: Seeder master data Satker (Ditreskrimum, Ditreskrimsus, Ditlantas, Ditresnarkoba, Bidpropam, Polresta/Polres jajaran) dan 9 akun pengguna pengujian.
- [x] **[NEW] `app/config/Database.php`**: Singleton PDO MySQL dengan konfigurasi keamanan `ATTR_ERRMODE_EXCEPTION`, `ATTR_DEFAULT_FETCH_MODE`, dan Prepared Statements.
- [x] **[NEW] `app/config/Config.php` & `app/config/Constants.php`**: Konstanta App, Base URL dinamis, Enums Status Perkara, Roles, dan SLA default.
- [x] **[NEW] `app/core/App.php`**: Routing Engine berbasis URL Parsing (`controller/action/params`) dan `.htaccess` Rewrite.
- [x] **[NEW] `app/core/Controller.php`**: Base Controller (fungsi `view()`, `json()`, CSRF validation, sanitization helper).
- [x] **[NEW] `app/core/Model.php`**: Base Model PDO Query Helper.
- [x] **[NEW] `app/core/Session.php`**: Secure Session Management (anti-session hijacking, flash message, CSRF token generator).
- [x] **[NEW] `app/core/Auth.php`**: RBAC Guard Middleware (pembatasan akses spesifik per role).
- [x] **[NEW] `public/index.php` & `public/.htaccess`**: Single entry point aplikasi.

---

### 🔐 Phase 3: Autentikasi & Multi-Role User Management
*Sistem autentikasi aman session-based dan verifikasi OTP.*
- [x] **[NEW] `app/controllers/AuthController.php`**:
  - Login Backoffice Polri (NRP/Email + Password bcrypt).
  - Login/Registrasi Pelapor (NIK KTP + WhatsApp OTP Gateway Simulator).
  - Logout aman & pembersihan session.
- [x] **[NEW] `app/models/User.php`**: Model manipulasi data user, autentikasi, dan pengecekan role/satker.
- [x] **[NEW] `app/views/auth/login.php`**: Halaman login backoffice modern bernuansa Command Center Polri.

---

### 🌐 Phase 4: Portal Publik Pengaduan Masyarakat (Citizen Portal)
*Portal pengaduan warga yang responsif, terpercaya, dan transparan.*
- [x] **[NEW] `app/views/layouts/main.php`**: Master layout publik bertema Presisi Polri dengan navbar resmi, logo Polri, dan footer hotline 110.
- [x] **[NEW] `app/controllers/HomeController.php`**: Landing page edukatif, transparansi alur pengaduan, dan call-to-action pengaduan.
- [x] **[NEW] `app/views/public/index.php`**: Landing page presisi dengan hero banner, quick stats penyelesaian kasus, dan tombol *Buat Laporan*.
- [x] **[NEW] `app/controllers/ReportController.php`**:
  - `create()` & `store()`: Multi-step submit laporan baru (NIK, Nama, Kontak, TKP Koordinat Leaflet, Kronologi, Multi-upload Bukti).
  - Opsi *Whistleblower Protection* ("Rahasiakan Identitas Saya").
  - `track()`: Live tracking perkembangan perkara berdasarkan Nomor Registrasi Laporan.
- [x] **[NEW] `app/views/public/create_report.php`**: Form multi-step dengan live GPS Pinpoint (Leaflet.js) dan upload drag-and-drop.
- [x] **[NEW] `app/views/public/track.php`**: Halaman pelacakan publik menampilkan visualisasi milestone penanganan dan tombol download SP2HP Elektronik PDF.

---

### 📱 Phase 5: Omnichannel Social Media Ingestion (Workspace Humas/Siber)
*Modul penanganan pengaduan dari media sosial (X, Instagram, Facebook, TikTok).*
- [x] **[NEW] `app/models/SocialFeed.php`**: Pengelolaan data incoming mention, DM, dan postingan viral.
- [x] **[NEW] `app/controllers/SocialMediaController.php`**:
  - Triage antrean keluhan dari media sosial.
  - Fitur *"1-Click Convert to Official Police Ticket"*.
  - Generator auto-response DM / Reply dengan nomor tiket.
- [x] **[NEW] `app/views/backoffice/social_triage.php`**: Antarmuka Social Media Inbox dengan filter sentimen, badge medsos, dan konversi cepat.

---

### 👮‍♂️ Phase 6: Backoffice SPKT & Satuan Kerja Operasional
*Alur kerja terpadu penanganan kasus dan penerbitan SP2HP.*
- [x] **[NEW] `app/views/layouts/admin.php`**: Master layout dashboard backoffice dengan sidebar dinamis berbasis RBAC.
- [x] **[NEW] `app/controllers/SpktController.php` & `app/views/backoffice/spkt_triage.php`**:
  - Meja Triage SPKT: verifikasi kelayakan laporan, tolak hoax, dan form disposisi pintar ke Dit/Polres.
- [x] **[NEW] `app/controllers/SatkerController.php` & `app/views/backoffice/satker_inbox.php`**:
  - Penerimaan disposisi di tingkat Satker/Polres.
  - Penunjukan Penyidik Pelaksana / Petugas Lapangan.
- [x] **[NEW] `app/controllers/InvestigatorController.php` & `app/views/backoffice/case_detail.php`**:
  - Workspace investigasi: pemisahan catatan internal intelijen vs catatan publik.
  - Penerbitan SP2HP Elektronik (format A1/A2 resmi bertanda tangan digital).
  - Submit pengajuan penutupan perkara dengan Berita Acara (BAP).

---

### 🎖️ Phase 7: Executive Dashboard Kapolda (Command Center PJU)
*Dashboard strategis pimpinan dengan visualisasi komprehensif.*
- [x] **[NEW] `app/views/layouts/executive.php`**: Layout khusus layar pimpinan / Command Center Polda.
- [x] **[NEW] `app/controllers/ExecutiveController.php`**:
  - Agregasi data KPI: Clearance Rate, Response Time, Bottleneck Watchlist Satker/Polres.
  - Data titik GIS untuk Heatmap Kriminalitas.
  - Radar Kasus Viral & Sentimen Media Sosial.
  - Action Controller *"Beri Atensi Kapolda"* (prioritas bintang merah).
- [x] **[NEW] `app/views/executive/dashboard.php`**: Dashboard Kapolda dengan peta Leaflet Heatmap, grafik Chart.js, feed kasus atensi, dan dialog instruksi pimpinan.

---

### ⭐ Phase 8: Survei Kepuasan Masyarakat (SKM) & Final Polish
*Loop umpan balik transparansi dan pengujian sistem.*
- [x] **[NEW] `app/models/Feedback.php`**: Model pencatatan indeks kepuasan masyarakat (bintang 1-5 & catatan ulasan).
- [x] **[NEW] `app/views/public/feedback.php`**: Form penilaian layanan publik setelah kasus selesai.
- [x] **[NEW] Testing & Security Audit**: Pengujian alur end-to-end, verifikasi Prepared Statements, dan RBAC protection.

---

### 🤝 Phase 9: Logo Resmi & Tema "Polisi Bersama Masyarakat" serta Ruang Klarifikasi Dua Arah
*Penyelarasan tema visual dan fitur komunikasi transparan warga-penyidik.*
- [x] **[NEW] `public/assets/img/logo-polisi-masyarakat.png`**: Integrasi emblem resmi *"POLISI BERSAMA MASYARAKAT - MELINDUNGI, MENGAYOMI DAN MELAYANI"* dengan transparansi piksel presisi.
- [x] **[NEW] `public/assets/css/custom-polri.css`**: Penambahan palet warna Marun Korps (`#7F1D1D`), Merah Crimson (`#991B1B`), Gold Brass, dan aksen garis Sang Saka Merah-Putih (`.ribbon-merah-putih`).
- [x] **[NEW] Update Seluruh Layout & Halaman Utama**:
  - `app/views/layouts/main.php` (Header Ribbon Merah-Putih & Footer Kemitraan Polisi-Warga)
  - `app/views/layouts/admin.php` (Sidebar Backoffice & Branding)
  - `app/views/layouts/executive.php` (Command Center Kapolda)
  - `app/views/public/index.php` (Hero Showcase Emblem Polisi Bersama Masyarakat)
  - `app/views/auth/login.php` (Kartu Autentikasi Personil POLRI)
  - `app/views/public/create_report.php` (Watermark Presisi)
  - `app/views/public/sp2hp_print.php` (Kop Surat Resmi SP2HP Elektronik)
- [x] **[NEW] Ruang Klarifikasi & Komunikasi Dua Arah Warga - Tim Penyidik (`case_messages`)**:
  - Database table `case_messages` dengan relasi ke `reports` dan `users`.
  - Model method `Report::getCaseMessages()` dan `Report::addCaseMessage()`.
  - Controller actions: `ReportController::sendMessage()` (warga) dan `InvestigatorController::sendMessage()` (penyidik).
  - UI Chatbox Interaktif di portal pelacakan tiket (`app/views/public/track.php`).
  - Workspace Chatbox di lembar kerja penyidik (`app/views/backoffice/case_detail.php`) dengan jejak audit supervisi pimpinan.

---

## 🧪 Verification Plan

### Manual Verification Flow:
1. **Verifikasi Branding Polisi Bersama Masyarakat:** Buka browser dan pastikan logo emblem *"Polisi Bersama Masyarakat"* (siluet polisi-warga, jabat tangan erat, padi-kapas, pita semboyan, dan bintang 3 Tribrata) serta garis pita merah-putih tampil harmonis pada portal publik, meja backoffice, dan dashboard pimpinan.
2. **Citizen Flow (Warga):**
   - Warga mengajukan aduan curanmor melalui form web dengan menyematkan koordinat lokasi dan bukti foto.
   - Warga menerima nomor tiket unik `LP-POLDA-XXXX`.
3. **Omnichannel Flow (Medsos):**
   - Operator Humas membuka menu *Social Triage*, melihat mention viral X/Instagram, dan mengklik *Konversi ke Tiket Resmi*.
4. **SPKT Triage:**
   - Operator SPKT memverifikasi tiket dan mendisposisikan ke *Ditreskrimum Polda*.
5. **Satker & Penyidik Handling:**
   - Kanit Satker menunjuk Penyidik Ditreskrimum.
   - Penyidik membuka lembar perkara di workspace, menerbitkan SP2HP Elektronik A-1, dan mengirimkan pesan klarifikasi kepada pelapor.
6. **Citizen-Police Clarification (Komunikasi Dua Arah):**
   - Warga membuka portal tracking tiket, membaca pesan penyidik, dan langsung membalas pertanyaan penyidik beserta lampiran bukti baru.
   - Percakapan tercatat dalam Berita Acara dan dapat diaudit oleh Kanit, Kasatker, dan Irwasda.
7. **Executive Kapolda Flow:**
   - Kapolda login, melihat titik perkara di GIS Heatmap, dan mengklik tombol *"Beri Atensi Kapolda"*.
   - Tiket bertanda prioritas bintang merah dan mendapat percepatan penanganan.
8. **Penyelesaian & SKM:**
   - Penyidik menyelesaikan kasus, warga menerima notifikasi dan mengisi survei kepuasan bintang 5.
