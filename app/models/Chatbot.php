<?php
/**
 * SIGAP-POLRI: Chatbot Konsultasi (Asisten Virtual SIGAP)
 *
 * Menjawab pertanyaan warga di Live Chat berdasarkan basis pengetahuan (intent + kata kunci)
 * yang dikelola petugas. Berbasis aturan: tidak ada data percakapan yang dikirim ke layanan luar.
 * Percakapan diteruskan ke petugas bila diminta, bila terdeteksi keadaan darurat, atau
 * (atas tawaran bot) setelah beberapa pertanyaan tidak dipahami.
 */

namespace App\Models;

use App\Core\Model;

class Chatbot extends Model
{
    public const BOT_NAME = 'Asisten Virtual SIGAP';

    /** Skor minimal agar sebuah intent dianggap cocok */
    public const MIN_SCORE = 2;

    /** Setelah sekian pertanyaan tidak dipahami, bot menawarkan petugas */
    public const MISSES_BEFORE_OFFER = 2;

    public const ACTIONS = [
        'NONE'      => 'Jawab saja',
        'HANDOFF'   => 'Teruskan ke petugas',
        'EMERGENCY' => 'Darurat: teruskan ke petugas sebagai prioritas',
    ];

    private const TICKET_PATTERNS = [
        '/\bLP-POLRI-\d{6}-\d{3,6}\b/i' => '/report/track?ticket=',
        '/\bHT-\d{6}-\d{5}\b/i'         => '/hilangtemu/status?code=',
    ];

    private ?array $intentCache = null;

    // ------------------------------------------------------------------
    // Percakapan
    // ------------------------------------------------------------------

    /**
     * Salam pembuka beserta menu topik
     */
    public function greet(int $conversationId): void
    {
        $this->botMessage($conversationId,
            'Halo, saya ' . self::BOT_NAME . '. Saya dapat membantu informasi layanan Polri 24 jam. '
            . 'Pilih topik di bawah atau tuliskan pertanyaan Anda. Untuk keadaan darurat, segera hubungi 110.',
            ['quick_replies' => $this->menuReplies()]
        );
    }

    /**
     * Tanggapi pesan warga selama percakapan dilayani chatbot.
     * $intentCode diisi bila warga menekan tombol cepat.
     *
     * @return string Hasil: ANSWERED, HANDOFF, EMERGENCY, TICKET, atau MISS
     */
    public function respond(int $conversationId, string $text, ?string $intentCode = null): string
    {
        $intent = $intentCode !== null ? $this->findActiveByCode($intentCode) : null;

        if ($intent === null) {
            foreach (self::TICKET_PATTERNS as $pattern => $path) {
                if (preg_match($pattern, $text, $m)) {
                    $this->botMessage($conversationId,
                        'Untuk keamanan data, status laporan ' . strtoupper($m[0]) . ' hanya dapat dibuka dengan PIN 6 digit milik pelapor. '
                        . 'Silakan buka halaman pelacakan berikut dan masukkan PIN Anda.',
                        ['link' => ['url' => $path . rawurlencode(strtoupper($m[0])), 'label' => 'Buka halaman status'],
                         'quick_replies' => $this->replies(['PETUGAS'])]
                    );
                    return 'TICKET';
                }
            }
            $intent = self::match($text, $this->activeIntents())['intent'] ?? null;
        }

        if ($intent === null) {
            return $this->miss($conversationId, $text);
        }

        $this->execute("UPDATE chatbot_intents SET hit_count = hit_count + 1 WHERE id = ?", [(int)$intent['id']]);

        $meta = ['quick_replies' => $this->replies(array_filter(array_map('trim', explode(',', (string)$intent['quick_replies']))))];
        if (!empty($intent['link_url'])) {
            $meta['link'] = ['url' => $intent['link_url'], 'label' => $intent['link_label'] ?: 'Buka tautan'];
        }
        if ($intent['action'] !== 'NONE') {
            $meta['quick_replies'] = []; // setelah diteruskan, petugas yang melanjutkan percakapan
        }
        $this->botMessage($conversationId, $intent['answer'], $meta);

        if ($intent['action'] !== 'NONE') {
            $this->handoff($conversationId, $intent['action'] === 'EMERGENCY');
            return $intent['action'];
        }
        return 'ANSWERED';
    }

