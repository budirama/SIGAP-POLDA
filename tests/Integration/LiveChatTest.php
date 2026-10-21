<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

/** Klien warga baru + token CSRF sesinya */
$visitor = function (): array {
    $client = new Http(TEST_BASE_URL);
    return [$client, $client->get('/report/create')->csrf()];
};
$json = fn($res) => json_decode($res->body, true) ?? [];
$startChat = function (Http $client, string $csrf, string $suffix) use ($json): array {
    // Suite membuka banyak chat dari IP yang sama; batas sesi per IP diuji terpisah
    Db::exec("DELETE FROM throttle_events WHERE bucket = 'chat_start_ip'");
    $res = $client->post('/chat/start', [
        '_csrf_token' => $csrf,
        'name'        => Db::PREFIX . "warga {$suffix}",
        'phone'       => '081234567890',
        'message'     => "Halo, saya ingin melapor ({$suffix})",
    ]);
    T::same(200, $res->status, 'mulai chat: ' . $res->body);
    $id = (int)Db::value("SELECT id FROM chat_conversations WHERE visitor_name = ?", [Db::PREFIX . "warga {$suffix}"]);
    // Percakapan baru dilayani chatbot; suite ini menguji alur petugas, jadi warga langsung meminta petugas
    $state = $json($res);
    if (($state['status'] ?? null) === 'BOT') {
        $state = $json($client->post('/chat/handoff', ['_csrf_token' => $csrf, 'after' => 0]));
    }
    return [$id, $state];
};
$agentPost = function (Http $agent, string $path, array $fields) {
    // Konsol Omnichat menyimpan token CSRF di atribut data-csrf
    preg_match('/data-csrf="([a-f0-9]+)"/', $agent->get('/omnichat')->body, $m);
    return $agent->post($path, ['_csrf_token' => $m[1] ?? ''] + $fields);
};

