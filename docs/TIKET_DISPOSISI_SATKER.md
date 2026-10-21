# Tiket Disposisi ke Satker Fungsi

SPKT mengirim laporan sebagai **tiket** ke satuan yang berwenang. Satuan tujuan bisa direktorat Mabes, Polda, Polres, Polsek, atau satuan fungsinya. Satker tujuan wajib **menerima** tiket sebelum batas waktu. Setelah itu satker dapat **meneruskan** tiket ke jajaran, atau **mengembalikan** ke SPKT bila bukan kewenangannya.

## Pemasangan

```bash
mysql -uroot sigap_polda < database/migrations/2026_10_21_satker_fungsi_ticketing.sql
mysql -uroot sigap_polda < database/seeders_tiket_satker.sql   # opsional: akun demo
```

Migrasi bersifat idempoten. Satuan fungsi dibentuk otomatis dari data Polda dan Polres yang sudah ada:

| Wilayah | Satuan fungsi yang dibentuk |
|---|---|
| Mabes | Dittipidum, Dittipidsiber, Dittipidnarkoba, Korlantas, Divpropam (diberi kode fungsi) |
| Polda | Ditreskrimum (+ Subdit Jatanras), Ditreskrimsus, Ditresnarkoba, Ditressiber, Ditlantas, Ditsamapta, Bidpropam |
| Polres | Satreskrim, Satresnarkoba, Satlantas, Satsamapta, Sipropam |

Satuan fungsi bertipe `DIREKTORAT` dengan kolom `function_code`. Dengan begitu satuan ini tidak ikut kewajiban lapor Management Posko. Polres atau Polsek baru yang ditambahkan nanti dapat diberi satuan fungsi dengan menjalankan ulang migrasi.

Cron pemantau SLA dijalankan tiap 15 menit:

```bash
php scripts/disposition_watchdog.php
```

## Rekomendasi tujuan

1. **Fungsi** ditentukan dari kategori laporan (`report_categories.function_code`): Kriminal Umum → Reskrimum, Jatanras → Jatanras, Krimsus → Reskrimsus, Siber → Siber, Narkoba → Narkoba, Lantas → Lantas, Propam → Propam, Kamtibmas → Samapta.
2. **Wilayah** ditentukan dari lokasi kejadian, dengan urutan pencocokan:
   1. nama Polres/Polsek pada alamat TKP;
   2. bila tidak ada, Polda terdekat dari koordinat TKP;
   3. bila tidak ada keduanya, direktorat Mabes.
3. Bila satuan fungsi tidak tersedia di wilayah tersebut, dipakai fungsi pengganti: Jatanras → Reskrimum, Siber → Reskrimsus → Reskrimum. Contoh: kasus Jatanras di Polres diarahkan ke Satreskrim.

Petugas SPKT tetap memilih tujuan akhir. Pemilihannya dua tahap: wilayah dulu, lalu satuan penerima.

## Alur & SLA

| Aksi | Siapa | Efek |
|---|---|---|
| Kirim / disposisi ulang | SPKT | Tiket `PENDING`; tiket sebelumnya `REVOKED`; penyidik lama dicabut |
| Terima | Satker tujuan | Tiket `ACCEPTED` (otomatis juga saat Sprintgas diterbitkan) |
| Teruskan | Satker pemegang | Tiket lama `FORWARDED`, tiket baru `PENDING` ke jajaran |
| Kembalikan ke SPKT | Satker pemegang, sebelum penyidik ditunjuk | Tiket `RETURNED`, laporan kembali `VERIFIED_SPKT` |

Batas waktu menerima tiket ditentukan dari urgensi laporan: **EMERGENCY 1 jam, HIGH 6 jam, NORMAL 24 jam**. Tiket yang lewat batas dieskalasi dan tampil di meja SPKT serta inbox satuan induk. Eskalasi juga dicatat di timeline internal laporan.

Jangkauan **Teruskan**:

- Satuan fungsi dapat meneruskan ke seluruh jajaran wilayah induknya. Contoh: Ditreskrimum Polda Metro Jaya dapat meneruskan ke satuan mana pun di Polda Metro Jaya, termasuk Polres dan Polsek.
- Direktorat Mabes dapat meneruskan ke satuan fungsi sejenis di Polda atau Polres.

## Akun demo (sandi `password123`)

| Akun | Satuan |
|---|---|
| `dirreskrimum.metro@sigap.polri.go.id` | Ditreskrimum Polda Metro Jaya (pimpinan) |
| `kasubdit.jatanras.metro@sigap.polri.go.id` | Subdit Jatanras Ditreskrimum Polda Metro Jaya |
| `kasubdit.gakkum.metro@sigap.polri.go.id` | Ditlantas Polda Metro Jaya |
| `kasatreskrim.jakbar@sigap.polri.go.id` | Satreskrim Polres Metro Jakarta Barat |
| `penyidik.reskrim.jakbar@sigap.polri.go.id` | Penyidik Satreskrim Polres Metro Jakarta Barat |
| `kasatlantas.jakbar@sigap.polri.go.id` | Satlantas Polres Metro Jakarta Barat |
