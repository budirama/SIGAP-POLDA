<?php

use Tests\Db;
use Tests\Http;
use Tests\T;

$dispatch = function (Http $spkt, int $reportId, int $satkerId): void {
    $spkt->post('/spkt/dispatch', ['_csrf_token' => $spkt->get('/spkt')->csrf(), 'report_id' => $reportId, 'satker_id' => $satkerId, 'dispatch_notes' => 'uji']);
};

return [
    'laporan yang sedang disidik, selesai, atau ditolak tidak dapat didisposisikan' => function () use ($dispatch) {
        $spkt = Http::as('spkt');
        foreach (['INVESTIGATING', 'CLOSED'] as $status) {
            $row = Db::row("SELECT id, current_satker_id, assigned_investigator_id FROM reports WHERE status = ? LIMIT 1", [$status]);
            $target = (int)Db::value("SELECT id FROM satkers WHERE id <> ? ORDER BY id LIMIT 1", [(int)$row['current_satker_id']]);
            $dispatch($spkt, (int)$row['id'], $target);
            T::contains('tidak dapat didisposisikan', $spkt->flash(), $status);
            T::same(
                [$status, $row['current_satker_id'], $row['assigned_investigator_id']],
                array_values(Db::row("SELECT status, current_satker_id, assigned_investigator_id FROM reports WHERE id = ?", [$row['id']])),
                "{$status} tidak berubah"
            );
        }
    },

    'disposisi ke Satker yang tidak ada ditolak' => function () use ($dispatch) {
        [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'satker fiktif']);
        $id = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);
        $spkt = Http::as('spkt');
        $dispatch($spkt, $id, 99999);
        T::contains('Satker tujuan disposisi yang sah', $spkt->flash());
        T::same('SUBMITTED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]));
    },

    'disposisi ulang sebelum penyidikan mencabut penyidik lama' => function () use ($dispatch) {
        $penyidikRian = (int)Db::value("SELECT id FROM users WHERE email = 'penyidik.rian@sigap.polri.go.id'");
        [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'disposisi ulang']);
        $id = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);

        $spkt = Http::as('spkt');
        $dispatch($spkt, $id, 2);
        $kanit = Http::as('kanit');
        $kanit->post('/satker/assign', ['_csrf_token' => $kanit->get('/satker')->csrf(), 'report_id' => $id, 'investigator_id' => $penyidikRian]);
        T::same('ASSIGNED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]), 'sudah ditugaskan');
        $penyidik = Http::as('penyidik');
        T::same(200, $penyidik->get("/investigator/detail/{$id}")->status, 'penyidik lama memegang kasus');

        $dispatch($spkt, $id, 3);
        T::same(['DISPATCHED', 3, null], [
            Db::value("SELECT status FROM reports WHERE id = ?", [$id]),
            (int)Db::value("SELECT current_satker_id FROM reports WHERE id = ?", [$id]),
            Db::value("SELECT assigned_investigator_id FROM reports WHERE id = ?", [$id]),
        ], 'pindah Satker & penyidik dicabut');
        T::same('Disposisi Ulang ke Satker Lain', Db::value("SELECT title FROM report_timeline WHERE report_id = ? ORDER BY id DESC LIMIT 1", [$id]));
        T::same(302, $penyidik->get("/investigator/detail/{$id}")->status, 'penyidik lama kehilangan akses');
    },

    'Itwasum hanya dapat melihat meja SPKT, tidak dapat mendisposisi atau menolak' => function () use ($dispatch) {
        [, $ticket] = Db::createReport(['title' => Db::PREFIX . 'itwasum']);
        $id = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$ticket]);

        $itwasda = Http::as('itwasum');
        $page = $itwasda->get('/spkt');
        T::same(200, $page->status);
        T::contains('Mode pengawasan', $page->body);
        T::notContains('onclick="openDispatchModal(', $page->body, 'tombol disposisi');
        T::notContains('onclick="openRejectModal(', $page->body, 'tombol tolak');

        $dispatch($itwasda, $id, 2);
        T::contains('peran pengawasan hanya dapat melihat', $itwasda->flash());
        $itwasda->post('/spkt/reject', ['_csrf_token' => $itwasda->get('/spkt')->csrf(), 'report_id' => $id, 'rejection_reason' => 'x']);
        T::contains('peran pengawasan hanya dapat melihat', $itwasda->flash());
        T::same('SUBMITTED', Db::value("SELECT status FROM reports WHERE id = ?", [$id]));
    },

    'tombol disposisi hanya tampil untuk status pra-penyidikan' => function () {
        $html = Http::as('spkt')->get('/spkt')->body;
        foreach (Db::pdo()->query("SELECT id, status FROM reports")->fetchAll() as $r) {
            $hasButton = str_contains($html, "openDispatchModal({&quot;id&quot;:{$r['id']},");
            T::same(in_array($r['status'], \App\Config\Constants::DISPATCHABLE_STATUSES, true), $hasButton, "laporan #{$r['id']} ({$r['status']})");
        }
    },
];
