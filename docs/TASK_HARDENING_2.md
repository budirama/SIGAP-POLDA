# 🛡️ TASK LIST: HARDENING TAHAP 2 (REVIEW ULANG SIGAP-POLDA)

Hasil review ulang 15 Oktober 2026 setelah PR #1–#11 (lihat `docs/TASK_HARDENING.md`). Setiap task dikerjakan di branch terpisah → commit → Pull Request → merge ke `main`, dengan test otomatis (`php tests/run.php`) tetap hijau.

## ⚖️ Keputusan Aturan Bisnis (disetujui)
1. **Itwasda (pengawas internal)** bersifat **read-only**: dapat melihat seluruh laporan & workspace kasus, tidak dapat mendisposisi/menolak/menangani.
2. **Disposisi ulang** ke Satker lain hanya diizinkan **sebelum penyidikan dimulai** (status `SUBMITTED`, `VERIFIED_SPKT`, `DISPATCHED`, `ASSIGNED`); penyidik lama dicabut. Laporan `INVESTIGATING`, `CLOSED`, `REJECTED` tidak dapat didisposisi.
3. **Panel SLA** dashboard Kapolda dihitung dari data `sla_deadline` yang nyata.
4. **Klaim fitur yang belum ada** (notifikasi WhatsApp/SMS, QR validasi) dihapus dari teks antarmuka.

---

## 🔴 Prioritas Tinggi

### U1 — Pisahkan akses pelapor vs personil & hapus PII dari source HTML
- [x] **Masalah:** `ReportController::canAccessReport` mengizinkan *semua* personil → Humas/penyidik unit lain dapat melihat PIN di halaman sukses, mengirim pesan atas nama pelapor, dan menimpa SKM. Halaman SPKT/Satker menanam `json_encode(r.*)` berisi PIN, NIK, nama & HP whistleblower ke source HTML.
- **Solusi:** halaman publik hanya untuk personil berwenang (`ReportAccess::officerCanView`) atau pelapor; aksi pelapor (halaman sukses, pesan warga, SKM) hanya untuk pelapor. Query daftar laporan memakai kolom eksplisit tanpa `security_pin`/NIK; modal hanya menerima data minimal.

### U2 — State machine disposisi & Itwasda read-only
- [x] **Masalah:** disposisi tanpa cek status (kasus CLOSED/REJECTED bisa "dibuka" kembali; penyidik lama tetap memegang akses). Itwasda dapat mendisposisi & menolak.
- **Solusi:** disposisi hanya dari status pra-penyidikan secara atomik (`UPDATE ... WHERE status IN (...)`), cabut penyidik lama saat disposisi ulang, tombol disembunyikan sesuai status; aksi SPKT hanya untuk Operator SPKT & Superadmin.

### U3 — KPI dashboard dari data nyata
- [x] **Masalah:** panel SLA hardcode "SLA Terpenuhi"; clearance rate menghitung laporan ditolak/belum diproses; KPI Atensi Kapolda ikut menghitung kasus selesai.
- **Solusi:** watchlist SLA per Satker dari `sla_deadline` (lewat SLA / mendekati SLA / sesuai), clearance rate = selesai ÷ (semua − ditolak), KPI atensi hanya kasus aktif.

### U4 — Pembatasan percobaan (login, laporan publik, PIN)
- [x] **Masalah:** login personil tanpa batas percobaan; formulir laporan publik tanpa batas (spam, disk penuh); kunci PIN per tiket dapat disalahgunakan untuk mengunci tiket warga.
- **Solusi:** `App\Core\Throttle` generik (tabel `throttle_events`) dipakai untuk login (per identitas & per IP), laporan publik (per IP), dan PIN (per tiket+IP, per IP, plafon per tiket). Login selalu menjalankan `password_verify` (anti timing).

---

## 🟠 Prioritas Sedang

### U5 — Pengerasan sesi
- [x] **Masalah:** user nonaktif/dipindah tetap memegang sesi lama; tanpa idle/absolute timeout; cookie tanpa `Secure`; `use_strict_mode` mati; fingerprint IP memutus pengguna seluler.
- **Solusi:** muat ulang user (aktif/role/satker) tiap request, timeout idle 30 menit & absolut 8 jam, `Secure` saat HTTPS, `use_strict_mode`, regenerasi ID sesi saat PIN diverifikasi, fingerprint tanpa IP.

