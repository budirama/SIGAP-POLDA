# 🗺️ ROADMAP: MODUL MANAGEMENT POSKO (PELAPORAN BERJENJANG)

Sumber kebutuhan: `docs/BRD-Management Posko V-01.pdf` (Draft Blueprint Teknis v1.0).
Analisis kesenjangan dilakukan 15 Oktober 2026 terhadap SIGAP-POLRI di `main`.

Setiap task dikerjakan di branch terpisah → commit → push → PR → merge ke `main`, dengan test otomatis (`php tests/run.php`) tetap hijau dan test baru untuk setiap perilaku baru.

---

## 🎯 Ringkasan

BRD menggambarkan **pelaporan operasional internal posko** yang mengalir **dari bawah ke atas** (Polsek → Polres → Polda → Pusat) dengan verifikasi, rekapitulasi, dan monitoring kepatuhan di setiap tingkat. Ini berbeda dari modul SIGAP yang sudah ada (aduan warga yang didisposisi **dari atas ke bawah** sampai SP2HP), sehingga dibangun sebagai **modul baru** yang berbagi fondasi platform.

| Fondasi SIGAP yang dipakai ulang | Dibangun baru |
|---|---|
| Login, sesi aman, throttle, CSRF, header keamanan | Hierarki akses 4 tingkat & role per tingkat |
| Tabel `satkers` (`parent_id`, tipe POLSEK/POLRES/POLDA/MABES) | Jenis laporan & formulir yang dapat dikonfigurasi |
| Jejak audit (`audit_logs`, `App\Core\Audit`) | Workflow kirim → verifikasi → kembalikan → setujui → teruskan |
| Penyimpanan unggahan privat (`App\Core\Upload`) | Revisi/versi laporan, rekapitulasi berjenjang, laporan nihil |
| Penomoran atomik (`ticket_sequences`) sebagai pola | Monitoring kepatuhan, notifikasi, ekspor PDF/Excel, REST API |
| Tema UI, layout backoffice, paginasi | Dashboard per tingkat dengan drill-down |

## ⚖️ Asumsi & Keputusan (perlu dikonfirmasi pemilik produk)

| # | Hal | Asumsi saat ini | Status |
|---|---|---|---|
| A1 | Bentuk aplikasi | **Modul di dalam SIGAP** (route `/posko/...`), bukan aplikasi terpisah | Rekomendasi, belum dikonfirmasi |
| A2 | Daftar jenis laporan & isiannya | Mulai dengan 4 jenis contoh: Laporan Harian, Laporan Periodik (Mingguan), Laporan Kejadian Khusus, Laporan Insidentil | Menunggu daftar resmi |
| A3 | Tenggat pelaporan | Harian: pukul 23.59 hari yang sama; Mingguan: Senin 12.00 minggu berikutnya | Menunggu ketentuan resmi |
| A4 | Persetujuan sebelum diteruskan | **Dapat dikonfigurasi per jenis laporan** (BRD 2.2); default: wajib disetujui | Sesuai BRD |
| A5 | Data organisasi | Data demo: 1 Pusat, 5 Polda, 2–3 Polres per Polda, 2–3 Polsek per Polres | Menunggu data resmi Polres/Polsek |
| A6 | Hubungan dengan aduan warga | Terpisah; laporan posko boleh **merujuk** nomor tiket aduan (opsional), tidak menggantikan alur SP2HP | Rekomendasi |
| A7 | Notifikasi | Dalam aplikasi + email (SMTP dari `.env`); WhatsApp/SMS di luar cakupan (BRD 4.6) | Sesuai BRD |
| A8 | SSO | Di luar cakupan tahap ini; disiapkan titik integrasinya saja | Menunggu infrastruktur instansi |

> Perubahan asumsi di atas cukup dicatat di tabel ini, lalu task terkait disesuaikan.

---

## 🧭 Tahapan & File Task

| Tahap | File | Isi | Bergantung pada | Rujukan BRD |
|---|---|---|---|---|
| 1 | [POSKO_01_ORGANISASI_ROLE.md](POSKO_01_ORGANISASI_ROLE.md) | Hierarki 4 tingkat, role per tingkat, pembatasan wilayah, manajemen pengguna & organisasi | — | Bab 2.1, 3, 9.1 |
| 2 | [POSKO_02_INPUT_LAPORAN.md](POSKO_02_INPUT_LAPORAN.md) | Jenis laporan terkonfigurasi, formulir dinamis, draft, lampiran, nomor laporan, periode & tenggat | Tahap 1 | Bab 4.2, 7.3, 9.3 |
| 3 | [POSKO_03_WORKFLOW_BERJENJANG.md](POSKO_03_WORKFLOW_BERJENJANG.md) | Kirim, verifikasi, kembalikan, setujui, teruskan, revisi & versi, eskalasi mendesak | Tahap 2 | Bab 4.3, 5, 9.3 |
| 4 | [POSKO_04_REKAP_NIHIL_KEPATUHAN.md](POSKO_04_REKAP_NIHIL_KEPATUHAN.md) | Rekapitulasi berjenjang, laporan nihil, monitoring kepatuhan | Tahap 3 | Bab 2.2–2.3, 4.4–4.5 |
| 5 | [POSKO_05_DASHBOARD_EKSPOR_NOTIFIKASI.md](POSKO_05_DASHBOARD_EKSPOR_NOTIFIKASI.md) | Dashboard per tingkat + drill-down, ekspor PDF/Excel, notifikasi | Tahap 4 | Bab 4.1, 4.6–4.7, 7.2, 7.5 |
| 6 | [POSKO_06_API_KEAMANAN_UAT.md](POSKO_06_API_KEAMANAN_UAT.md) | REST API v1, pemulihan akses, audit lanjutan, backup, kinerja, kriteria penerimaan | Tahap 1–5 | Bab 6, 8, 9, 10, 12 |

## 📐 Konvensi Teknis Modul

- **Namespace & route:** controller `PoskoController` (dan turunannya bila perlu), route `/posko/*`; model diawali `Posko` (mis. `App\Models\PoskoReport`).
- **Tabel baru diawali `posko_`** agar tidak tercampur dengan tabel aduan warga (`reports`, `report_timeline`, dst.).
- **Setiap perubahan skema** = file migrasi baru di `database/migrations/` + pembaruan `database/schema.sql` + data demo di `database/seeders.sql`.
- **Transisi status selalu atomik di server** (`UPDATE ... WHERE status IN (...)`), mengikuti pola disposisi SIGAP; frontend tidak menentukan status.
- **Tidak ada penghapusan** laporan yang sudah dikirim/disetujui; gunakan pembatalan, koreksi, atau revisi yang tercatat (BRD 3.2, 5.3).
- **Pembatasan wilayah** diterapkan di query (bukan hanya menyembunyikan tombol) dan diuji lintas organisasi.
- **Audit** setiap aksi workflow lewat `App\Core\Audit::log('posko.*', ...)`.

## ✅ Definisi Selesai (berlaku untuk semua task)

1. Fitur bekerja di desktop & HP (lebar 390 px) tanpa error konsol.
2. Test integrasi baru mencakup jalur sukses **dan** penolakan akses lintas organisasi.
3. `php tests/run.php` hijau.
4. Migrasi + `schema.sql` + seeder diperbarui dan dapat dijalankan ulang tanpa error.
5. Checkbox task di file terkait dicentang dan dicatat di commit.
