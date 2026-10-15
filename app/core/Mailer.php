<?php
/**
 * SIGAP-POLRI: Pengirim email SMTP minimal tanpa dependensi (dipakai skrip scripts/posko_mailer.php)
 *
 * Konfigurasi dari .env: MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
 * MAIL_ENCRYPTION (tls = STARTTLS, ssl = TLS langsung, none), MAIL_FROM, MAIL_FROM_NAME.
 */

namespace App\Core;

use App\Config\Config;

class Mailer
{
    public static function isConfigured(): bool
    {
        return Config::env('MAIL_HOST') !== '' && Config::env('MAIL_FROM') !== '';
    }

    /**
     * Kirim satu email teks. Kembalikan null bila sukses, atau pesan kesalahan singkat.
     */
    public static function send(string $to, string $subject, string $body): ?string
    {
        if (!self::isConfigured()) {
            return 'SMTP belum dikonfigurasi (MAIL_HOST/MAIL_FROM di .env).';
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return 'Alamat tujuan tidak valid.';
        }

        $host = Config::env('MAIL_HOST');
        $port = (int)(Config::env('MAIL_PORT', '587') ?: 587);
        $enc = strtolower(Config::env('MAIL_ENCRYPTION', 'tls'));
        $from = Config::env('MAIL_FROM');
        $fromName = Config::env('MAIL_FROM_NAME', 'SIGAP Posko');

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(($enc === 'ssl' ? 'ssl://' : 'tcp://') . "{$host}:{$port}", $errno, $errstr, 15);
        if (!$socket) {
            return "Koneksi SMTP gagal: {$errstr}";
        }
        stream_set_timeout($socket, 15);

        try {
            self::expect($socket, [220]);
            self::cmd($socket, 'EHLO sigap.local', [250]);
            if ($enc === 'tls') {
                self::cmd($socket, 'STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Negosiasi TLS gagal.');
                }
                self::cmd($socket, 'EHLO sigap.local', [250]);
            }
            $user = Config::env('MAIL_USERNAME');
            if ($user !== '') {
                self::cmd($socket, 'AUTH LOGIN', [334]);
                self::cmd($socket, base64_encode($user), [334]);
                self::cmd($socket, base64_encode(Config::env('MAIL_PASSWORD')), [235]);
            }
            self::cmd($socket, "MAIL FROM:<{$from}>", [250]);
            self::cmd($socket, "RCPT TO:<{$to}>", [250, 251]);
            self::cmd($socket, 'DATA', [354]);

            $headers = [
                'From: ' . self::encodeHeader($fromName) . " <{$from}>",
                "To: <{$to}>",
                'Subject: ' . self::encodeHeader($subject),
                'Date: ' . date('r'),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ];
            $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
            fwrite($socket, $message . "\r\n.\r\n");
            self::expect($socket, [250]);
            self::cmd($socket, 'QUIT', [221]);
            return null;
        } catch (\Throwable $e) {
            return mb_substr($e->getMessage(), 0, 250);
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    private static function encodeHeader(string $text): string
    {
        return preg_match('/[^\x20-\x7E]/', $text) ? '=?UTF-8?B?' . base64_encode($text) . '?=' : $text;
    }

    private static function cmd($socket, string $command, array $okCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $okCodes);
    }

    /** Baca balasan (termasuk baris lanjutan "250-") dan pastikan kodenya termasuk yang diharapkan */
    private static function expect($socket, array $okCodes): string
    {
        $reply = '';
        while (($line = fgets($socket, 515)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int)substr($reply, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            throw new \RuntimeException('SMTP: ' . trim(preg_replace('/\s+/', ' ', $reply)));
        }
        return $reply;
    }
}