### U6 — Akses baca per role & navigasi
- [x] **Masalah:** link Kapolda ke SPKT/Medsos/"Lihat Progres" ditolak; Kasatker & Itwasda tidak dapat membuka workspace kasus; Kanit hanya melihat kasus miliknya sendiri; daftar penyidik untuk Superadmin kosong.
- **Solusi:** workspace kasus mode baca untuk personil berwenang (`officerCanView`), aksi tetap hanya untuk penangan; navigasi sesuai hak akses; Kanit melihat seluruh kasus Satker-nya; daftar penyidik sesuai Satker laporan.

### U7 — Modul Media Sosial
- [ ] **Masalah:** satu postingan bisa dikonversi berkali-kali; postingan terkonversi bisa ditandai spam; simulasi & "Ganti Role" aktif di production; `post_url` menerima skema `javascript:`.
- **Solusi:** konversi/abaikan atomik hanya dari status `NEW` + catat petugas; validasi kategori; simulasi & ganti role hanya di development; URL hanya `http(s)`.

### U8 — Atensi Kapolda & inbox instruksi
- [ ] **Masalah:** atensi dapat diberikan ke kasus selesai & berulang; menurunkan EMERGENCY ke HIGH; target Satker hardcode; instruksi tidak pernah tampil ke Kasatker.
- **Solusi:** validasi status & duplikasi, pertahankan EMERGENCY, target mengikuti disposisi; inbox instruksi Kapolda di halaman Satker dengan aksi terima & selesaikan (catatan tanggapan).

### U9 — Alur PIN & formulir laporan publik
- [ ] **Masalah:** PIN dikirim via GET/URL & dibagikan via WhatsApp; pesan berbeda untuk tiket tak dikenal (enumerasi); NIK tidak divalidasi; laporan otomatis ditautkan ke akun pemilik NIK; isi formulir hilang saat validasi gagal; jumlah file tak dibatasi.
- **Solusi:** PIN via POST + redirect tanpa PIN, share hanya nomor tiket, pesan seragam; validasi NIK 16 digit, tanggal, kategori, maks 5 file; tidak menautkan ke akun lain berdasarkan NIK ketikan; simpan input lama.

### U10 — Dokumen SP2HP cetak & klaim fitur
- [ ] **Masalah:** nomor SP2HP cetak dikarang & berubah per bulan; tanggal kejadian ditulis sebagai tanggal registrasi; laporan ditolak tertulis "telah ditangani"; klaim WhatsApp/SMS/QR yang tidak ada.
- **Solusi:** pakai data SP2HP terakhir yang sebenarnya, hanya dapat dicetak setelah ada penyidik; hapus klaim fitur yang belum ada.

### U11 — Audit log
- [ ] **Masalah:** tidak ada jejak login/gagal login/akses data sensitif (identitas whistleblower, file bukti).
- **Solusi:** tabel `audit_logs` (user, aksi, laporan, IP, waktu) dicatat dari login, logout, workspace kasus, unduhan file & aksi status; halaman audit untuk Superadmin & Itwasda.

### U12 — Header keamanan, aktor timeline & konfigurasi DB
- [ ] **Masalah:** tanpa CSP/X-Frame-Options/Referrer-Policy; `X-Powered-By` terbuka; laporan anonim tercatat dibuat oleh Superadmin ("Diverifikasi oleh: Bripka Adhi Nugraha"); zona waktu koneksi DB tidak diset; `sql_mode` non-strict memotong data diam-diam.
- **Solusi:** header keamanan; `actor_id` timeline nullable (Sistem/Pelapor) & konversi medsos mencatat petugas; `time_zone` +07:00 & mode strict di koneksi.

---

## 🟡 Prioritas Rendah

### U13 — Kegunaan & konsistensi
- [ ] **Masalah:** daftar laporan berhenti di 100 tanpa paginasi; menu backoffice tak dapat dipakai di HP; nomor Sprintgas/SP2HP default selalu "/Ditreskrimum"; pesan dengan lampiran ditolak tetap tersimpan & tampil sukses; rating bintang tak dapat dipakai keyboard; data seeder tidak konsisten.
- **Solusi:** paginasi, menu off-canvas, sufiks kode Satker, alur lampiran ditolak yang jelas, rating aksesibel, perbaiki seeder.

---

## 📌 Di Luar Cakupan (dicatat)
- Tailwind dimuat dari `cdn.tailwindcss.com` (JIT, tanpa SRI). Solusi ideal: build CSS statis dengan Tailwind CLI (membutuhkan Node/standalone CLI di pipeline build).
- PIN disimpan plaintext di DB (6 digit; hashing tidak efektif terhadap brute-force offline). Akses DB harus dibatasi.
