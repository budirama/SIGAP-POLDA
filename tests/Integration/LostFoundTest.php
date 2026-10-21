<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

/** Kirim formulir Hilang & Temu; mengembalikan [respons, baris DB laporan] */
$submit = function (Http $client, array $fields): array {
    $csrf = $client->get('/hilangtemu/lapor')->csrf();
    $res = $client->post('/hilangtemu/store', $fields + [
        '_csrf_token'    => $csrf,
        'report_type'    => 'LOST',
        'item_category'  => 'KENDARAAN',
        'description'    => 'Motor matic warna hitam, ada stiker klub di spakbor belakang.',
        'event_date'     => date('Y-m-d'),
        'event_location' => 'Parkiran Pasar Minggu, Jakarta Selatan',
        'reporter_name'  => 'Warga Uji',
        'reporter_phone' => '081234567890',
        'reporter_nik'   => '3171000000000001',
    ]);
    $row = isset($fields['title']) ? Db::row("SELECT * FROM lost_found_items WHERE title = ? ORDER BY id DESC LIMIT 1", [$fields['title']]) : null;
    return [$res, $row];
};
$officerPost = function (Http $officer, string $path, int $itemId, array $fields = []) {
    $csrf = $officer->get("/hilangtemu/detail/{$itemId}")->csrf();
    return $officer->post($path, ['_csrf_token' => $csrf, 'item_id' => $itemId] + $fields);
};