    /**
     * Serahkan percakapan dari chatbot ke antrean petugas (sekali saja)
     */
    public function handoff(int $conversationId, bool $urgent): bool
    {
        $done = $this->affected(
            "UPDATE chat_conversations SET status = 'WAITING', handoff_at = NOW(), is_urgent = GREATEST(is_urgent, ?)
             WHERE id = ? AND status = 'BOT'",
            [$urgent ? 1 : 0, $conversationId]
        ) === 1;

        if ($done) {
            (new Chat())->addMessage($conversationId, 'SYSTEM', null, $urgent
                ? 'PRIORITAS: percakapan diteruskan ke petugas Command Center.'
                : 'Percakapan diteruskan ke petugas Command Center. Mohon tunggu.');
        }
        return $done;
    }

    /**
     * Pesan darurat saat menunggu petugas: tandai percakapan sebagai prioritas di Omnichat
     */
    public function flagUrgentIfEmergency(int $conversationId, string $text): bool
    {
        $emergency = array_values(array_filter($this->activeIntents(), fn($i) => $i['action'] === 'EMERGENCY'));
        if (!$emergency || self::match($text, $emergency) === null) {
            return false;
        }
        return $this->affected(
            "UPDATE chat_conversations SET is_urgent = 1 WHERE id = ? AND is_urgent = 0 AND status <> 'CLOSED'",
            [$conversationId]
        ) === 1;
    }

    private function miss(int $conversationId, string $text): string
    {
        $this->execute("UPDATE chat_conversations SET bot_misses = bot_misses + 1 WHERE id = ?", [$conversationId]);
        $this->execute(
            "INSERT INTO chatbot_misses (conversation_id, question) VALUES (?, ?)",
            [$conversationId, mb_substr($text, 0, 1000)]
        );
        $misses = (int)($this->fetch("SELECT bot_misses FROM chat_conversations WHERE id = ?", [$conversationId])['bot_misses'] ?? 1);

        if ($misses >= self::MISSES_BEFORE_OFFER) {
            $this->botMessage($conversationId,
                'Maaf, saya belum dapat menjawab pertanyaan tersebut. Apakah Anda ingin dihubungkan dengan petugas Command Center?',
                ['quick_replies' => $this->replies(['PETUGAS', 'CARA_LAPOR', 'CEK_STATUS'])]
            );
        } else {
            $this->botMessage($conversationId,
                'Maaf, saya belum memahami pertanyaan Anda. Coba gunakan kata lain atau pilih salah satu topik berikut.',
                ['quick_replies' => $this->menuReplies()]
            );
        }
        return 'MISS';
    }

    private function botMessage(int $conversationId, string $body, array $meta): void
    {
        $meta = array_filter($meta, fn($v) => $v !== [] && $v !== null);
        $this->execute(
            "INSERT INTO chat_messages (conversation_id, sender_type, body, meta) VALUES (?, 'BOT', ?, ?)",
            [$conversationId, $body, $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null]
        );
        $this->execute("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?", [$conversationId]);
    }

    // ------------------------------------------------------------------
    // Pencocokan
    // ------------------------------------------------------------------

    /**
     * Intent dengan skor tertinggi untuk sebuah pertanyaan, atau null bila tidak ada yang cukup cocok.
     * Frasa kunci utuh (≥2 kata) berbobot 3 per kata, kata kunci tunggal 2, awalan kata (≥4 huruf,
     * mis. "melaporkan" untuk "lapor") 1. Seri dipecahkan oleh sort_order terkecil.
     *
     * @return array{intent: array, score: int}|null
     */
    public static function match(string $text, array $intents): ?array
    {
        $normalized = self::normalize($text);
        if (trim($normalized) === '') {
            return null;
        }
        $tokens = array_filter(explode(' ', trim($normalized)));

        $best = null;
        foreach ($intents as $intent) {
            $score = 0;
            foreach (explode(',', (string)$intent['keywords']) as $keyword) {
                $kw = trim(self::normalize($keyword));
                if ($kw === '') {
                    continue;
                }
                if (str_contains($kw, ' ')) {
                    if (str_contains($normalized, ' ' . $kw . ' ')) {
                        $score += 3 * count(explode(' ', $kw));
                    }
                    continue;
                }
                if (in_array($kw, $tokens, true)) {
                    $score += 2;
                } elseif (mb_strlen($kw) >= 4) {
                    foreach ($tokens as $token) {
                        if (str_starts_with($token, $kw) || (mb_strlen($token) >= 5 && str_ends_with($token, $kw))) {
                            $score += 1;
                            break;
                        }
                    }
                }
            }

            if ($score >= self::MIN_SCORE && ($best === null || $score > $best['score']
                || ($score === $best['score'] && (int)$intent['sort_order'] < (int)$best['intent']['sort_order']))) {
                $best = ['intent' => $intent, 'score' => $score];
            }
        }
        return $best;
    }

