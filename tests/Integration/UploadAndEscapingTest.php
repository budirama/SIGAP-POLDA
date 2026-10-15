<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$tmp = sys_get_temp_dir();
$png = $tmp . '/sigap_test_bukti.png';
file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
$fakeJpg = $tmp . '/sigap_test_shell.jpg';
file_put_contents($fakeJpg, '<?php echo "pwned"; ?>');

return [
    'file bukti tersimpan privat & hanya dapat diunduh pihak berwenang' => function () use ($png, $fakeJpg) {
        [$pelapor, $ticket] = Db::createReport(['title' => Db::PREFIX . 'lampiran'], [
            new CURLFile($png, 'image/png', 'bukti.png'),
            new CURLFile($fakeJpg, 'image/jpeg', 'foto.jpg'),
        ]);
        T::contains('1 file bukti tidak dapat dilampirkan', $pelapor->flash(), 'file PHP palsu ditolak');

        $att = Db::row("SELECT a.id, a.file_path FROM report_attachments a JOIN reports r ON r.id = a.report_id WHERE r.ticket_number = ?", [$ticket]);
        T::true($att !== null, 'lampiran PNG tersimpan');
        T::true((bool)preg_match('#^uploads/[0-9a-f]{32}\.png$#', $att['file_path']), 'nama acak: ' . $att['file_path']);
        T::true(is_file(\App\Core\Upload::absolutePath($att['file_path'])), 'file ada di storage');

        $res = $pelapor->get("/file/attachment/{$att['id']}");
        T::same(200, $res->status, 'pelapor');
        T::same('image/png', $res->header('content-type'));
        T::same(file_get_contents($png), $res->body, 'isi file identik');

        T::same(200, Http::as('spkt')->get("/file/attachment/{$att['id']}")->status, 'SPKT');
        T::same(404, (new Http(TEST_BASE_URL))->get("/file/attachment/{$att['id']}")->status, 'anonim');
        T::same(404, Http::as('penyidik')->get("/file/attachment/{$att['id']}")->status, 'penyidik bukan kasusnya');

        $root = preg_replace('#/public$#', '', TEST_BASE_URL);
        T::same(403, (new Http($root))->get('/storage/' . $att['file_path'])->status, 'akses langsung storage');
    },

    'lampiran pesan klarifikasi warga hanya untuk pelapor & personil' => function () use ($png) {
        [$pelapor, $ticket] = Db::createReport(['title' => Db::PREFIX . 'pesan lampiran']);
        $pelapor->post('/report/sendMessage', [
            '_csrf_token'        => $pelapor->get("/report/track?ticket={$ticket}")->csrf(),
            'ticket_number'      => $ticket,
            'message_text'       => 'pesan uji',
            'clarification_file' => new CURLFile($png, 'image/png', 'klarifikasi.png'),
        ]);
        $msgId = (int)Db::value("SELECT m.id FROM case_messages m JOIN reports r ON r.id = m.report_id WHERE r.ticket_number = ? AND m.attachment_path IS NOT NULL", [$ticket]);
        T::true($msgId > 0, 'pesan berlampiran tersimpan');
        T::contains("/file/message/{$msgId}", $pelapor->get("/report/track?ticket={$ticket}")->body, 'link di halaman tracking');
        T::same(200, $pelapor->get("/file/message/{$msgId}")->status, 'pelapor');
        T::same(404, (new Http(TEST_BASE_URL))->get("/file/message/{$msgId}")->status, 'anonim');
    },

    'input disimpan mentah dan ditampilkan dengan escape tunggal' => function () {
        $title = Db::PREFIX . 'Tom & Jerry "uji" <script>alert(1)</script>';
        [$pelapor, $ticket] = Db::createReport([
            'title'             => $title,
            'incident_location' => 'Jl. A&B </script><b>x',
            'latitude'          => '-6.2',
            'longitude'         => '106.8',
            'urgency_level'     => 'HACK',
        ]);

        $row = Db::row("SELECT title, incident_location, urgency_level FROM reports WHERE ticket_number = ?", [$ticket]);
        T::same($title, $row['title'], 'judul mentah di DB');
        T::same('NORMAL', $row['urgency_level'], 'urgency tak dikenal jatuh ke NORMAL');

        $track = $pelapor->get("/report/track?ticket={$ticket}")->body;
        T::contains('Tom &amp; Jerry &quot;uji&quot; &lt;script&gt;', $track);
        T::notContains('&amp;amp;', $track, 'double escape');
        T::notContains('<script>alert(1)', $track);

        $dashboard = Http::as('kapolda')->get('/executive')->body;
        T::notContains('</script><b>x', $dashboard, 'breakout </script> di JSON heatmap');
        T::contains('<script>', $dashboard, 'JSON heatmap hex-escaped');
    },
];