return [
    'widget Live Chat tampil di portal & validasi input' => function () use ($visitor, $json) {
        $home = (new Http(TEST_BASE_URL))->get('/')->body;
        T::contains('id="livechat"', $home);
        T::contains('/assets/js/livechat.js', $home);

        [$client, $csrf] = $visitor();
        $res = $client->post('/chat/start', ['_csrf_token' => $csrf, 'name' => '', 'message' => 'x']);
        T::same(422, $res->status, 'nama wajib');
        $res = $client->post('/chat/start', ['_csrf_token' => $csrf, 'name' => Db::PREFIX . 'x', 'phone' => 'abc', 'message' => 'x']);
        T::same(422, $res->status, 'HP tidak valid');
        T::same(403, $client->post('/chat/start', ['name' => Db::PREFIX . 'x', 'message' => 'x'])->status, 'tanpa CSRF');
        T::same(false, $json($client->get('/chat/poll'))['active'] ?? null, 'belum ada chat');
    },

    'sesi chat baru dibatasi per IP' => function () use ($visitor) {
        Db::exec("DELETE FROM throttle_events WHERE bucket = 'chat_start_ip'");
        $last = null;
        for ($i = 1; $i <= 6; $i++) {
            [$c, $csrf] = $visitor();
            $last = $c->post('/chat/start', ['_csrf_token' => $csrf, 'name' => Db::PREFIX . "spam {$i}", 'message' => 'spam']);
        }
        T::same(429, $last->status, 'sesi ke-6 dalam 1 jam ditolak');
    },

    'alur chat: warga ↔ agen, ambil alih atomik, hanya penangan yang membalas' => function () use ($visitor, $startChat, $json, $agentPost) {
        [$warga, $csrf] = $visitor();
        [$id, $state] = $startChat($warga, $csrf, 'alur');
        T::same('WAITING', $state['status']);
        T::contains('Halo, saya ingin melapor', implode(' ', array_column($state['messages'], 'body')));

        $humas = Http::as('humas');
        $list = $json($humas->get('/omnichat/conversations?scope=WAITING'));
        T::true(in_array($id, array_column($list['conversations'], 'id'), true), 'tampil di antrean agen');

        T::same(200, $agentPost($humas, '/omnichat/claim', ['conversation_id' => $id])->status, 'humas ambil alih');
        $spkt = Http::as('spkt');
        T::same(409, $agentPost($spkt, '/omnichat/claim', ['conversation_id' => $id])->status, 'klaim kedua ditolak');
        T::same(403, $agentPost($spkt, '/omnichat/send', ['conversation_id' => $id, 'message' => 'serobot'])->status, 'bukan penangan');

        T::same(200, $agentPost($humas, '/omnichat/send', ['conversation_id' => $id, 'message' => 'Selamat malam, ada yang bisa dibantu?'])->status);
        $poll = $json($warga->get('/chat/poll?after=0'));
        T::same('ACTIVE', $poll['status']);
        T::true(str_contains((string)$poll['agent'], 'Cindy'), 'nama petugas tampil ke warga');
        T::contains('ada yang bisa dibantu', implode(' ', array_column($poll['messages'], 'body')));

        $lastId = max(array_column($poll['messages'], 'id'));
        T::same([], $json($warga->get("/chat/poll?after={$lastId}"))['messages'], 'polling inkremental');

        T::same(200, $warga->post('/chat/send', ['_csrf_token' => $csrf, 'message' => 'Terima kasih Pak'])->status);
        $thread = $json($humas->get("/omnichat/messages/{$id}"));
        T::contains('Terima kasih Pak', implode(' ', array_column($thread['messages'], 'body')));
    },

    'percakapan terikat sesi warga & Itwasum hanya memantau' => function () use ($visitor, $startChat, $json, $agentPost) {
        [$wargaA, $csrfA] = $visitor();
        [$id] = $startChat($wargaA, $csrfA, 'isolasi');

        [$lain, $csrfLain] = $visitor();
        T::same(false, $json($lain->get('/chat/poll'))['active'] ?? null, 'sesi lain tidak melihat chat');
        T::same(404, $lain->post('/chat/send', ['_csrf_token' => $csrfLain, 'message' => 'menyusup'])->status);

        $itwasum = Http::as('itwasum');
        T::same(200, $itwasum->get('/omnichat/conversations')->status, 'Itwasum boleh melihat');
        T::same(403, $agentPost($itwasum, '/omnichat/claim', ['conversation_id' => $id])->status, 'Itwasum tidak boleh bertindak');

        // Warga & role lain tidak bisa membuka konsol
        T::same(302, (new Http(TEST_BASE_URL))->get('/omnichat/conversations')->status, 'anonim');
        T::same(302, Http::as('penyidik')->get('/omnichat')->status, 'penyidik');
    },

    'konversi chat menjadi tiket LIVE_CHAT; PIN hanya untuk warga pemilik chat' => function () use ($visitor, $startChat, $json, $agentPost) {
        [$warga, $csrf] = $visitor();
        [$id] = $startChat($warga, $csrf, 'konversi');
        $spkt = Http::as('spkt');

        T::same(403, $agentPost($spkt, '/omnichat/convert', ['conversation_id' => $id, 'category_id' => 1, 'title' => 'x', 'incident_location' => 'y'])->status, 'harus ambil alih dulu');
        $agentPost($spkt, '/omnichat/claim', ['conversation_id' => $id]);
        T::same(422, $agentPost($spkt, '/omnichat/convert', ['conversation_id' => $id, 'category_id' => 999, 'title' => 'x', 'incident_location' => 'y'])->status, 'kategori tidak valid');

        $res = $agentPost($spkt, '/omnichat/convert', [
            'conversation_id'   => $id,
            'category_id'       => 1,
            'title'             => Db::PREFIX . 'tiket dari chat',
            'incident_location' => 'Jakarta Selatan',
        ]);
        T::same(200, $res->status, $res->body);
        $ticket = $json($res)['ticket'];
        $report = Db::row("SELECT id, source_channel, security_pin, description FROM reports WHERE ticket_number = ?", [$ticket]);
        T::same('LIVE_CHAT', $report['source_channel']);
        T::contains('Halo, saya ingin melapor (konversi)', $report['description'], 'kronologi dari pesan warga');
        T::same(409, $agentPost($spkt, '/omnichat/convert', ['conversation_id' => $id, 'category_id' => 1, 'title' => 'x', 'incident_location' => 'y'])->status, 'tidak dua kali');

        $thread = $spkt->get("/omnichat/messages/{$id}")->body;
        T::notContains($report['security_pin'], $thread, 'PIN tidak terlihat agen');

        $poll = $json($warga->get('/chat/poll?after=0'));
        T::same($ticket, $poll['ticket'], 'tiket tampil di widget warga');

        [$lain, $csrfLain] = $visitor();
        T::same(404, $lain->post('/chat/ticket', ['_csrf_token' => $csrfLain])->status, 'sesi lain tidak bisa klaim tiket');

        $open = $json($warga->post('/chat/ticket', ['_csrf_token' => $csrf]));
        T::contains(rawurlencode($ticket), $open['url']);
        T::contains($report['security_pin'], $warga->get('/report/success?ticket=' . rawurlencode($ticket))->body, 'warga melihat PIN');

        T::same('LIVE_CHAT', Db::value("SELECT source_channel FROM reports WHERE id = ?", [$report['id']]));
        T::true((int)Db::value("SELECT COUNT(*) FROM audit_logs WHERE action = 'chat.convert' AND report_id = ?", [$report['id']]) === 1, 'tercatat di audit');
    },

    'penutupan percakapan oleh warga & agen' => function () use ($visitor, $startChat, $json, $agentPost) {
        [$warga, $csrf] = $visitor();
        [$id] = $startChat($warga, $csrf, 'tutup');
        $spkt = Http::as('spkt');
        $agentPost($spkt, '/omnichat/claim', ['conversation_id' => $id]);
        T::same(200, $agentPost($spkt, '/omnichat/close', ['conversation_id' => $id])->status);
        T::same('CLOSED', $json($warga->get('/chat/poll'))['status']);
        T::same(409, $warga->post('/chat/send', ['_csrf_token' => $csrf, 'message' => 'halo?'])->status, 'chat tertutup');

        [$warga2, $csrf2] = $visitor();
        [$id2] = $startChat($warga2, $csrf2, 'akhiri');
        $warga2->post('/chat/end', ['_csrf_token' => $csrf2]);
        T::same('CLOSED', Db::value("SELECT status FROM chat_conversations WHERE id = ?", [$id2]));
        T::same(false, $json($warga2->get('/chat/poll'))['active'] ?? null, 'sesi dilepas');
    },
];
