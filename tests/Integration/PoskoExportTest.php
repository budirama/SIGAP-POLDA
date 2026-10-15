<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

// Satker demo
const EXPORT_GUBENG = 1005; // Polsek Gubeng (Surabaya)
const EXPORT_SBY    = 113;  // Polrestabes Surabaya
const EXPORT_JATIM  = 10;   // Polda Jatim
const EXPORT_JAKSEL = 102;  // Polres Jaksel

return [
    'cetak dokumen resmi: kop, nomor, versi, indikator & tanda tangan' => function () {
        // Ambil laporan terkirim milik Gubeng (misal ID 1)
        $id = (int)Db::value("SELECT id FROM posko_reports WHERE satker_id = ? AND report_number IS NOT NULL ORDER BY id LIMIT 1", [EXPORT_GUBENG]);
        T::true($id > 0, 'laporan Gubeng tersedia');

        $report = Db::row("SELECT * FROM posko_reports WHERE id = ?", [$id]);
        $gubeng = Http::as('poskoOpGubeng');

        // Buka rute print
        $res = $gubeng->get("/posko-laporan/print/{$id}");
        T::same(200, $res->status);
        T::contains('KEPOLISIAN NEGARA REPUBLIK INDONESIA', $res->body, 'kop resmi');
        T::contains('Polsek Gubeng', $res->body, 'nama satker');
        T::contains($report['report_number'], $res->body, 'nomor laporan');
        T::contains('v' . (int)$report['version'], $res->body, 'penanda versi');
        T::contains('window.print()', $res->body, 'tombol cetak');
        T::contains('YANG MEMBUAT LAPORAN', $res->body, 'lembar tanda tangan');

        // Alias print-report juga bekerja
        $resAlias = $gubeng->get("/posko-laporan/print-report/{$id}");
        T::same(200, $resAlias->status);
        T::contains($report['report_number'], $resAlias->body);
    },

    'cetak dokumen resmi: hirarki berjenjang & isolasi lintas wilayah' => function () {
        $id = (int)Db::value("SELECT id FROM posko_reports WHERE satker_id = ? AND report_number IS NOT NULL ORDER BY id LIMIT 1", [EXPORT_GUBENG]);

        // Satker induk (Polrestabes Surabaya & Polda Jatim) dapat mencetak laporan bawahannya
        T::same(200, Http::as('poskoOpSby')->get("/posko-laporan/print/{$id}")->status, 'induk Polres dapat mencetak');
        T::same(200, Http::as('poskoOpJatim')->get("/posko-laporan/print/{$id}")->status, 'induk Polda dapat mencetak');

        // Satker lain di luar hierarki ditolak
        $jaksel = Http::as('poskoOpJaksel')->get("/posko-laporan/print/{$id}");
        T::same(302, $jaksel->status, 'lintas Polda/Polres ditolak');
    },

    'cetak dokumen resmi: draft hanya untuk pembuat' => function () {
        // Buat draft laporan uji
        $op = Http::as('poskoOpGubeng');
        $csrf = $op->get('/posko-laporan/create?type=1')->csrf();
        $op->post('/posko-laporan/save', [
            '_csrf_token' => $csrf, 'id' => 0, 'report_type_id' => 1, 'period_date' => date('Y-m-d', strtotime('-48 days')),
            'region' => Db::PREFIX . 'CetakDraft', 'priority' => 'NORMAL', 'situation_summary' => 'Draft cetak uji', 'next' => 'draft',
        ]);
        $draftId = (int)Db::value("SELECT MAX(id) FROM posko_reports WHERE region = ?", [Db::PREFIX . 'CetakDraft']);
        T::true($draftId > 0, 'draft dibuat');

        // Pembuat dapat mencetak pratinjau draft
        $res = $op->get("/posko-laporan/print/{$draftId}");
        T::same(200, $res->status);
        T::contains('DRAFT', $res->body);

        // Induk belum dapat melihat atau mencetak draft
        T::same(302, Http::as('poskoOpSby')->get("/posko-laporan/print/{$draftId}")->status, 'induk ditolak membuka draft');
    },

    'cetak daftar laporan: memuat tabel terfilter & menghormati batas wilayah' => function () {
        $op = Http::as('poskoOpSby');
        $res = $op->get('/posko-laporan/print-list?status=SUBMITTED');
        T::same(200, $res->status);
        T::contains('DAFTAR REKAPITULASI LAPORAN POSKO', $res->body);
        T::contains('Status: Dikirim', $res->body, 'filter status');
        T::contains('window.print()', $res->body);

        // Pastikan tidak ada data di luar wilayah Surabaya
        T::notContains('Polres Metro Jakarta Selatan', $res->body, 'tidak memuat data satker lain');
    },

    'ekspor excel: valid SpreadsheetML XML, terfilter, batas wilayah & tercatat di audit' => function () {
        $admin = (int)Db::value("SELECT COALESCE(MAX(id), 0) FROM audit_logs");
        $op = Http::as('poskoOpSby');

        // Panggil ekspor excel dengan filter status
        $res = $op->get('/posko-laporan/export-excel?status=SUBMITTED');
        T::same(200, $res->status);
        T::contains('application/vnd.ms-excel', $res->header('content-type') ?? '', 'tipe konten excel');
        T::contains('attachment; filename="laporan_posko_', $res->header('content-disposition') ?? '', 'disposisi unduhan');

        // Validasi struktur SpreadsheetML 2003 XML
        T::contains('<?xml version="1.0" encoding="UTF-8"?>', $res->body);
        T::contains('<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"', $res->body);
        T::contains('<Worksheet ss:Name="Laporan Posko">', $res->body);
        T::contains('Nomor Laporan', $res->body);
        T::contains('Satker', $res->body);

        // Isolasi wilayah: operator Surabaya tidak boleh mendapat satker Metro Jaya/Jaksel
        T::notContains('Polres Metro Jakarta Selatan', $res->body, 'satker luar wilayah tidak ikut tereksport');

        // Verifikasi jejak audit posko.export
        $audit = Db::row("SELECT action, detail FROM audit_logs WHERE id > ? AND action = 'posko.export' ORDER BY id DESC LIMIT 1", [$admin]);
        T::true($audit !== null, 'audit log posko.export tercatat');
        T::contains('Ekspor Excel', $audit['detail']);
    },
];
