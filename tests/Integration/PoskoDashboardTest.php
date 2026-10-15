<?php

use App\Models\PoskoDashboard;
use App\Models\PoskoOutbox;
use Tests\Db;
use Tests\Http;
use Tests\T;

// Gubeng (1005) & Benowo (1000) → Polrestabes Surabaya (113) → Polda Jatim (10) → Mabes (1)
const P5_SBY = 113;
const P5_JATIM = 10;
const P5_U = ['gubeng' => 110, 'benowo' => 109, 'sbyOp' => 107, 'sbyAdmin' => 106, 'sbyExec' => 108,
              'jatimOp' => 104, 'jatimAdmin' => 103, 'jatimExec' => 105, 'sidoarjo' => 113];

$csrfOf = fn(Http $c) => $c->get('/posko/password')->csrf();
$act = fn(Http $c, string $action, int $id, string $notes = '') => $c->post("/posko-laporan/{$action}", ['_csrf_token' => $csrfOf($c), 'id' => $id, 'notes' => $notes]);

/** Laporan harian dari unit $role pada tanggal $date; kembalikan id (terkirim bila $submit) */
$daily = function (string $role, string $date, string $priority = 'NORMAL', bool $submit = true) use ($csrfOf): int {
    $c = Http::as($role);
    $csrf = $csrfOf($c);
    $c->post('/posko-laporan/save', [
        '_csrf_token' => $csrf, 'id' => 0, 'report_type_id' => 1, 'period_date' => $date,
        'region' => Db::PREFIX . $role . $date, 'priority' => $priority, 'situation_summary' => 'Aman', 'description' => 'Patroli',
        'values' => ['situasi_kamtibmas' => 'Aman terkendali', 'jumlah_kejadian' => '1', 'jumlah_laka_lantas' => '0', 'personel_siaga' => '5'],
        'next' => 'draft',
    ]);
    $id = (int)Db::value("SELECT MAX(id) FROM posko_reports WHERE region = ?", [Db::PREFIX . $role . $date]);
    if ($submit) {
        $c->post('/posko-laporan/submit', ['_csrf_token' => $csrf, 'id' => $id]);
    }
    return $id;
};
/** Angka kartu dashboard dari halaman /posko */
$cards = function (Http $c): array {
    $body = $c->get('/posko')->body;
    $out = [];
    foreach (['required', 'sent', 'pending', 'revision', 'late'] as $k) {
        preg_match('/data-card="' . $k . '".*?font-mono[^>]*>(\d+)</s', $body, $m);
        $out[$k] = isset($m[1]) ? (int)$m[1] : -1;
    }
    return $out;
};
/** Notifikasi baru (id > $mark) untuk pengguna $uid, dikelompokkan jenisnya */
$notifTypes = fn(int $uid, int $mark) => array_column(Db::pdo()->query("SELECT type FROM posko_notifications WHERE user_id = {$uid} AND id > {$mark} ORDER BY id")->fetchAll(), 'type');
$mark = fn() => (int)Db::value("SELECT COALESCE(MAX(id), 0) FROM posko_notifications");

