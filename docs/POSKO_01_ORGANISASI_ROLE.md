# 🏛️ TAHAP 1 — ORGANISASI, ROLE & HAK AKSES BERJENJANG

Rujukan: BRD Bab 2.1 (struktur organisasi), Bab 3 (role & matriks hak akses), Bab 9.1 (otorisasi). Lihat asumsi di [POSKO_00_ROADMAP.md](POSKO_00_ROADMAP.md).

**Kondisi saat ini:** tabel `satkers` sudah memiliki `parent_id` dan tipe `MABES/POLDA/DIREKTORAT/POLRES/POLSEK`, tetapi data demo hanya berisi Mabes + Polda. Hak akses SIGAP berbasis **fungsi** (SPKT, Satker, Penyidik, dst.) dan Satker hanya melihat datanya sendiri — belum ada konsep "unit di bawahnya".

**Output tahap:** setiap pengguna posko terikat ke satu unit dan satu tingkat; sistem tahu unit mana saja yang berada di bawahnya; akses lintas organisasi ditolak di server.

---

## 🔴 Prioritas Tinggi

### P1-01 — Hierarki organisasi 4 tingkat
- [ ] **Masalah:** belum ada data & helper hierarki Polsek → Polres → Polda → Pusat; sistem tidak dapat menjawab "unit apa saja di bawah Polda X".
- **Solusi:**
  - Gunakan `satkers` yang ada; tipe `MABES` = tingkat **Pusat**. Tambahkan kolom `level` turunan (1 Polsek, 2 Polres, 3 Polda, 4 Pusat) atau fungsi pemetaan tipe → tingkat.
  - Tambahkan kolom `path` (materialized path, mis. `/1/7/21/`) agar query "seluruh unit di bawah X" cukup `WHERE path LIKE '/1/7/%'` dan ber-indeks.
  - Model `App\Models\Satker`: `descendantIds(int $id)`, `ancestors(int $id)`, `childrenOf(int $id)`, validasi induk (Polsek wajib berinduk Polres, Polres berinduk Polda, Polda berinduk Pusat).
  - Seeder: tambah 2–3 Polres per Polda dan 2–3 Polsek per Polres (data demo fiktif).
- **Kriteria selesai:**
  - [ ] Migrasi `level` + `path` dan pengisian otomatis untuk data lama.
  - [ ] Test: `descendantIds` untuk Pusat mencakup seluruh unit; untuk Polres hanya Polsek di bawahnya.
  - [ ] Validasi menolak induk yang salah tingkat.

### P1-02 — Role posko per tingkat
- [ ] **Masalah:** role BRD (Admin/Operator per tingkat, Executive 1–3, Admin Pusat, System Administrator) belum ada.
- **Solusi:**
  - Pisahkan **peran fungsi** dari **tingkat**: role baru `ROLE_POSKO_ADMIN`, `ROLE_POSKO_OPERATOR`, `ROLE_POSKO_EXECUTIVE`; tingkatnya diambil dari `users.satker_id` → `satkers.level`. Contoh: Operator Polres = `ROLE_POSKO_OPERATOR` + satker bertipe POLRES.
  - `ROLE_SUPERADMIN` yang ada = System Administrator (konfigurasi global), **dipisah** dari hak operasional (BRD 9.1).
  - Label tampilan di `Constants::getRoleLabel` menyebut tingkat (mis. "Operator Posko Polres").
  - Pengguna SIGAP lama boleh merangkap akses posko lewat tabel `user_posko_roles` (opsional) — putuskan saat implementasi; default: satu role per akun.
- **Kriteria selesai:**
  - [ ] Enum role diperluas lewat migrasi; akun demo untuk setiap kombinasi role × tingkat.
  - [ ] Quick login development untuk akun posko demo.

### P1-03 — Kebijakan akses berbasis wilayah (`PoskoAccess`)
- [ ] **Masalah:** belum ada aturan "melihat laporan unit di bawahnya" sesuai matriks BRD 3.2.
- **Solusi:** kelas `App\Core\PoskoAccess` (pola sama dengan `ReportAccess`):
  - `scopeSatkerIds(): int[]` — unit sendiri + seluruh turunannya (Pusat = semua).
  - `canView(report)`, `canEdit(report)` (hanya unit pembuat & status draft/perlu perbaikan), `canVerify(report)` (hanya tingkat tepat di atas unit pengirim, atau sesuai konfigurasi), `canForward(report)`.
  - Semua query daftar laporan posko **wajib** memakai `scopeSatkerIds()`.
- **Kriteria selesai:**
  - [ ] Test lintas organisasi: Operator Polres A tidak dapat membuka/mengubah laporan Polsek di bawah Polres B (404/403), termasuk lewat URL langsung.
  - [ ] Test Executive Polda hanya melihat wilayah Polda-nya.

---

## 🟠 Prioritas Sedang

### P1-04 — Manajemen pengguna (UI)
- [ ] **Masalah:** akun hanya bisa dibuat lewat seeder/SQL.
- **Solusi:** halaman `/posko/users`: Admin tingkat X membuat/menonaktifkan akun **hanya** untuk unit di dalam wilayahnya dan **tidak** dapat memberi role lebih tinggi dari wewenangnya; reset kata sandi oleh admin memaksa ganti sandi saat login berikutnya.
- **Kriteria selesai:**
  - [ ] Admin Polres tidak dapat membuat akun Polda atau akun di Polres lain.
  - [ ] Aksi tercatat di audit (`posko.user.create`, `posko.user.disable`, `posko.user.reset_password`).

### P1-05 — Manajemen organisasi (UI)
- [ ] **Masalah:** penambahan/pemindahan unit belum dapat dilakukan dari aplikasi.
- **Solusi:** halaman `/posko/organizations` (System Administrator & Admin Pusat): tambah unit, ubah nama/kode, pindah induk (memperbarui `path` seluruh turunan dalam satu transaksi), nonaktifkan unit (tidak dihapus bila sudah memiliki laporan).
- **Kriteria selesai:**
  - [ ] Pindah induk memperbarui `path` turunan secara atomik (test).
  - [ ] Unit nonaktif tidak muncul sebagai wajib lapor, tetapi laporan lamanya tetap dapat ditelusuri.

### P1-06 — Navigasi & layout modul posko
- [ ] **Solusi:** grup menu "Management Posko" di sidebar admin, tampil sesuai role; header menampilkan nama & tingkat posko pengguna (BRD 7.2).
- **Kriteria selesai:**
  - [ ] Menu tidak tampil untuk role yang tidak berwenang, dan route tetap menolak bila dibuka langsung.
