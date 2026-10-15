<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

return [
    'dokumen SP2HP memakai nomor & tanggal SP2HP yang benar-benar diterbitkan' => function () {
        $row = Db::row(
            "SELECT r.ticket_number, t.sp2hp_number, t.created_at
             FROM reports r JOIN report_timeline t ON t.report_id = r.id AND t.is_sp2hp = 1
             WHERE r.assigned_investigator_id IS NOT NULL ORDER BY t.created_at DESC, t.id DESC LIMIT 1"
        );
        $html = Http::as('superadmin')->get('/report/sp2hpPrint?ticket=' . urlencode($row['ticket_number']))->body;
        T::contains('Nomor: ' . htmlspecialchars($row['sp2hp_number']), $html);
        $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        T::contains(date('j', strtotime($row['created_at'])) . ' ' . $bulan[(int)date('n', strtotime($row['created_at']))], $html, 'tanggal SP2HP berbahasa Indonesia');
        T::notContains('QR VALIDASI', $html);
        T::notContains('Ditandatangani secara Digital', $html);
        T::notContains('TERVERIFIKASI SISTEM ELEKTRONIK', $html);
    },

    'laporan tanpa SP2HP tidak dapat dicetak' => function () {
        [$pelapor, $ticket] = Db::createReport(['title' => Db::PREFIX . 'belum sp2hp']);
        $res = $pelapor->get('/report/sp2hpPrint?ticket=' . urlencode($ticket));
        T::same(302, $res->status);
        T::contains('setelah penyidik menerbitkan', $pelapor->flash());
    },

    'klaim fitur yang belum tersedia tidak ditampilkan' => function () {
        $guest = new Http(TEST_BASE_URL);
        $home = $guest->get('/')->body;
        $form = $guest->get('/report/create')->body;
        T::notContains('Notifikasi Otomatis WhatsApp', $home);
        T::notContains('QR Code', $home);
        T::notContains('akan dikirimkan ke nomor ini', $form);
        T::notContains('href="#"', $home, 'tautan sosial palsu di footer');
    },
];
