<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

return [
    'watchlist SLA menandai Satker dengan kasus lewat tenggat' => function () {
        [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'lewat sla']);
        $id = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);
        $spkt = Http::as('spkt');
        $spkt->post('/spkt/dispatch', ['_csrf_token' => $spkt->get('/spkt')->csrf(), 'report_id' => $id, 'satker_id' => 4, 'dispatch_notes' => 'uji']);
        Db::exec("UPDATE reports SET sla_deadline = NOW() - INTERVAL 2 HOUR WHERE id = ?", [$id]);
        $satkerName = (string)Db::value("SELECT name FROM satkers WHERE id = 4");

        $html = Http::as('kapolri')->get('/executive')->body;
        T::notContains('SLA Terpenuhi', $html, 'badge hardcode lama');
        // Satker dengan kasus lewat SLA diurutkan paling atas dan berlabel merah
        $firstCard = strpos($html, htmlspecialchars($satkerName));
        $badge = strpos($html, '1 Lewat SLA');
        T::true($firstCard !== false && $badge !== false && $badge > $firstCard, 'badge "1 Lewat SLA" pada Satker tujuan');
        T::true($firstCard < strpos($html, 'Sesuai SLA'), 'Satker bermasalah tampil sebelum yang sesuai SLA');
    },

    'clearance rate tidak menghitung laporan yang ditolak' => function () {
        [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'ditolak kpi']);
        $id = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);
        $spkt = Http::as('spkt');
        $spkt->post('/spkt/reject', ['_csrf_token' => $spkt->get('/spkt')->csrf(), 'report_id' => $id, 'rejection_reason' => 'uji kpi']);

        $total = (int)Db::value("SELECT COUNT(*) FROM reports");
        $closed = (int)Db::value("SELECT COUNT(*) FROM reports WHERE status = 'CLOSED'");
        $rejected = (int)Db::value("SELECT COUNT(*) FROM reports WHERE status = 'REJECTED'");
        $expected = round($closed / ($total - $rejected) * 100, 1);

        $html = (new Http(TEST_BASE_URL))->get('/')->body;
        T::contains('>' . $expected . '%<', $html, 'clearance rate di beranda');
    },

    'KPI & daftar atensi Kapolri hanya kasus aktif' => function () {
        $closedId = (int)Db::value("SELECT id FROM reports WHERE status = 'CLOSED' LIMIT 1");
        $before = Db::value("SELECT is_kapolda_attention FROM reports WHERE id = ?", [$closedId]);
        Db::exec("UPDATE reports SET is_kapolda_attention = 1 WHERE id = ?", [$closedId]);
        try {
            $expected = (int)Db::value("SELECT COUNT(*) FROM reports WHERE is_kapolda_attention = 1 AND status NOT IN ('CLOSED','REJECTED')");
            $html = Http::as('kapolri')->get('/executive')->body;
            T::true((bool)preg_match('/text-red-400 font-heading mt-1">(\d+)</', $html, $m), 'KPI atensi ditemukan');
            T::same($expected, (int)$m[1], 'KPI atensi');
        } finally {
            Db::exec("UPDATE reports SET is_kapolda_attention = ? WHERE id = ?", [$before, $closedId]);
        }
    },
];