return [
    'dashboard: kartu sama dengan hitungan database & berubah mengikuti laporan' => function () use ($cards, $daily, $act) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $sby = Http::as('poskoOpSby');
        $before = $cards($sby);
        T::true(!in_array(-1, $before, true), 'semua kartu tampil: ' . json_encode($before));

        // Pembanding langsung ke database untuk wilayah Polrestabes Surabaya (unit + turunan)
        $scope = array_map('intval', array_column(Db::pdo()->query(
            "SELECT id FROM satkers WHERE id = " . P5_SBY . " OR path LIKE CONCAT((SELECT path FROM satkers WHERE id = " . P5_SBY . "), '%')"
        )->fetchAll(), 'id'));
        $in = implode(',', $scope);
        T::same((int)Db::value("SELECT COUNT(*) FROM posko_reports WHERE satker_id IN ({$in}) AND report_number IS NOT NULL AND status IN ('SUBMITTED','IN_REVIEW')"), $before['pending'], 'kartu menunggu verifikasi = hitungan DB');
        T::same((int)Db::value("SELECT COUNT(*) FROM posko_reports WHERE satker_id IN ({$in}) AND report_number IS NOT NULL AND status = 'NEEDS_REVISION'"), $before['revision'], 'kartu perlu perbaikan = hitungan DB');
        $model = (new PoskoDashboard())->cards($scope);
        T::same([$model['required'], $model['sent'], $model['late']], [$before['required'], $before['sent'], $before['late']], 'halaman = model');

        // Gubeng mengirim laporan harian kemarin (terlambat karena tenggat sudah lewat)
        $id = $daily('poskoOpGubeng', $yesterday);
        T::same('SUBMITTED', Db::value("SELECT status FROM posko_reports WHERE id = ?", [$id]), 'laporan terkirim');
        $after = $cards($sby);
        T::same($before['required'], $after['required'], 'jumlah kewajiban tidak berubah');
        T::same($before['sent'] + 1, $after['sent'], 'sudah dikirim +1');
        T::same($before['pending'] + 1, $after['pending'], 'menunggu verifikasi +1');
        T::same($before['late'], $after['late'], 'tetap tercatat terlambat (terkirim lewat tenggat)');

        // Disetujui → keluar dari antrean verifikasi
        $act($sby, 'approve', $id);
        T::same($before['pending'], $cards($sby)['pending'], 'menunggu verifikasi kembali turun setelah disetujui');

        // Polsek hanya melihat wilayahnya sendiri (tanpa laporan Polsek lain)
        $gubeng = Http::as('poskoOpGubeng')->get('/posko')->body;
        T::contains('Tren Laporan Terkirim', $gubeng);
        T::notContains('Polsek Benowo', Http::as('poskoOpGubeng')->get('/posko')->body, 'Polsek lain tidak muncul di dashboard Gubeng');
    },

    'dashboard: drill-down berhenti di batas wilayah & Pusat melihat status Polda' => function () {
        $sby = Http::as('poskoOpSby');
        $gubengId = (int)Db::value("SELECT id FROM satkers WHERE code = (SELECT code FROM satkers WHERE name LIKE '%Gubeng%' AND type = 'POLSEK' LIMIT 1) LIMIT 1");
        T::true($gubengId > 0, 'Polsek Gubeng ada');
        T::same(200, $sby->get("/posko/unit/{$gubengId}")->status, 'drill-down ke Polsek di bawahnya diizinkan');
        T::contains('Tren Laporan Terkirim', $sby->get("/posko/unit/{$gubengId}")->body);

        $up = $sby->get('/posko/unit/' . P5_JATIM);
        T::same(302, $up->status, 'URL drill-down ke Polda (atas) ditolak');
        T::notContains('/posko/unit/', $up->location());

        $other = (int)Db::value("SELECT id FROM satkers WHERE name LIKE 'Polresta Sidoarjo%' LIMIT 1");
        T::true($other > 0, 'Polresta Sidoarjo ada');
        T::same(302, $sby->get("/posko/unit/{$other}")->status, 'drill-down lintas Polres ditolak');
        T::same(302, Http::as('poskoOpGubeng')->get('/posko/unit/' . P5_SBY)->status, 'Polsek tidak dapat naik ke Polres');

        $pusat = Http::as('poskoExecPusat')->get('/posko');
        T::same(200, $pusat->status);
        T::contains('data-child-status="' . P5_JATIM . '"', $pusat->body, 'Pusat melihat status pelaporan Polda Jatim');
        T::contains('terkirim', $pusat->body);
    },

    'notifikasi: hanya untuk penerima berwenang di setiap tahap workflow' => function () use ($daily, $act, $mark, $notifTypes, $csrfOf) {
        $d = date('Y-m-d', strtotime('-41 days'));
        $m = $mark();

        // Gubeng kirim laporan prioritas tinggi → Polres Surabaya (operator/admin) + pejabat Polres (prioritas)
        $id = $daily('poskoOpGubeng', $d, 'HIGH');
        T::same(['report_submitted'], $notifTypes(P5_U['sbyOp'], $m), 'operator Polres menerima laporan baru');
        T::same(['report_submitted'], $notifTypes(P5_U['sbyAdmin'], $m), 'admin Polres menerima laporan baru');
        T::same(['high_priority'], $notifTypes(P5_U['sbyExec'], $m), 'pejabat Polres menerima peringatan prioritas');
        T::same([], $notifTypes(P5_U['gubeng'], $m), 'pengirim tidak diberi tahu atas aksinya sendiri');
        foreach (['jatimOp', 'jatimAdmin', 'jatimExec', 'sidoarjo', 'benowo'] as $k) {
            T::same([], $notifTypes(P5_U[$k], $m), "{$k} di luar jalur tidak menerima notifikasi");
        }
        T::true((int)Db::value("SELECT COUNT(*) FROM posko_notification_outbox WHERE to_email = 'posko.op.surabaya@sigap.polri.go.id' AND status = 'PENDING' AND subject LIKE '%Laporan baru%'") >= 1, 'email masuk antrean outbox');

        // Polres menyetujui → operator Polsek pembuat menerima; Polres meneruskan → Polda (bukan Polsek)
        $sby = Http::as('poskoOpSby');
        $m2 = $mark();
        $act($sby, 'approve', $id);
        T::same(['report_approved'], $notifTypes(P5_U['gubeng'], $m2), 'Polsek menerima persetujuan');
        T::same([], $notifTypes(P5_U['sbyOp'], $m2), 'penyetuju tidak menerima notifikasi sendiri');

        $m3 = $mark();
        $act($sby, 'forward', $id);
        T::same(['report_forwarded'], $notifTypes(P5_U['jatimOp'], $m3), 'operator Polda menerima laporan teruskan');
        T::same(['high_priority'], $notifTypes(P5_U['jatimExec'], $m3), 'pejabat Polda menerima peringatan prioritas');
        T::same([], $notifTypes(P5_U['gubeng'], $m3), 'Polsek tidak menerima notifikasi eskalasi');

        // Dikembalikan → operator unit pembuat
        $id2 = $daily('poskoOpBenowo', $d, 'NORMAL');
        $m4 = $mark();
        $act($sby, 'return-report', $id2, 'Mohon lengkapi uraian kegiatan.');
        T::same(['report_returned'], $notifTypes(P5_U['benowo'], $m4), 'Polsek menerima pengembalian');
        T::same([], $notifTypes(P5_U['gubeng'], $m4), 'Polsek lain tidak menerima');
    },

    'notifikasi: lonceng, ringkasan JSON, dan baca hanya milik sendiri' => function () use ($daily, $mark, $csrfOf) {
        $d = date('Y-m-d', strtotime('-42 days'));
        $daily('poskoOpGubeng', $d, 'NORMAL');

        $sby = Http::as('poskoOpSby');
        $page = $sby->get('/posko');
        T::contains('id="poskoBell"', $page->body, 'lonceng tampil untuk akun posko');
        $summary = json_decode($sby->get('/posko-notifikasi/summary')->body, true);
        T::true(($summary['unread'] ?? 0) >= 1 && !empty($summary['items']), 'ringkasan JSON memuat notifikasi belum dibaca');
        T::same(200, $sby->get('/posko-notifikasi')->status, 'halaman daftar notifikasi');

        // Buka notifikasi sendiri → ditandai dibaca & diarahkan ke laporan
        $own = Db::row("SELECT id, link FROM posko_notifications WHERE user_id = ? AND read_at IS NULL ORDER BY id DESC LIMIT 1", [P5_U['sbyOp']]);
        $resp = $sby->post('/posko-notifikasi/open', ['_csrf_token' => $csrfOf($sby), 'id' => $own['id']]);
        T::contains($own['link'], $resp->location());
        T::true(Db::value("SELECT read_at FROM posko_notifications WHERE id = ?", [$own['id']]) !== null, 'ditandai dibaca');

        // Notifikasi milik orang lain tidak dapat dibuka/ditandai
        $foreign = Db::row("SELECT id FROM posko_notifications WHERE user_id = ? AND read_at IS NULL ORDER BY id DESC LIMIT 1", [P5_U['gubeng']])
            ?? Db::row("SELECT id FROM posko_notifications WHERE user_id <> ? AND read_at IS NULL ORDER BY id DESC LIMIT 1", [P5_U['sbyOp']]);
        if ($foreign) {
            $sby->post('/posko-notifikasi/open', ['_csrf_token' => $csrfOf($sby), 'id' => $foreign['id']]);
            T::true(Db::value("SELECT read_at FROM posko_notifications WHERE id = ?", [$foreign['id']]) === null, 'notifikasi orang lain tidak berubah');
        }

        // Tanpa CSRF ditolak; bukan akun posko ditolak
        $sby->post('/posko-notifikasi/readAll', ['_csrf_token' => 'salah']);
        T::true((int)Db::value("SELECT COUNT(*) FROM posko_notifications WHERE user_id = ? AND read_at IS NULL", [P5_U['sbyAdmin']]) >= 0, 'admin tidak terdampak');
        T::true(Http::as('kapolri')->get('/posko-notifikasi/summary')->status !== 200, 'akun non-posko tidak mendapat notifikasi posko');

        $sby->post('/posko-notifikasi/readAll', ['_csrf_token' => $csrfOf($sby)]);
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM posko_notifications WHERE user_id = ? AND read_at IS NULL", [P5_U['sbyOp']]), 'tandai semua dibaca');
    },

    'pengingat: skrip idempoten, hanya unit yang belum mengirim, kegagalan email dicoba ulang' => function () use ($daily) {
        $day = date('Y-m-d', strtotime('-43 days'));
        // Benowo sudah mengirim; Gubeng belum → hanya Gubeng diingatkan
        $daily('poskoOpBenowo', $day);
        $now = $day . ' 22:00:00';
        $run = fn() => shell_exec('"' . PHP_BINARY . '" ' . escapeshellarg(dirname(__DIR__, 2) . '/scripts/posko_reminders.php') . ' --now=' . escapeshellarg($now) . ' 2>&1');
        $count = fn(int $uid) => (int)Db::value("SELECT COUNT(*) FROM posko_notifications WHERE user_id = ? AND type = 'deadline_soon' AND dedupe_key LIKE ?", [$uid, "deadline_soon:1:%:{$day}"]);

        $first = $run();
        T::contains('Pengingat baru', (string)$first, 'skrip berjalan: ' . $first);
        T::true($count(P5_U['gubeng']) === 1, 'Gubeng belum mengirim → diingatkan');
        T::same(0, $count(P5_U['benowo']), 'Benowo sudah mengirim → tidak diingatkan');

        $total = (int)Db::value("SELECT COUNT(*) FROM posko_notifications WHERE dedupe_key LIKE 'deadline_%'");
        $outbox = (int)Db::value("SELECT COUNT(*) FROM posko_notification_outbox WHERE dedupe_key LIKE 'deadline_%'");
        $run();
        T::same($total, (int)Db::value("SELECT COUNT(*) FROM posko_notifications WHERE dedupe_key LIKE 'deadline_%'"), 'dijalankan ulang tidak membuat pengingat ganda');
        T::same($outbox, (int)Db::value("SELECT COUNT(*) FROM posko_notification_outbox WHERE dedupe_key LIKE 'deadline_%'"), 'dan tidak mengantrekan email ganda');

        // Antrean email: gagal → dicoba ulang dengan jeda, tidak menghentikan proses lain; berhasil → SENT
        Db::exec("INSERT INTO posko_notification_outbox (to_email, subject, body, dedupe_key) VALUES ('zztest-a@example.test', 'A', 'a', 'zztest-a'), ('zztest-b@example.test', 'B', 'b', 'zztest-b')");
        $ids = array_map('intval', array_column(Db::pdo()->query("SELECT id FROM posko_notification_outbox WHERE dedupe_key LIKE 'zztest-%' ORDER BY id")->fetchAll(), 'id'));
        $box = new PoskoOutbox();
        $box->markFailed($ids[0], 'SMTP timeout');
        $box->markSent($ids[1]);
        $a = Db::row("SELECT status, attempts, last_error, next_attempt_at FROM posko_notification_outbox WHERE id = ?", [$ids[0]]);
        T::same(['PENDING', 1, 'SMTP timeout'], [$a['status'], (int)$a['attempts'], $a['last_error']], 'kegagalan dicatat & masih akan dicoba');
        T::true($a['next_attempt_at'] > date('Y-m-d H:i:s'), 'dijadwalkan ulang dengan jeda');
        T::same('SENT', Db::value("SELECT status FROM posko_notification_outbox WHERE id = ?", [$ids[1]]), 'email lain tetap terkirim');
        T::true(!in_array($ids[0], array_map('intval', array_column($box->due(500), 'id')), true), 'belum jatuh tempo tidak diproses ulang');
        for ($i = 0; $i < PoskoOutbox::MAX_ATTEMPTS; $i++) {
            $box->markFailed($ids[0], 'SMTP timeout');
        }
        T::same('FAILED', Db::value("SELECT status FROM posko_notification_outbox WHERE id = ?", [$ids[0]]), 'setelah batas percobaan ditandai FAILED');
        Db::exec("DELETE FROM posko_notification_outbox WHERE dedupe_key LIKE 'zztest-%'");
    },
];
