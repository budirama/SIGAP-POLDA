<?php

use Tests\Db;
use Tests\Http;
use Tests\T;
use App\Models\OneService\OpsTrack;
use App\Models\OneService\OpsCase;

$testTrack = new ArrayObject();

return [
    'halaman hub terpadu OneService terlindungi di balik login personil' => function () {
        $public = new Http(TEST_BASE_URL);
        $res = $public->get('/oneservice');
        // Tamu tanpa login diarahkan ke login
        T::same(302, $res->status, 'Tamu diarahkan ke halaman login');
        T::true(str_contains($res->header('Location') ?? '', '/auth/login'), 'Redirect URL memuat /auth/login');

        // Personil login (misal operator SPKT) dapat mengakses
        $spkt = Http::as('spkt');
        $resAuth = $spkt->get('/oneservice');
        T::same(200, $resAuth->status, 'Status response /oneservice untuk personil login');
        T::contains('Satu Nomor, Satu Jejak', $resAuth->body);
        T::contains('8 Modul Quick Win', $resAuth->body);
    },

    'registrasi kasus OneService menghasilkan tiket atomik dan PIN valid' => function () use ($testTrack) {
        $res = OpsTrack::createTrack([
            'source_channel'     => 'WEB_PORTAL',
            'current_status'     => 'INCOMING',
            'priority_level'     => 'P2_URGENT',
            'incident_category'  => 'PENIPUAN_ONLINE',
            'incident_summary'   => 'ZZTEST_ Laporan uji coba integrasi Satu Nomor',
            'citizen_name'       => 'Sdr. Budi Penguji',
            'citizen_nik'        => '3201012304950001',
            'citizen_phone'      => '081298765432',
            'current_satker_id'  => 1
        ]);

        T::true($res['success'], 'Kasus berhasil dibuat');
        T::true(!empty($res['tracking_number']), 'Tracking number terbit');
        T::true(!empty($res['security_pin']), 'PIN terbit');

        $testTrack['number'] = $res['tracking_number'];
        $testTrack['pin'] = $res['security_pin'];

        // Cek persistensi di basis data
        $dbStatus = Db::value("SELECT current_status FROM ops_global_tracks WHERE tracking_number = ?", [$res['tracking_number']]);
        T::same('INCOMING', $dbStatus, 'Status tersimpan di database');

        // Cek Event Bus log
        $eventCount = (int)Db::value("SELECT COUNT(*) FROM ops_event_streams WHERE tracking_number = ? AND event_name = 'CASE_REGISTERED'", [$res['tracking_number']]);
        T::true($eventCount >= 1, 'Event CASE_REGISTERED terbit ke ops_event_streams');
    },

    'pelacak publik memvalidasi kombinasi nomor lacak dan PIN 6-digit' => function () use ($testTrack) {
        $public = new Http(TEST_BASE_URL);

        // Akses tanpa input
        $resBlank = $public->get('/oneservice/track');
        T::same(200, $resBlank->status);

        // Akses dengan PIN salah
        $resWrong = $public->post('/oneservice/track', [
            'ticket' => $testTrack['number'],
            'pin'    => '999999'
        ]);
        T::contains('Nomor pelacakan atau PIN keamanan tidak sesuai', $resWrong->body);

        // Akses dengan PIN benar
        $resValid = $public->post('/oneservice/track', [
            'ticket' => $testTrack['number'],
            'pin'    => $testTrack['pin']
        ]);
        T::same(200, $resValid->status);
        T::contains($testTrack['number'], $resValid->body);
        T::contains('3201**********01', $resValid->body, 'NIK harus tertutup masking');
        T::contains('0812****5432', $resValid->body, 'No HP harus tertutup masking');
    },

    'penerbitan SP2HP A2 memperbarui status perkara dan menambah timeline' => function () use ($testTrack) {
        $sp2hp = OpsCase::issueSp2hp(
            $testTrack['number'],
            'A2',
            'Perkembangan Penyelidikan Tahap 1',
            'Pemeriksaan saksi pelapor dan saksi ahli telah dilakukan.',
            'Catatan rahasia penyidik.',
            '88030301'
        );

        T::true($sp2hp['success'], 'Penerbitan SP2HP berhasil');
        T::true(!empty($sp2hp['qr_verification_hash']), 'QR verification hash terbit');

        // Status perkara otomatis berganti menjadi INVESTIGATION
        $statusAfter = Db::value("SELECT current_status FROM ops_global_tracks WHERE tracking_number = ?", [$testTrack['number']]);
        T::same('INVESTIGATION', $statusAfter, 'Status perkara naik ke INVESTIGATION');

        // Timeline publik memuat SP2HP
        $public = new Http(TEST_BASE_URL);
        $res = $public->post('/oneservice/track', [
            'ticket' => $testTrack['number'],
            'pin'    => $testTrack['pin']
        ]);
        T::contains('SP2HP A2', $res->body);
        T::contains('Perkembangan Penyelidikan Tahap 1', $res->body);
    },

    'modul 2 triase 110 mencatat panggilan dan menghitung respon tiba di TKP' => function () {
        $spkt = Http::as('spkt');
        $csrf = $spkt->get('/oneservice/dispatch')->csrf();

        $resCall = $spkt->post('/oneservice/dispatch', [
            '_csrf_token'       => $csrf,
            'action'            => 'create_call',
            'caller_phone'      => '081122334455',
            'caller_name'       => 'ZZTEST_ Penelpon Darurat',
            'urgency_level'     => 'P1_EMERGENCY',
            'incident_category' => 'DARURAT_110',
            'call_summary'      => 'Kecelakaan lalin beruntun butuh ambulans dan patroli',
            'address'           => 'Jl. Tol KM 14',
            'satker_id'         => 1
        ]);
        T::same(200, $resCall->status);
        T::contains('Panggilan 110 berhasil diregistrasi', $resCall->body);

        // Ambil tiket yang baru dibuat
        $trackNum = Db::value("SELECT tracking_number FROM ops_dispatch_110 WHERE caller_phone = '081122334455' ORDER BY id DESC LIMIT 1");
        T::true(!empty($trackNum), 'Record ops_dispatch_110 terbentuk');

        // Disposisi unit
        $csrf2 = $spkt->get('/oneservice/dispatch')->csrf();
        $resDispatch = $spkt->post('/oneservice/dispatch', [
            '_csrf_token'     => $csrf2,
            'action'          => 'dispatch_unit',
            'tracking_number' => $trackNum,
            'unit_callsign'   => 'PATROLI-SAMAPTA-99',
            'vehicle_type'    => 'SAMAPTA_CAR'
        ]);
        T::same(200, $resDispatch->status);
        T::contains('berhasil didisposisi via CAD', $resDispatch->body);

        // Konfirmasi tiba di TKP
        $csrf3 = $spkt->get('/oneservice/dispatch')->csrf();
        $resArrive = $spkt->post('/oneservice/dispatch', [
            '_csrf_token'     => $csrf3,
            'action'          => 'mark_arrived',
            'tracking_number' => $trackNum,
            'scene_notes'     => 'Unit tiba dan mengamankan arus lalu lintas.'
        ]);
        T::same(200, $resArrive->status);
        T::contains('Konfirmasi tiba di TKP tercatat', $resArrive->body);

        $arriveStatus = Db::value("SELECT status FROM ops_dispatch_110 WHERE tracking_number = ?", [$trackNum]);
        T::same('ON_SCENE', $arriveStatus, 'Status dispatch ON_SCENE');
    },

    'modul 7 Bhabin menolak laporan seremonial tanpa mufakat damai konkret' => function () {
        $spkt = Http::as('spkt');
        $csrf = $spkt->get('/oneservice/bhabin')->csrf();

        // Laporan seremonial pendek (hanya foto bersama)
        $resCeremonial = $spkt->post('/oneservice/bhabin', [
            '_csrf_token'       => $csrf,
            'village_name'      => 'Desa Uji',
            'conflict_category' => 'Sengketa Batas Warga',
            'parties_involved'  => 'Warga A dan Warga B',
            'mediation_outcome' => 'foto bersama warga',
            'citizen_phone'     => '08123456789'
        ]);
        T::contains('Sistem menolak laporan seremonial', $resCeremonial->body);

        // Laporan penyelesaian damai konkret (Restorative Justice)
        $csrf2 = $spkt->get('/oneservice/bhabin')->csrf();
        $resValid = $spkt->post('/oneservice/bhabin', [
            '_csrf_token'       => $csrf2,
            'village_name'      => 'Desa Presisi Uji',
            'conflict_category' => 'Sengketa Batas Warga',
            'parties_involved'  => 'Bpk. Hendra & Bpk. Joko',
            'mediation_outcome' => 'Kedua belah pihak sepakat memasang patok pembatas tanah disaksikan RT dan tokoh adat setempat.',
            'citizen_phone'     => '08123456789'
        ]);
        T::contains('Restorative Justice) berhasil dicatat', $resValid->body);
    },

    'modul 8 dashboard akuntabilitas 100 hari menyajikan 6 pertanyaan strategis' => function () {
        $superadmin = Http::as('superadmin');
        $res = $superadmin->get('/oneservice/dashboard');
        T::same(200, $res->status);
        T::contains('Dashboard Akuntabilitas 100 Hari Presisi', $res->body);
        T::contains('Berapa warga yang dilayani?', $res->body);
        T::contains('Berapa cepat respons Polri?', $res->body);
        T::contains('Berapa masalah yang selesai?', $res->body);
        T::contains('Berapa keluhan yang diperbaiki?', $res->body);
        T::contains('Apa yang belum berhasil?', $res->body);
        T::contains('Apa yang akan diperbaiki?', $res->body);
    },

    'audit UU PDP mencatat pembukaan data pribadi pelapor oleh personil berwenang' => function () use ($testTrack) {
        $penyidik = Http::as('penyidik');
        $csrf = $penyidik->get('/oneservice/cases')->csrf();

        $beforeCount = (int)Db::value("SELECT COUNT(*) FROM ops_audit_pdp_logs WHERE tracking_number = ?", [$testTrack['number']]);

        $res = $penyidik->post('/oneservice/apiRevealPii', [
            '_csrf_token'     => $csrf,
            'tracking_number' => $testTrack['number'],
            'justification'   => 'ZZTEST_ Verifikasi data saksi pelapor'
        ]);

        $json = json_decode($res->body, true);
        T::true(is_array($json) && !empty($json['success']), 'API reveal PII berhasil');
        T::same('3201012304950001', $json['citizen_nik'], 'NIK asli berhasil didekripsi');

        $afterCount = (int)Db::value("SELECT COUNT(*) FROM ops_audit_pdp_logs WHERE tracking_number = ?", [$testTrack['number']]);
        T::same($beforeCount + 1, $afterCount, 'Akses tercatat di tabel ops_audit_pdp_logs');
    },

    'modul 3 SPKT ramah korban menerima laporan jalur PPA dan menerbitkan tiket' => function () {
        $spkt = Http::as('spkt');
        $csrf = $spkt->get('/oneservice/spkt')->csrf();

        $res = $spkt->post('/oneservice/spkt', [
            '_csrf_token'         => $csrf,
            'is_vulnerable_group' => '1',
            'is_whistleblower'    => '0',
            'citizen_name'        => 'Ibu Siti Aminah (Korban PPA)',
            'citizen_nik'         => '3172024508900002',
            'citizen_phone'       => '081299887766',
            'incident_category'   => 'KEKERASAN_PPA',
            'priority_level'      => 'P1_EMERGENCY',
            'satker_id'           => 1,
            'incident_address'    => 'Kecamatan Cilincing RT 03/05',
            'incident_summary'    => 'ZZTEST_ Laporan tindak kekerasan perempuan dan anak butuh perlindungan darurat'
        ]);

        T::same(200, $res->status);
        T::contains('Laporan Berhasil Diregistrasi di SPKT!', $res->body);
        T::contains('POL-', $res->body);

        $isPpa = (int)Db::value("SELECT is_vulnerable_group FROM ops_global_tracks WHERE citizen_name = 'Ibu Siti Aminah (Korban PPA)' ORDER BY id DESC LIMIT 1");
        T::same(1, $isPpa, 'Flag is_vulnerable_group tersimpan');
    },

    'modul 6 aduan siber meneruskan permintaan blokir rekening ke perbankan/OJK' => function () {
        $spkt = Http::as('spkt');
        $csrf = $spkt->get('/oneservice/cyber')->csrf();

        $res = $spkt->post('/oneservice/cyber', [
            '_csrf_token'         => $csrf,
            'bank_name'           => 'BANK_BCA',
            'account_number'      => '8899001122',
            'account_holder_name' => 'Sindikat Fiktif A',
            'fraud_scheme'        => 'ONLINE_SHOP',
            'loss_amount'         => '4500000',
            'citizen_name'        => 'ZZTEST_ Korban Belanja',
            'citizen_nik'         => '3201019909990001',
            'citizen_phone'       => '081211223344'
        ]);

        T::same(200, $res->status);
        T::contains('Aduan penipuan daring berhasil diproses', $res->body);

        $freezeStatus = Db::value("SELECT freeze_status FROM ops_cyber_fraud WHERE account_number = '8899001122' ORDER BY id DESC LIMIT 1");
        T::true(in_array($freezeStatus, ['FROZEN_CONFIRMED', 'REQUESTED_TO_BANK'], true), 'Status pembekuan rekening');
    },

    'modul 5 Dumas terpadu mendaftarkan pengaduan masyarakat berstatus terlindungi' => function () {
        $itwasum = Http::as('itwasum');
        $csrf = $itwasum->get('/oneservice/dumas')->csrf();

        $res = $itwasum->post('/oneservice/dumas', [
            '_csrf_token'            => $csrf,
            'is_whistleblower'       => '1',
            'reported_satker_id'     => 1,
            'reported_service_loket' => 'Loket Pelayanan SIM Keliling',
            'allegation_type'        => 'PUNGLI',
            'summary'                => 'ZZTEST_ Oknum meminta biaya tambahan di luar PNBP resmi',
            'reporter_name'          => 'Whistleblower Peduli',
            'reporter_phone'         => '081399881122'
        ]);

        T::same(200, $res->status);
        T::contains('Aduan Dumas berhasil diterima secara terpadu', $res->body);

        $dumasRow = Db::row("SELECT * FROM ops_dumas_cases WHERE reported_service_loket = 'Loket Pelayanan SIM Keliling' ORDER BY id DESC LIMIT 1");
        T::true(!empty($dumasRow), 'Record ops_dumas_cases terbentuk');
        T::same('PUNGLI', $dumasRow['allegation_type']);
        T::same(1, (int)$dumasRow['is_whistleblower_protected']);
    },

    'dual-write bridge: laporan via modul legacy otomatis menerbitkan Satu Nomor dan tersinkron ke milestones' => function () {
        $reportModel = new \App\Models\Report();

        // 1. Buat laporan via Report model (jalur publik /report/create)
        $ticket = $reportModel->createReport([
            'source_channel'    => 'WEB_PORTAL',
            'is_anonymous'      => 0,
            'reporter_name_raw' => 'ZZTEST_ Warga Bridge',
            'reporter_nik_raw'  => '3201015505990003',
            'reporter_phone'    => '081234567890',
            'category_id'       => 1,
            'title'             => 'ZZTEST_ Laporan Uji Coba Dual Write Bridge',
            'description'       => 'Detail laporan perkara uji sinkronisasi Satu Nomor',
            'incident_date'     => date('Y-m-d'),
            'incident_location' => 'Jakarta Pusat',
            'urgency_level'     => 'NORMAL'
        ]);

        T::true(!empty($ticket), 'Tiket legacy terbit: ' . $ticket);

        // 2. Cek apakah ops_global_tracks otomatis terisi via bridge
        $bridgeTrack = Db::row("SELECT * FROM ops_global_tracks WHERE external_reference_no = ? LIMIT 1", [$ticket]);
        T::true(!empty($bridgeTrack), 'Record ops_global_tracks otomatis terbentuk via bridge');
        T::true(str_starts_with($bridgeTrack['tracking_number'], 'POL-'), 'Nomor Satu Nomor berformat POL-');

        // 3. Uji terbitkan SP2HP dari penyidik di modul eksisting
        $reportRow = $reportModel->findByTicket($ticket);
        $resSp2hp = $reportModel->issueSp2hp(
            (int)$reportRow['id'],
            1,
            'SP2HP/TEST/001',
            'Penyelidikan telah ditingkatkan ke tahap gelar perkara.',
            'Catatan internal gelar perkara'
        );
        T::true($resSp2hp, 'Penerbitan SP2HP legacy berhasil');

        // 4. Verifikasi milestone otomatis tercatat di ops_case_milestones
        $milestone = Db::row("SELECT * FROM ops_case_milestones WHERE tracking_number = ? ORDER BY id DESC LIMIT 1", [$bridgeTrack['tracking_number']]);
        T::true(!empty($milestone), 'Milestone otomatis tersinkron ke OneService');
        T::same('A2', $milestone['stage_code']);
        T::same('Penyelidikan telah ditingkatkan ke tahap gelar perkara.', $milestone['description_public']);
        T::true(!empty($milestone['qr_verification_hash']), 'Hash verifikasi QR terbit');
    },

    'unified tracker: pelacak report/track mengalihkan tiket POL-... secara otomatis' => function () {
        $public = new Http(TEST_BASE_URL);
        $res = $public->get('/report/track?ticket=POL-202610-WEB-TEST1');
        T::same(302, $res->status, 'Redirect 302 jika memasukkan POL- di /report/track');
        T::true(str_contains($res->header('Location') ?? '', '/oneservice/track'), 'Redirect mengarah ke /oneservice/track');
    },

    'SLA watchdog menandai perkara macet yang melewati batas tenggat' => function () {
        $penyidik = Http::as('penyidik');
        $csrf = $penyidik->get('/oneservice/cases')->csrf();

        $res = $penyidik->post('/oneservice/cases', [
            '_csrf_token' => $csrf,
            'action'      => 'run_watchdog'
        ]);

        T::same(200, $res->status);
        T::contains('SLA Watchdog berhasil dijalankan', $res->body);

        // Bersihkan data uji yang berawalan ZZTEST_
        $tracks = Db::pdo()->query("SELECT tracking_number FROM ops_global_tracks WHERE incident_summary LIKE '%ZZTEST_%'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tracks as $tn) {
            Db::exec("DELETE FROM ops_audit_pdp_logs WHERE tracking_number = ?", [$tn]);
            Db::exec("DELETE FROM ops_case_milestones WHERE tracking_number = ?", [$tn]);
            Db::exec("DELETE FROM ops_dispatch_110 WHERE tracking_number = ?", [$tn]);
            Db::exec("DELETE FROM ops_cyber_fraud WHERE tracking_number = ?", [$tn]);
            Db::exec("DELETE FROM ops_bhabin_resolutions WHERE tracking_number = ?", [$tn]);
            Db::exec("DELETE FROM ops_dumas_cases WHERE tracking_number = ?", [$tn]);
            Db::exec("DELETE FROM ops_event_streams WHERE tracking_number = ?", [$tn]);
            Db::exec("DELETE FROM ops_global_tracks WHERE tracking_number = ?", [$tn]);
        }

        // Bersihkan reports data uji
        $reportIds = Db::pdo()->query("SELECT id FROM reports WHERE title LIKE '%ZZTEST_%'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($reportIds as $rid) {
            Db::exec("DELETE FROM report_timeline WHERE report_id = ?", [$rid]);
            Db::exec("DELETE FROM reports WHERE id = ?", [$rid]);
        }
    }
];