    /** Huruf kecil, tanda baca jadi spasi, diapit spasi agar pencocokan frasa per kata utuh */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        return ' ' . trim(preg_replace('/\s+/', ' ', $text)) . ' ';
    }

    // ------------------------------------------------------------------
    // Basis pengetahuan
    // ------------------------------------------------------------------

    public function activeIntents(): array
    {
        return $this->intentCache ??= $this->fetchAll(
            "SELECT * FROM chatbot_intents WHERE is_active = 1 ORDER BY sort_order, id"
        );
    }

    public function findActiveByCode(string $code): ?array
    {
        foreach ($this->activeIntents() as $intent) {
            if ($intent['code'] === strtoupper($code)) {
                return $intent;
            }
        }
        return null;
    }

    /** Tombol cepat dari kode intent aktif (urutan dipertahankan, yang tidak aktif dilewati) */
    private function replies(array $codes): array
    {
        $out = [];
        foreach ($codes as $code) {
            $intent = $this->findActiveByCode($code);
            if ($intent) {
                $out[] = ['intent' => $intent['code'], 'label' => $intent['label']];
            }
        }
        return $out;
    }

    private function menuReplies(): array
    {
        return $this->replies(array_column(array_filter($this->activeIntents(), fn($i) => (int)$i['show_in_menu'] === 1), 'code'));
    }

    public function all(): array
    {
        return $this->fetchAll(
            "SELECT i.*, u.name AS updated_by_name FROM chatbot_intents i
             LEFT JOIN users u ON u.id = i.updated_by
             ORDER BY i.sort_order, i.id"
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetch("SELECT * FROM chatbot_intents WHERE id = ?", [$id]);
    }

    public function codeTaken(string $code, int $exceptId = 0): bool
    {
        return $this->fetch("SELECT id FROM chatbot_intents WHERE code = ? AND id <> ?", [$code, $exceptId]) !== null;
    }

    /**
     * Simpan intent baru ($id = 0) atau ubah yang ada
     */
    public function save(int $id, array $d, int $userId): int
    {
        $params = [$d['code'], $d['label'], $d['keywords'], $d['answer'], $d['link_url'], $d['link_label'],
                   $d['quick_replies'], $d['action'], $d['show_in_menu'], $d['is_active'], $d['sort_order'], $userId];
        if ($id > 0) {
            $this->execute(
                "UPDATE chatbot_intents SET code = ?, label = ?, keywords = ?, answer = ?, link_url = ?, link_label = ?,
                        quick_replies = ?, action = ?, show_in_menu = ?, is_active = ?, sort_order = ?, updated_by = ?
                 WHERE id = ?",
                [...$params, $id]
            );
            return $id;
        }
        $this->execute(
            "INSERT INTO chatbot_intents (code, label, keywords, answer, link_url, link_label, quick_replies, action,
                                          show_in_menu, is_active, sort_order, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            $params
        );
        return $this->lastInsertId();
    }

    public function delete(int $id): bool
    {
        return $this->affected("DELETE FROM chatbot_intents WHERE id = ?", [$id]) === 1;
    }

    public function openMisses(int $limit = 50): array
    {
        return $this->fetchAll(
            "SELECT * FROM chatbot_misses WHERE resolved = 0 ORDER BY id DESC LIMIT " . max(1, $limit)
        );
    }

    public function resolveMiss(int $id): bool
    {
        return $this->affected("UPDATE chatbot_misses SET resolved = 1 WHERE id = ? AND resolved = 0", [$id]) === 1;
    }

    /**
     * Ringkasan 30 hari: percakapan dilayani bot, diselesaikan bot tanpa petugas, diteruskan, darurat
     */
    public function stats(): array
    {
        return $this->fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(handoff_at IS NULL AND status IN ('BOT', 'CLOSED') AND assigned_agent_id IS NULL), 0) AS bot_only,
                    COALESCE(SUM(handoff_at IS NOT NULL), 0) AS handoff,
                    COALESCE(SUM(is_urgent = 1), 0) AS urgent
             FROM chat_conversations
             WHERE created_at > NOW() - INTERVAL 30 DAY
               AND id IN (SELECT conversation_id FROM chat_messages WHERE sender_type = 'BOT')"
        ) ?? ['total' => 0, 'bot_only' => 0, 'handoff' => 0, 'urgent' => 0];
    }
}
