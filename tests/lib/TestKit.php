<?php
/**
 * SIGAP-POLRI: Test kit minimal tanpa dependensi (assert, HTTP client, fixture DB)
 */

namespace Tests;

use App\Config\Database;
use PDO;

final class AssertionFailed extends \RuntimeException {}

final class T
{
    public static function true(bool $cond, string $msg): void
    {
        if (!$cond) {
            throw new AssertionFailed($msg);
        }
    }

    public static function same(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new AssertionFailed(($msg ? "{$msg}: " : '') . 'diharapkan ' . var_export($expected, true) . ', didapat ' . var_export($actual, true));
        }
    }

    public static function contains(string $needle, string $haystack, string $msg = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new AssertionFailed(($msg ? "{$msg}: " : '') . "tidak ditemukan teks '{$needle}'");
        }
    }

    public static function notContains(string $needle, string $haystack, string $msg = ''): void
    {
        if (str_contains($haystack, $needle)) {
            throw new AssertionFailed(($msg ? "{$msg}: " : '') . "seharusnya tidak ada teks '{$needle}'");
        }
    }
}

final class Response
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function location(): string
    {
        return $this->header('location') ?? '';
    }

    public function csrf(): string
    {
        preg_match('/name="_csrf_token" value="([a-f0-9]+)"/', $this->body, $m);
        return $m[1] ?? '';
    }
}

/**
 * Klien HTTP dengan cookie jar sendiri (satu instance = satu sesi browser)
 */
final class Http
{
    private string $jar;

    public function __construct(private string $baseUrl)
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'sigap_test_');
    }

    public function __destruct()
    {
        @unlink($this->jar);
    }

    public static function as(string $role): self
    {
        $client = new self(TEST_BASE_URL);
        $res = $client->get("/auth/quickLogin/{$role}");
        T::true($res->status === 302 && !str_contains($res->location(), '/auth/login'), "quick login '{$role}' gagal (APP_ENV harus development)");
        return $client;
    }

    public function get(string $path): Response
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, array $fields): Response
    {
        return $this->request('POST', $path, $fields);
    }

    /** Verifikasi PIN tiket via formulir (POST); mengembalikan respons POST (redirect) */
    public function unlock(string $ticket, string $pin): Response
    {
        $csrf = $this->get('/report/track')->csrf();
        return $this->post('/report/track', ['_csrf_token' => $csrf, 'ticket' => $ticket, 'pin' => $pin]);
    }

    /** Verifikasi PIN lalu buka halaman tiket (mengikuti redirect) */
    public function trackWithPin(string $ticket, string $pin): Response
    {
        $this->unlock($ticket, $pin);
        return $this->get('/report/track?ticket=' . urlencode($ticket));
    }

    /** Ambil pesan flash pertama (error lalu success) dari halaman ber-layout publik */
    public function flash(): string
    {
        $body = $this->get('/report/create')->body;
        preg_match_all('/<span>([^<]*)<\/span>\s*<\/div>/', $body, $m);
        return html_entity_decode(implode(' | ', $m[1]));
    }

    private function request(string $method, string $path, array $fields = []): Response
    {
        $headers = [];
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$headers) {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $headers[strtolower(trim($k))] = trim($v);
                }
                return strlen($line);
            },
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }

        $body = curl_exec($ch);
        if ($body === false) {
            throw new \RuntimeException('HTTP gagal: ' . curl_error($ch));
        }
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return new Response($status, $body, $headers);
    }
}

/**
 * Akses DB untuk fixture & pembersihan data uji (judul berawalan ZZTEST_)
 */
final class Db
{
    public const PREFIX = 'ZZTEST_';

    public static function pdo(): PDO
    {
        return Database::getConnection();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function row(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public static function exec(string $sql, array $params = []): void
    {
        self::pdo()->prepare($sql)->execute($params);
    }

    /** Buat laporan via portal publik; kembalikan [klien sesi pelapor, nomor tiket] */
    public static function createReport(array $overrides = [], array $files = []): array
    {
        // Suite membuat banyak laporan dari IP yang sama; batas laporan publik per IP diuji terpisah
        self::exec("DELETE FROM throttle_events WHERE bucket = 'report_ip'");

        $client = new Http(TEST_BASE_URL);
        $fields = array_merge([
            '_csrf_token'       => $client->get('/report/create')->csrf(),
            'reporter_nik'      => '3171000000000001',
            'reporter_name'     => 'Penguji Otomatis',
            'reporter_phone'    => '080000000000',
            'category_id'       => '1',
            'title'             => self::PREFIX . 'laporan',
            'description'       => 'Laporan uji otomatis',
            'incident_location' => 'Lokasi uji',
        ], $overrides);
        foreach (array_values($files) as $i => $file) {
            $fields["proof_files[{$i}]"] = $file;
        }

        $res = $client->post('/report/store', $fields);
        if (!str_contains($res->location(), '/report/success?ticket=')) {
            // flash() hanya dibaca saat gagal agar pesan flash tetap tersedia untuk test
            throw new AssertionFailed('pembuatan laporan gagal: ' . $client->flash());
        }
        parse_str((string)parse_url($res->location(), PHP_URL_QUERY), $q);

        return [$client, $q['ticket']];
    }

    private static ?int $auditMark = null;

    public static function cleanup(): void
    {
        // Jejak audit: tandai posisi di awal suite, hapus entri yang dibuat test di akhir suite
        if (self::$auditMark === null) {
            self::$auditMark = (int)self::value("SELECT COALESCE(MAX(id), 0) FROM audit_logs");
        } else {
            self::exec("DELETE FROM audit_logs WHERE id > ?", [self::$auditMark]);
            self::$auditMark = null;
        }

        $ids = self::pdo()->query("SELECT id FROM reports WHERE title LIKE '" . self::PREFIX . "%'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            $paths = self::pdo()->query(
                "SELECT file_path FROM report_attachments WHERE report_id = {$id}
                 UNION SELECT attachment_path FROM case_messages WHERE report_id = {$id} AND attachment_path IS NOT NULL"
            )->fetchAll(PDO::FETCH_COLUMN);
            foreach ($paths as $path) {
                @unlink(\App\Core\Upload::absolutePath($path));
            }
            foreach (['case_messages', 'report_attachments', 'report_timeline', 'report_feedbacks', 'kapolda_directives'] as $table) {
                self::exec("DELETE FROM {$table} WHERE report_id = ?", [$id]);
            }
            self::exec("DELETE FROM reports WHERE id = ?", [$id]);
        }
        self::exec("DELETE FROM social_feeds WHERE post_id_str LIKE 'zztest_%'");
        // Percakapan Live Chat uji (pesan ikut terhapus via ON DELETE CASCADE)
        self::exec("DELETE FROM chat_conversations WHERE visitor_name LIKE '" . self::PREFIX . "%'");
        // Sequence diinisialisasi ulang dari tiket tertinggi saat laporan berikutnya dibuat
        self::exec("DELETE FROM ticket_sequences");
        self::exec("DELETE FROM throttle_events");
    }
}
