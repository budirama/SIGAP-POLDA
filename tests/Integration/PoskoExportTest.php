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

    'ekspor excel: .xlsx (PhpSpreadsheet), isi = tampilan, batas wilayah & tercatat di audit' => function () {
        T::true(class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class), 'vendor/ terpasang (composer install)');
        $mark = (int)Db::value("SELECT COALESCE(MAX(id), 0) FROM audit_logs");
        $op = Http::as('poskoOpSby');

        $res = $op->get('/posko-laporan/export-excel?status=SUBMITTED');
        T::same(200, $res->status);
        T::contains('spreadsheetml.sheet', $res->header('content-type') ?? '', 'tipe konten xlsx');
        T::contains('.xlsx"', $res->header('content-disposition') ?? '', 'nama berkas .xlsx');
        T::same("PK\x03\x04", substr($res->body, 0, 4), 'berkas zip OOXML');

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($tmp, $res->body);
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getSheetByName('Laporan Posko');
        unlink($tmp);
        T::true($sheet !== null, 'lembar "Laporan Posko"');
        T::same('Nomor Laporan', $sheet->getCell('B4')->getValue(), 'baris judul kolom');

        $exported = [];
        $satkers = [];
        for ($row = 5; $row <= $sheet->getHighestDataRow(); $row++) {
            $exported[] = (string)$sheet->getCell([2, $row])->getValue();
            $satkers[] = (string)$sheet->getCell([3, $row])->getValue();
            T::same('Dikirim', $sheet->getCell([9, $row])->getValue(), 'filter status diterapkan');
        }
        T::true(count($exported) > 0, 'ada data terekspor');

        // Isi ekspor = isi tampilan cetak daftar untuk filter yang sama (BRD 12.10)
        $view = $op->get('/posko-laporan/print-list?status=SUBMITTED')->body;
        preg_match_all('~font-mono font-bold text-slate-900">([^<]+)</td>~', $view, $m);
        $shown = array_map(fn($s) => html_entity_decode(trim($s)), $m[1]);
        sort($shown);
        $sortedExport = $exported;
        sort($sortedExport);
        T::same($shown, $sortedExport, 'nomor laporan ekspor sama dengan tampilan');

        foreach ($satkers as $name) {
            T::true(!str_contains($name, 'Jakarta') && !str_contains($name, 'Metro'), "satker luar wilayah tidak ikut: {$name}");
        }

        $audit = Db::row("SELECT detail FROM audit_logs WHERE id > ? AND action = 'posko.export' ORDER BY id DESC LIMIT 1", [$mark]);
        T::true($audit !== null, 'audit log posko.export tercatat');
        T::contains('Ekspor Excel', $audit['detail']);
    },

    'ekspor pdf (Dompdf): daftar terfilter & dokumen tunggal, batas wilayah & audit' => function () {
        $mark = (int)Db::value("SELECT COALESCE(MAX(id), 0) FROM audit_logs");
        $op = Http::as('poskoOpSby');

        $list = $op->get('/posko-laporan/export-pdf?status=SUBMITTED');
        T::same(200, $list->status);
        T::same('application/pdf', $list->header('content-type'), 'tipe konten pdf');
        T::same('%PDF-', substr($list->body, 0, 5), 'berkas PDF valid');
        T::contains('Ekspor PDF', (string)Db::value("SELECT detail FROM audit_logs WHERE id > ? AND action = 'posko.export' ORDER BY id DESC LIMIT 1", [$mark]));

        $id = (int)Db::value("SELECT id FROM posko_reports WHERE satker_id = ? AND report_number IS NOT NULL ORDER BY id LIMIT 1", [EXPORT_GUBENG]);
        $number = (string)Db::value("SELECT report_number FROM posko_reports WHERE id = ?", [$id]);
        $one = Http::as('poskoOpGubeng')->get("/posko-laporan/pdf/{$id}");
        T::same(200, $one->status);
        T::same('%PDF-', substr($one->body, 0, 5), 'dokumen tunggal PDF');
        T::contains('_v', $one->header('content-disposition') ?? '', 'nama berkas memuat penanda versi');
        T::same(200, Http::as('poskoOpSby')->get("/posko-laporan/pdf/{$id}")->status, 'induk dapat mengunduh PDF bawahan');

        // Lintas wilayah ditolak & tidak menghasilkan PDF
        $jaksel = Http::as('poskoOpJaksel')->get("/posko-laporan/pdf/{$id}");
        T::same(302, $jaksel->status, 'lintas wilayah ditolak');
        T::true(!str_starts_with($jaksel->body, '%PDF'), 'tidak ada PDF untuk wilayah lain');

        $audit = Db::row("SELECT detail FROM audit_logs WHERE id > ? AND action = 'posko.export' AND detail LIKE ? ORDER BY id DESC LIMIT 1", [$mark, "%{$number}%"]);
        T::true($audit !== null, 'unduh PDF dokumen tercatat di audit');
    },
];
