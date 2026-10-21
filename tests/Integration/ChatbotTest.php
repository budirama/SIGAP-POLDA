<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$json = fn($res) => json_decode($res->body, true) ?? [];

/** Warga baru memulai chat (pesan pertama opsional); kembalikan [klien, csrf, id percakapan, state] */
$start = function (string $suffix, string $message = '') use ($json): array {
    Db::exec("DELETE FROM throttle_events WHERE bucket IN ('chat_start_ip', 'chat_msg_ip')");
    $client = new Http(TEST_BASE_URL);
    $csrf = $client->get('/report/create')->csrf();
    $res = $client->post('/chat/start', ['_csrf_token' => $csrf, 'name' => Db::PREFIX . "bot {$suffix}", 'message' => $message]);
    T::same(200, $res->status, 'mulai chat: ' . $res->body);
    $id = (int)Db::value("SELECT id FROM chat_conversations WHERE visitor_name = ?", [Db::PREFIX . "bot {$suffix}"]);
    return [$client, $csrf, $id, $json($res)];
};
$say = fn(Http $c, string $csrf, string $text, ?string $intent = null) =>
    $json($c->post('/chat/send', ['_csrf_token' => $csrf, 'message' => $text, 'after' => 0] + ($intent ? ['intent' => $intent] : [])));
$lastBot = function (array $state): array {
    $bots = array_values(array_filter($state['messages'] ?? [], fn($m) => $m['from'] === 'bot'));
    return end($bots) ?: [];
};

