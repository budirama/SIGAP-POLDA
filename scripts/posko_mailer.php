<?php
/**
 * Pengirim antrean email notifikasi posko (P5-06). Jalankan terjadwal, mis. tiap 5 menit:
 *   php scripts/posko_mailer.php [--limit=50]
 *
 * Mengirim baris PENDING yang sudah jatuh tempo lewat SMTP (.env: MAIL_*). Kegagalan satu email dicatat
 * (last_error), dicoba ulang dengan jeda bertambah, dan tidak menghentikan email lain.
 * Bila SMTP belum dikonfigurasi, antrean dibiarkan utuh.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Mailer;
use App\Models\PoskoOutbox;

$limit = 50;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int)substr($arg, 8));
    }
}

if (!Mailer::isConfigured()) {
    echo "SMTP belum dikonfigurasi (MAIL_HOST/MAIL_FROM di .env); antrean tidak diproses.\n";
    exit(0);
}

$outbox = new PoskoOutbox();
$sent = $failed = 0;
foreach ($outbox->due($limit) as $mail) {
    $error = Mailer::send($mail['to_email'], $mail['subject'], $mail['body']);
    if ($error === null) {
        $outbox->markSent((int)$mail['id']);
        $sent++;
    } else {
        $outbox->markFailed((int)$mail['id'], $error);
        $failed++;
        fwrite(STDERR, "Gagal #{$mail['id']} ({$mail['to_email']}): {$error}\n");
    }
}
echo date('Y-m-d H:i:s') . " Email terkirim: {$sent}, gagal (akan dicoba ulang): {$failed}\n";
