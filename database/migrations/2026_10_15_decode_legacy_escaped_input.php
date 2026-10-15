<?php
/**
 * Migrasi: kembalikan input lama yang tersimpan dalam bentuk ter-escape HTML.
 *
 * Sebelum PR #7 (escape di output), Controller::post()/get() menjalankan htmlspecialchars
 * sebelum data disimpan, sehingga teks seperti "Tom & Jerry" tersimpan "Tom &amp; Jerry"
 * dan kini tampil rusak. Skrip ini men-decode kolom input pengguna pada baris yang dibuat
 * SEBELUM perbaikan dideploy (--before), agar teks sah yang diketik setelahnya tidak tersentuh.
 *
 * Penggunaan (default dry-run, tidak mengubah data):
 *   php database/migrations/2026_10_15_decode_legacy_escaped_input.php --before="2026-10-15 20:00:00"
 *   php database/migrations/2026_10_15_decode_legacy_escaped_input.php --before="2026-10-15 20:00:00" --apply
 * Opsional: --since="YYYY-mm-dd HH:ii:ss" untuk membatasi awal rentang.
 */

declare(strict_types=1);

// Kolom yang dahulu diisi dari input pengguna lewat post()/get() (ter-escape sekali)
const LEGACY_ESCAPED_COLUMNS = [
    'reports'            => ['reporter_name_raw', 'reporter_nik_raw', 'reporter_phone', 'title', 'description', 'incident_location', 'rejection_reason'],
    'report_timeline'    => ['public_notes', 'internal_notes', 'sp2hp_number'],
    'case_messages'      => ['sender_name', 'message_text'],
    'report_feedbacks'   => ['comments'],
    'kapolda_directives' => ['directive_text'],
    'social_feeds'       => ['author_username', 'author_name', 'post_content', 'post_url'],
    'users'              => ['name', 'phone'],
];

/**
 * @return array{changes: array<string,int>, samples: list<string>}
 */
function decodeLegacyEscapedInput(PDO $db, ?string $since, string $before, bool $apply): array
{
    $changes = [];
    $samples = [];

    if ($apply) {
        $db->beginTransaction();
    }

    try {
        foreach (LEGACY_ESCAPED_COLUMNS as $table => $columns) {
            $entity = implode(' OR ', array_map(fn($c) => "`{$c}` REGEXP '&(amp|quot|lt|gt|#0?39);'", $columns));
            $where = "created_at < ? AND ({$entity})";
            $params = [$before];
            if ($since !== null) {
                $where = "created_at >= ? AND " . $where;
                array_unshift($params, $since);
            }
            if ($table === 'users') {
                // Hanya akun warga (dibuat dari formulir publik); data personil berasal dari seeder/admin
                $where .= " AND role = 'ROLE_CITIZEN'";
            }

            $select = $db->prepare("SELECT id, `" . implode('`, `', $columns) . "` FROM `{$table}` WHERE {$where}");
            $select->execute($params);

            foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $updates = [];
                foreach ($columns as $column) {
                    if ($row[$column] === null) {
                        continue;
                    }
                    $decoded = htmlspecialchars_decode($row[$column], ENT_QUOTES);
                    if ($decoded !== $row[$column]) {
                        $updates[$column] = $decoded;
                        $changes["{$table}.{$column}"] = ($changes["{$table}.{$column}"] ?? 0) + 1;
                        if (count($samples) < 10) {
                            $samples[] = "{$table}#{$row['id']}.{$column}: " . mb_strimwidth($row[$column], 0, 60, '…') . '  →  ' . mb_strimwidth($decoded, 0, 60, '…');
                        }
                    }
                }

                if ($apply && $updates) {
                    $set = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($updates)));
                    $db->prepare("UPDATE `{$table}` SET {$set} WHERE id = ?")
                        ->execute([...array_values($updates), $row['id']]);
                }
            }
        }

        if ($apply) {
            $db->commit();
        }
    } catch (\Throwable $e) {
        if ($apply && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    return ['changes' => $changes, 'samples' => $samples];
}

// ---- Eksekusi CLI (dilewati bila file di-include oleh test) ----
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) {
    return;
}

$opts = getopt('', ['before:', 'since:', 'apply']);
$isDate = fn($v) => is_string($v) && strtotime($v) !== false;
if (!$isDate($opts['before'] ?? null) || (isset($opts['since']) && !$isDate($opts['since']))) {
    fwrite(STDERR, "Wajib: --before=\"YYYY-mm-dd HH:ii:ss\" (waktu deploy perbaikan escape). Opsional: --since=..., --apply\n");
    exit(2);
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $file = dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

$apply = isset($opts['apply']);
$result = decodeLegacyEscapedInput(\App\Config\Database::getConnection(), $opts['since'] ?? null, $opts['before'], $apply);

echo ($apply ? "[APPLY]" : "[DRY-RUN] tidak ada data yang diubah; tambahkan --apply untuk menerapkan") . PHP_EOL;
if (!$result['changes']) {
    echo "Tidak ditemukan data ter-escape pada rentang waktu tersebut." . PHP_EOL;
    exit(0);
}
foreach ($result['changes'] as $column => $count) {
    echo "  {$column}: {$count} baris" . PHP_EOL;
}
echo "Contoh:" . PHP_EOL . '  ' . implode(PHP_EOL . '  ', $result['samples']) . PHP_EOL;