return [
    'chat baru disambut Asisten Virtual dengan menu topik' => function () use ($start, $lastBot) {
        [, , $id, $state] = $start('salam');
        T::same('BOT', $state['status']);
        $greet = $lastBot($state);
        T::contains('Asisten Virtual', $greet['body']);
        T::same('Asisten Virtual SIGAP', $greet['name']);
        $menu = array_column($greet['meta']['quick_replies'], 'intent');
        T::true(in_array('CARA_LAPOR', $menu, true) && in_array('PETUGAS', $menu, true), 'menu berisi cara lapor & petugas');
        T::same(0, (int)Db::value("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND sender_type = 'VISITOR'", [$id]), 'tanpa pesan pertama');
    },

    'pertanyaan bebas, tombol cepat & nomor tiket dijawab dari basis pengetahuan' => function () use ($start, $say, $lastBot) {
        $hits = Db::value("SELECT hit_count FROM chatbot_intents WHERE code = 'SKCK'");
        [$c, $csrf, , $state] = $start('jawab', 'Bagaimana cara membuat laporan ke polisi?');
        $answer = $lastBot($state);
        T::contains('formulir Pengaduan Online', $answer['body']);
        T::same('/report/create', $answer['meta']['link']['url']);

        $hilang = $lastBot($say($c, $csrf, 'Kehilangan / menemukan barang', 'HILANG_TEMU'));
        T::contains('Kehilangan dan Temuan', $hilang['body'], 'sebutan layanan terbaru');
        T::notContains('Hilang & Temu', $hilang['body']);
        T::same('Buka Kehilangan dan Temuan', $hilang['meta']['link']['label']);

        $state = $say($c, $csrf, 'SKCK', 'SKCK');
        T::contains('SKCK dapat diajukan', $lastBot($state)['body']);
        T::same((int)$hits + 1, (int)Db::value("SELECT hit_count FROM chatbot_intents WHERE code = 'SKCK'"), 'statistik dipakai');
        Db::exec("UPDATE chatbot_intents SET hit_count = ? WHERE code = 'SKCK'", [$hits]); // jangan ubah statistik nyata

        $state = $say($c, $csrf, 'status laporan LP-POLRI-202610-0001 bagaimana?');
        $ticket = $lastBot($state);
        T::contains('PIN 6 digit', $ticket['body']);
        T::same('/report/track?ticket=LP-POLRI-202610-0001', $ticket['meta']['link']['url'], 'tidak membuka status tanpa PIN');
        T::same('BOT', $state['status']);
    },

    'pertanyaan tidak dipahami dicatat; kedua kalinya bot menawarkan petugas' => function () use ($start, $say, $lastBot) {
        [$c, $csrf, $id] = $start('bingung');
        $first = $lastBot($say($c, $csrf, 'qwerty asdfgh zxcvb'));
        T::contains('belum memahami', $first['body']);
        $second = $lastBot($say($c, $csrf, 'lorem ipsum dolor'));
        T::contains('dihubungkan dengan petugas', $second['body']);
        T::same('PETUGAS', $second['meta']['quick_replies'][0]['intent']);
        T::same(2, (int)Db::value("SELECT COUNT(*) FROM chatbot_misses WHERE conversation_id = ?", [$id]));
        T::contains('lorem ipsum dolor', Http::as('spkt')->get('/chatbot')->body, 'tampil di daftar belum terjawab');
    },

    'minta petugas: diteruskan ke antrean, bot berhenti menjawab, petugas bisa membalas' => function () use ($start, $say, $json) {
        [$c, $csrf, $id] = $start('petugas');
        $state = $say($c, $csrf, 'saya mau bicara dengan petugas');
        T::same('WAITING', $state['status']);
        T::true(Db::value("SELECT handoff_at FROM chat_conversations WHERE id = ?", [$id]) !== null, 'waktu serah dicatat');

        $botCount = (int)Db::value("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND sender_type = 'BOT'", [$id]);
        $say($c, $csrf, 'Cara lapor bagaimana?');
        T::same($botCount, (int)Db::value("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND sender_type = 'BOT'", [$id]), 'bot diam setelah diteruskan');

        $list = $json(Http::as('spkt')->get('/omnichat/conversations?scope=WAITING'));
        T::true(in_array($id, array_column($list['conversations'], 'id'), true), 'masuk antrean petugas');
    },

    'darurat: langsung diteruskan sebagai prioritas & tampil teratas di Omnichat' => function () use ($start, $json) {
        [, , $id, $state] = $start('darurat', 'Tolong saya sedang ditodong orang di jalan');
        T::same('WAITING', $state['status']);
        T::same(1, (int)Db::value("SELECT is_urgent FROM chat_conversations WHERE id = ?", [$id]));
        T::contains('110', implode(' ', array_column($state['messages'], 'body')));

        $list = $json(Http::as('spkt')->get('/omnichat/conversations?scope=WAITING'))['conversations'];
        T::same($id, $list[0]['id'], 'prioritas di urutan pertama');
        T::same(true, $list[0]['urgent']);
    },

    'petugas dapat memantau & mengambil alih percakapan yang masih dilayani bot' => function () use ($start, $json) {
        [, , $id] = $start('ambilalih');
        $humas = Http::as('humas');
        T::true(in_array($id, array_column($json($humas->get('/omnichat/conversations?scope=BOT'))['conversations'], 'id'), true), 'tab Asisten Virtual');
        T::true(!in_array($id, array_column($json($humas->get('/omnichat/conversations?scope=open'))['conversations'], 'id'), true), 'tidak membanjiri tab Aktif');

        preg_match('/data-csrf="([a-f0-9]+)"/', $humas->get('/omnichat')->body, $m);
        T::same(200, $humas->post('/omnichat/claim', ['_csrf_token' => $m[1] ?? '', 'conversation_id' => $id])->status);
        T::same('ACTIVE', Db::value("SELECT status FROM chat_conversations WHERE id = ?", [$id]));
    },

    'basis pengetahuan: tambah intent, uji pertanyaan, validasi tautan, peran pantau hanya baca' => function () use ($start, $say, $lastBot, $json) {
        $spkt = Http::as('spkt');
        $csrf = $spkt->get('/chatbot')->csrf();
        $fields = [
            '_csrf_token' => $csrf, 'code' => 'ZZTEST_PAJAK', 'label' => 'Uji pajak', 'keywords' => 'zztestpajak, pajak uji coba',
            'answer' => 'Jawaban uji pajak', 'link_url' => 'javascript:alert(1)', 'action' => 'NONE', 'is_active' => 1, 'sort_order' => 5,
        ];
        $spkt->post('/chatbot/save', $fields);
        T::same(null, Db::row("SELECT id FROM chatbot_intents WHERE code = 'ZZTEST_PAJAK'"), 'tautan javascript: ditolak');

        $spkt->post('/chatbot/save', ['link_url' => '/report/create'] + $fields);
        T::true(Db::row("SELECT id FROM chatbot_intents WHERE code = 'ZZTEST_PAJAK'") !== null, 'tersimpan');

        $test = $json($spkt->get('/chatbot/test?q=' . urlencode('tanya zztestpajak dong')));
        T::same('ZZTEST_PAJAK', $test['code']);

        [$c, $chatCsrf] = $start('kb');
        T::same('Jawaban uji pajak', $lastBot($say($c, $chatCsrf, 'tanya zztestpajak dong'))['body'], 'langsung dipakai chatbot');

        $itwasum = Http::as('itwasum');
        $itwasum->post('/chatbot/save', ['code' => 'ZZTEST_ITWASUM', '_csrf_token' => $itwasum->get('/chatbot')->csrf()] + $fields);
        T::same(null, Db::row("SELECT id FROM chatbot_intents WHERE code = 'ZZTEST_ITWASUM'"), 'Itwasum hanya melihat');
    },
];
