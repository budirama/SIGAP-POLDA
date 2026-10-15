<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$tmp = sys_get_temp_dir();
$fakePdf = $tmp . '/sigap_test_palsu.pdf';
file_put_contents($fakePdf, '<?php echo "bukan pdf"; ?>');

return [
    'daftar laporan dipaginasi dengan total & filter yang benar' => function () {
        for ($i = 1; $i <= 3; $i++) {
            Db::createReport(['title' => Db::PREFIX . "paginasi {$i}"]);
        }
        $filters = ['search' => Db::PREFIX . 'paginasi'];
        $model = new \App\Models\Report();

        $p1 = $model->paginateFilteredReports($filters, 1, 2);
        T::same(3, $p1['total'], 'total');
        T::same(2, $p1['pages'], 'jumlah halaman');
        T::same(2, count($p1['items']), 'isi halaman 1');

        $p2 = $model->paginateFilteredReports($filters, 2, 2);
        T::same(1, count($p2['items']), 'isi halaman 2');
        T::same([], array_intersect(array_column($p1['items'], 'id'), array_column($p2['items'], 'id')), 'tidak ada duplikasi antarhalaman');

        T::same(2, $model->paginateFilteredReports($filters, 99, 2)['page'], 'halaman di luar batas dijepit ke halaman terakhir');

        $page = Http::as('spkt')->get('/spkt?search=' . urlencode(Db::PREFIX . 'paginasi'))->body;
        T::contains('>3</div>', $page, 'total tiket di SPKT dari hitungan, bukan isi halaman');
    },

    'menu backoffice dapat dibuka di layar kecil' => function () {
        $page = Http::as('spkt')->get('/spkt')->body;
        T::contains('id="sidebarToggle"', $page);
        T::contains('aria-controls="sidebar"', $page);
        T::notContains('Menu navigasi dapat diakses pada layar desktop', $page, 'alert lama dihapus');
    },

    'nomor Sprintgas & SP2HP default bersufiks kode Satker' => function () {
        // Laporan seeder #1 ditangani Ditreskrimum (DIT-KRIMUM) oleh penyidik Rian
        $page = Http::as('penyidik')->get('/investigator/detail/1')->body;
        T::true((bool)preg_match('#value="SP2HP/\d{3}/\d{2}/\d{4}/DIT-KRIMUM"#', $page), 'SP2HP bersufiks kode Satker');
        T::notContains('/Ditreskrimum"', $page);

        T::same('SP.Gas', explode('/', \App\Config\Constants::documentNumber('SP.Gas', 'POLRES-JAKSEL'))[0]);
        T::true(str_ends_with(\App\Config\Constants::documentNumber('SP.Gas', 'POLRES-JAKSEL'), '/POLRES-JAKSEL'), 'sufiks Satker');
        T::true(str_ends_with(\App\Config\Constants::documentNumber('SP2HP', null), '/POLDA'), 'tanpa Satker jatuh ke POLDA');
    },

    'pesan dengan lampiran ditolak tidak tersimpan & teks dikembalikan' => function () use ($fakePdf) {
        [$pelapor, $ticket] = Db::createReport(['title' => Db::PREFIX . 'lampiran ditolak']);
        $before = (int)Db::value("SELECT COUNT(*) FROM case_messages m JOIN reports r ON r.id = m.report_id WHERE r.ticket_number = ?", [$ticket]);

        $pelapor->post('/report/sendMessage', [
            '_csrf_token'        => $pelapor->get("/report/track?ticket={$ticket}")->csrf(),
            'ticket_number'      => $ticket,
            'message_text'       => 'draf pesan uji lampiran',
            'clarification_file' => new CURLFile($fakePdf, 'application/pdf', 'bukti.pdf'),
        ]);

        T::same($before, (int)Db::value("SELECT COUNT(*) FROM case_messages m JOIN reports r ON r.id = m.report_id WHERE r.ticket_number = ?", [$ticket]), 'pesan tidak disimpan');
        $page = $pelapor->get("/report/track?ticket={$ticket}")->body;
        T::contains('Pesan belum terkirim', $page, 'pesan error jelas');
        T::notContains('berhasil dikirimkan', $page, 'tidak tampil sukses');
        T::contains('>draf pesan uji lampiran</textarea>', $page, 'teks pesan dikembalikan ke formulir');
        T::notContains('draf pesan uji lampiran', $pelapor->get("/report/track?ticket={$ticket}")->body, 'draf hanya sekali pakai');
    },

    'rating SKM memakai radio yang dapat dipilih keyboard' => function () {
        [$pelapor, $ticket] = Db::createReport(['title' => Db::PREFIX . 'rating']);
        Db::exec("UPDATE reports SET status = 'CLOSED', closed_at = NOW() WHERE ticket_number = ?", [$ticket]);
        $page = $pelapor->get("/report/track?ticket={$ticket}")->body;
        T::same(5, preg_match_all('#<input type="radio" name="rating_stars" value="[1-5]"#', $page), 'lima radio rating');
        T::notContains('onclick="setRating(', $page, 'tidak bergantung klik ikon');

        $pelapor->post('/report/submitFeedback', [
            '_csrf_token'    => $pelapor->get("/report/track?ticket={$ticket}")->csrf(),
            'ticket_number'  => $ticket,
            'rating_stars'   => '3',
            'speed_score'    => '4',
            'fairness_score' => '4',
            'comments'       => 'uji rating',
        ]);
        T::same(3, (int)Db::value("SELECT f.rating_stars FROM report_feedbacks f JOIN reports r ON r.id = f.report_id WHERE r.ticket_number = ?", [$ticket]));
    },

    'data seeder konsisten' => function () {
        T::same(null, Db::value("SELECT reporter_id FROM reports WHERE id = 1"), 'laporan medsos tidak ditautkan ke akun warga');
        T::same(null, Db::value("SELECT reporter_id FROM reports WHERE id = 2"), 'whistleblower tidak ditautkan ke akun warga');
        T::same(0, (int)Db::value(
            "SELECT COUNT(*) FROM reports r JOIN users u ON u.id = r.assigned_investigator_id
             WHERE r.id <= 3 AND u.satker_id <> r.current_satker_id"
        ), 'penyidik berasal dari Satker penanganan');
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM reports WHERE id <= 3 AND status = 'CLOSED' AND closed_at IS NULL"), 'kasus selesai punya closed_at');
    },
];