return [
    'galeri & formulir publik tampil, tautan ada di beranda' => function () {
        $client = new Http(TEST_BASE_URL);
        T::same(200, $client->get('/hilangtemu')->status);
        $form = $client->get('/hilangtemu/lapor?type=FOUND&category=ORANG');
        T::same(200, $form->status);
        T::contains('value="FOUND" class="peer sr-only" checked', $form->body);
        T::contains('/hilangtemu', $client->get('/')->body);
    },

    'validasi: CSRF wajib & kendaraan butuh plat/rangka/mesin' => function () use ($submit) {
        $client = new Http(TEST_BASE_URL);
        $res = $client->post('/hilangtemu/store', ['title' => Db::PREFIX . 'tanpa csrf']);
        T::true(str_contains($res->location(), '/hilangtemu/lapor'), 'ditolak');
        T::same(null, Db::row("SELECT id FROM lost_found_items WHERE title = ?", [Db::PREFIX . 'tanpa csrf']));

        [$res, $row] = $submit($client, ['title' => Db::PREFIX . 'motor tanpa nomor']);
        T::same(null, $row, 'tidak tersimpan');
        T::contains('plat nomor', $client->flash());
    },

    'alur kehilangan → temuan loket → saran cocok → verifikasi → konfirmasi → SKTLK' => function () use ($submit, $officerPost) {
        Db::exec("DELETE FROM throttle_events");
        $warga = new Http(TEST_BASE_URL);
        [$res, $lost] = $submit($warga, ['title' => Db::PREFIX . 'Honda Beat hilang', 'plate_number' => 'b 1234 xyz', 'brand' => 'Honda Beat', 'color' => 'Hitam']);
        T::true(str_contains($res->location(), '/hilangtemu/sukses'), 'redirect sukses: ' . $res->location());
        T::same('PENDING', $lost['status']);
        T::same('B1234XYZ', $lost['plate_number'], 'plat dinormalisasi');
        T::true((bool)preg_match('/^HT-\d{6}-\d{5}$/', $lost['code']), 'format kode');

        $success = $warga->get('/hilangtemu/sukses')->body;
        T::contains($lost['code'], $success);
        preg_match('/PIN Keamanan<\/div>\s*<div[^>]*>(\d{6})</', $success, $m);
        $pin = $m[1] ?? '';
        T::same(6, strlen($pin), 'PIN tampil sekali');
        T::notContains($pin, $warga->get('/hilangtemu/sukses')->body, 'PIN tidak tampil lagi');

        // Belum terverifikasi: tidak tayang ke publik
        $anon = new Http(TEST_BASE_URL);
        T::same(404, $anon->get("/hilangtemu/item/{$lost['id']}")->status);
        T::notContains("/hilangtemu/item/{$lost['id']}\"", $anon->get('/hilangtemu?q=' . $lost['code'])->body);
        T::same(404, $anon->get("/hilangtemu/sktlk/{$lost['id']}")->status, 'SKTLK butuh akses');

        // Petugas loket menginput temuan: langsung tayang & otomatis dicocokkan
        $spkt = Http::as('spkt');
        [, $found] = $submit($spkt, ['title' => Db::PREFIX . 'Motor ditemukan', 'report_type' => 'FOUND', 'plate_number' => 'B-1234-XYZ',
                                     'color' => 'hitam', 'custody_location' => 'Ruang barang temuan SPKT', 'reporter_name' => 'Bripka Uji']);
        T::same('PUBLISHED', $found['status'], 'input petugas langsung terverifikasi');
        $match = Db::row("SELECT * FROM lost_found_matches WHERE lost_item_id = ? AND found_item_id = ?", [$lost['id'], $found['id']]);
        T::true($match !== null && (int)$match['score'] >= 70, 'saran cocok berdasarkan plat');

        // Publik: plat temuan disamarkan, kontak pelapor tidak tampil
        $page = $anon->get("/hilangtemu/item/{$found['id']}")->body;
        T::contains('******YZ', $page);
        T::notContains('B1234XYZ', $page);
        T::notContains('081234567890', $page);
        T::contains('ajukan klaim', $page);

        // Verifikasi laporan kehilangan
        $res = $officerPost($spkt, '/hilangtemu/verify', (int)$lost['id']);
        T::true(str_contains($res->location(), "/hilangtemu/detail/{$lost['id']}"), 'kembali ke detail');
        T::same('PUBLISHED', Db::value("SELECT status FROM lost_found_items WHERE id = ?", [$lost['id']]));
        T::contains("/hilangtemu/item/{$lost['id']}\"", $anon->get('/hilangtemu?q=' . $lost['code'])->body, 'tayang di galeri');
        T::contains('B1234XYZ', $anon->get("/hilangtemu/item/{$lost['id']}")->body, 'plat kehilangan utuh');

        // Konfirmasi kecocokan → keduanya MATCHED
        $officerPost($spkt, '/hilangtemu/match', (int)$lost['id'], ['match_id' => $match['id'], 'decision' => 'confirm']);
        T::same('CONFIRMED', Db::value("SELECT status FROM lost_found_matches WHERE id = ?", [$match['id']]));
        T::same(2, (int)Db::value("SELECT COUNT(*) FROM lost_found_items WHERE id IN (?, ?) AND status = 'MATCHED'", [$lost['id'], $found['id']]));

        // Pelapor: PIN salah ditolak, PIN benar membuka status & SKTLK
        $pelapor = new Http(TEST_BASE_URL);
        $csrf = $pelapor->get('/hilangtemu/status')->csrf();
        T::contains('tidak sesuai', $pelapor->post('/hilangtemu/status', ['_csrf_token' => $csrf, 'code' => $lost['code'], 'pin' => '000000' === $pin ? '111111' : '000000'])->body);
        $status = $pelapor->post('/hilangtemu/status', ['_csrf_token' => $csrf, 'code' => $lost['code'], 'pin' => $pin])->body;
        T::contains('menemukan kecocokan', $status);
        $sktlk = $pelapor->get("/hilangtemu/sktlk/{$lost['id']}");
        T::same(200, $sktlk->status);
        T::contains('SURAT KETERANGAN TANDA LAPOR KEHILANGAN', $sktlk->body);
        T::contains('B1234XYZ', $sktlk->body);

        // Serah terima menyelesaikan pasangan terkonfirmasi
        $officerPost($spkt, '/hilangtemu/resolve', (int)$found['id'], ['officer_note' => 'Diserahkan kepada pemilik di SPKT']);
        T::same(2, (int)Db::value("SELECT COUNT(*) FROM lost_found_items WHERE id IN (?, ?) AND status = 'RESOLVED'", [$lost['id'], $found['id']]));
        T::same(404, $anon->get("/hilangtemu/item/{$found['id']}")->status, 'selesai: tidak tayang lagi');
    },

    'klaim barang temuan: diajukan warga, diputus petugas, sisa klaim ditolak saat serah terima' => function () use ($submit, $officerPost) {
        Db::exec("DELETE FROM throttle_events");
        $spkt = Http::as('spkt');
        [, $item] = $submit($spkt, ['title' => Db::PREFIX . 'Dompet coklat ditemukan', 'report_type' => 'FOUND', 'item_category' => 'BARANG_BERHARGA',
                                    'brand' => 'Dompet kulit', 'plate_number' => 'B1', 'reporter_name' => 'Bripka Uji']);
        T::same(null, $item['plate_number'], 'kolom kendaraan dikosongkan untuk kategori barang');

        $claim = function (string $name) use ($item) {
            $c = new Http(TEST_BASE_URL);
            $csrf = $c->get("/hilangtemu/item/{$item['id']}")->csrf();
            return $c->post('/hilangtemu/klaim', [
                '_csrf_token' => $csrf, 'item_id' => $item['id'], 'claimant_name' => $name,
                'claimant_nik' => '3171000000000002', 'claimant_phone' => '081298765432',
                'proof_description' => 'Di dalam dompet ada KTP atas nama saya dan foto keluarga.',
            ]);
        };
        $claim('Pemilik Asli');
        $claim('Pengaju Lain');
        $claims = Db::pdo()->query("SELECT id, claimant_name FROM lost_found_claims WHERE item_id = {$item['id']} ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
        T::same(2, count($claims));

        // Kapolri hanya memantau: halaman terbuka, aksi ditolak
        $kapolri = Http::as('kapolri');
        T::same(200, $kapolri->get('/hilangtemu/admin')->status);
        $csrf = $kapolri->get("/hilangtemu/detail/{$item['id']}")->csrf();
        $kapolri->post('/hilangtemu/claim', ['_csrf_token' => $csrf, 'item_id' => $item['id'], 'claim_id' => array_key_first($claims), 'decision' => 'approve']);
        T::same('PENDING', Db::value("SELECT status FROM lost_found_claims WHERE id = ?", [array_key_first($claims)]));

        // Tolak tanpa alasan ditolak; setujui klaim pertama
        $officerPost($spkt, '/hilangtemu/claim', (int)$item['id'], ['claim_id' => array_key_last($claims), 'decision' => 'reject']);
        T::same('PENDING', Db::value("SELECT status FROM lost_found_claims WHERE id = ?", [array_key_last($claims)]));
        $officerPost($spkt, '/hilangtemu/claim', (int)$item['id'], ['claim_id' => array_key_first($claims), 'decision' => 'approve']);
        T::same('APPROVED', Db::value("SELECT status FROM lost_found_claims WHERE id = ?", [array_key_first($claims)]));
        T::same('MATCHED', Db::value("SELECT status FROM lost_found_items WHERE id = ?", [$item['id']]));

        $officerPost($spkt, '/hilangtemu/resolve', (int)$item['id'], ['officer_note' => 'Diserahkan kepada Pemilik Asli']);
        T::same('RESOLVED', Db::value("SELECT status FROM lost_found_items WHERE id = ?", [$item['id']]));
        T::same('REJECTED', Db::value("SELECT status FROM lost_found_claims WHERE id = ?", [array_key_last($claims)]), 'klaim sisa ditolak otomatis');
    },

    'akses petugas: warga tidak bisa membuka panel atau memverifikasi' => function () use ($submit) {
        Db::exec("DELETE FROM throttle_events");
        [, $item] = $submit(new Http(TEST_BASE_URL), ['title' => Db::PREFIX . 'Kucing oren hilang', 'item_category' => 'HEWAN', 'animal_species' => 'Kucing']);
        $warga = Http::as('warga');
        T::true(!str_contains($warga->get('/hilangtemu/admin')->location(), '/hilangtemu/admin') && $warga->get('/hilangtemu/admin')->status === 302, 'panel ditolak');
        $csrf = $warga->get('/hilangtemu/lapor')->csrf();
        $warga->post('/hilangtemu/verify', ['_csrf_token' => $csrf, 'item_id' => $item['id']]);
        T::same('PENDING', Db::value("SELECT status FROM lost_found_items WHERE id = ?", [$item['id']]));
        T::same(302, (new Http(TEST_BASE_URL))->get('/hilangtemu/admin')->status, 'tamu diarahkan ke login');
    },

    'laporan publik dibatasi per IP' => function () use ($submit) {
        Db::exec("DELETE FROM throttle_events");
        $client = new Http(TEST_BASE_URL);
        for ($i = 1; $i <= 6; $i++) {
            [$res] = $submit($client, ['title' => Db::PREFIX . "spam {$i}", 'plate_number' => "B{$i}"]);
        }
        T::same(5, (int)Db::value("SELECT COUNT(*) FROM lost_found_items WHERE title LIKE ?", [Db::PREFIX . 'spam %']));
        T::contains('Batas pengiriman', $client->flash());
    },
];
