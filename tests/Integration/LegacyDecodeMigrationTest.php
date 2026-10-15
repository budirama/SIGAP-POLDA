<?php

use Tests\Db;
use Tests\T;

require_once dirname(__DIR__, 2) . '/database/migrations/2026_10_15_decode_legacy_escaped_input.php';

// Rentang waktu buatan yang tidak bersinggungan dengan data seeder
const LEGACY_SINCE = '2000-01-01 00:00:00';
const LEGACY_BEFORE = '2000-01-02 00:00:00';

return [
    'data seeder tidak berisi entitas ter-escape (rentang waktu nyata)' => function () {
        $result = decodeLegacyEscapedInput(Db::pdo(), null, date('Y-m-d H:i:s', time() + 60), false);
        T::same([], $result['changes']);
    },

    'data ter-escape lama di-decode, teks sah setelah perbaikan tidak tersentuh' => function () {
        // Laporan "lama" (sebelum perbaikan): tersimpan ter-escape
        [, $oldTicket] = Db::createReport(['title' => Db::PREFIX . 'lama']);
        $oldId = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$oldTicket]);
        Db::exec("UPDATE reports SET title = ?, incident_location = ?, created_at = '2000-01-01 10:00:00' WHERE id = ?",
            [Db::PREFIX . 'Tom &amp; Jerry &quot;uji&quot; &#039;x&#039; &lt;b&gt;', 'Jl. A&amp;B', $oldId]);
        Db::exec("UPDATE report_timeline SET public_notes = 'Catatan &amp; tindak lanjut', created_at = '2000-01-01 10:00:00' WHERE report_id = ?", [$oldId]);

        // Laporan "baru" (setelah perbaikan): pengguna sungguh mengetik "&amp;"
        [, $newTicket] = Db::createReport(['title' => Db::PREFIX . 'baru']);
        $newId = (int)Db::value("SELECT id FROM reports WHERE ticket_number = ?", [$newTicket]);
        Db::exec("UPDATE reports SET title = ?, created_at = '2000-01-03 10:00:00' WHERE id = ?", [Db::PREFIX . 'kode HTML &amp; literal', $newId]);

        // Dry-run: melaporkan perubahan tanpa menulis
        $dry = decodeLegacyEscapedInput(Db::pdo(), LEGACY_SINCE, LEGACY_BEFORE, false);
        T::same(['reports.title' => 1, 'reports.incident_location' => 1, 'report_timeline.public_notes' => 1], $dry['changes'], 'ringkasan dry-run');
        T::same(Db::PREFIX . 'Tom &amp; Jerry &quot;uji&quot; &#039;x&#039; &lt;b&gt;', Db::value("SELECT title FROM reports WHERE id = ?", [$oldId]), 'dry-run tidak mengubah data');

        // Apply
        decodeLegacyEscapedInput(Db::pdo(), LEGACY_SINCE, LEGACY_BEFORE, true);
        T::same(Db::PREFIX . 'Tom & Jerry "uji" \'x\' <b>', Db::value("SELECT title FROM reports WHERE id = ?", [$oldId]), 'judul lama');
        T::same('Jl. A&B', Db::value("SELECT incident_location FROM reports WHERE id = ?", [$oldId]), 'lokasi lama');
        T::same('Catatan & tindak lanjut', Db::value("SELECT public_notes FROM report_timeline WHERE report_id = ?", [$oldId]), 'timeline lama');
        T::same(Db::PREFIX . 'kode HTML &amp; literal', Db::value("SELECT title FROM reports WHERE id = ?", [$newId]), 'data setelah perbaikan tidak tersentuh');

        // Idempoten: menjalankan ulang tidak mengubah apa pun
        T::same([], decodeLegacyEscapedInput(Db::pdo(), LEGACY_SINCE, LEGACY_BEFORE, true)['changes'], 'jalankan ulang');
    },
];
