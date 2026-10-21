# Chatbot Konsultasi (Asisten Virtual SIGAP)

Percakapan Live Chat baru dilayani **Asisten Virtual** lebih dulu. Asisten menjawab pertanyaan umum masyarakat dari basis pengetahuan, lalu meneruskan percakapan ke petugas Command Center bila diperlukan.

Chatbot ini **berbasis aturan**: kata kunci dicocokkan dengan topik yang dikelola petugas. Tidak ada isi percakapan yang dikirim ke layanan AI pihak ketiga.

## Pemasangan

```bash
mysql -uroot sigap_polda < database/migrations/2026_10_22_chatbot.sql
mysql -uroot sigap_polda < database/migrations/2026_10_22_chatbot_module_flag.sql   # butuh tabel system_modules
```

Isi awal berupa 20 topik:

- cara melapor, cek status, Hilang & Temu, kendaraan hilang, orang hilang;
- SKCK, SIM, STNK;
- penipuan online, KDRT/PPA, narkoba, Propam, kecelakaan, izin keramaian;
- lapor rahasia, kontak;
- darurat, bicara dengan petugas, salam, terima kasih.

Migrasi tidak menimpa jawaban yang sudah diubah petugas.

Untuk mematikan chatbot, nonaktifkan modul **Chatbot Konsultasi** (`op_chatbot`) di Manajemen Modul. Setelah itu, chat baru langsung masuk antrean petugas seperti sebelumnya.

## Alur percakapan

| Kejadian | Perilaku |
|---|---|
| Chat dimulai | Salam + menu topik (tombol cepat). Pesan pertama boleh kosong. |
| Pertanyaan cocok dengan topik | Jawaban + tautan + tombol lanjutan. |
| Nomor tiket `LP-POLRI-…` / `HT-…` disebut | Bot memberi tautan halaman status. Status tidak dibuka tanpa PIN. |
| Pertanyaan tidak dipahami | Dicatat di *Pertanyaan Belum Terjawab*. Bila terjadi kedua kalinya, bot menawarkan petugas. |
| Warga meminta petugas (topik `PETUGAS` atau tombol *Bicara dengan petugas*) | Status menjadi `WAITING` dan masuk antrean Omnichat. |
| Kata darurat (`DARURAT`: ditodong, dibegal, diculik, kebakaran, …) | Bot menyarankan menelepon 110, lalu diteruskan sebagai **PRIORITAS**. Percakapan ditandai DARURAT dan tampil teratas di Omnichat. |
| Petugas mengambil alih | Bisa dilakukan dari tab *Asisten Virtual* di Omnichat, kapan saja. |

## Pencocokan

Skor dihitung dari kata kunci yang cocok:

- frasa utuh (≥ 2 kata): bobot 3 per kata;
- kata tunggal: bobot 2;
- awalan/akhiran kata (≥ 4 huruf), misalnya "melaporkan" untuk kata kunci "lapor": bobot 1.

Skor minimal agar sebuah topik dipilih adalah 2. Bila skornya seri, topik dengan **urutan** terkecil yang menang. Karena topik darurat berurutan 1, hindari memasukkan kata umum (seperti "tolong") ke dalam kata kunci darurat.

## Mengelola basis pengetahuan

Menu **Basis Pengetahuan Chatbot** dapat dibuka oleh Operator SPKT, Humas, dan Superadmin. Itwasum hanya dapat melihat. Fitur yang tersedia:

- statistik 30 hari: percakapan dilayani bot, selesai tanpa petugas, diteruskan, darurat;
- **Uji Pertanyaan**: melihat topik dan skor yang akan dipilih bot;
- **Pertanyaan Belum Terjawab**: tombol *Buat jawaban* mengisi formulir dengan pertanyaan tersebut;
- tambah, ubah, nonaktifkan, dan hapus topik.

Tautan topik hanya boleh berupa path internal (`/report/create`), `https://`, atau `tel:`.
