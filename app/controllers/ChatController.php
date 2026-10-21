<?php
/**
 * SIGAP-POLRI: Chat Controller (Kanal Live Chat warga — endpoint JSON widget portal publik)
 *
 * Kepemilikan percakapan terikat pada sesi warga (id + token acak), tanpa perlu login.
 * Bila modul Chatbot aktif, percakapan baru dilayani Asisten Virtual hingga diteruskan ke petugas.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ModuleManager;
use App\Core\ReportAccess;
use App\Core\Session;
use App\Models\Chat;
use App\Models\Chatbot;
use App\Models\Throttle;

class ChatController extends Controller
{
    private const SESSION_KEY = 'livechat';

    private Chat $chat;
    private Chatbot $bot;
    private Throttle $throttle;

    public function __construct()
    {
        $this->chat = new Chat();
        $this->bot = new Chatbot();
        $this->throttle = new Throttle();
    }

    /**
     * Mulai percakapan: nama, no. HP (opsional), pesan pertama (opsional bila chatbot aktif)
     */
    public function start(): void
    {
        $this->requireJsonPost();

        $name = mb_substr(trim((string)$this->post('name', '')), 0, 100);
        // Hanya spasi/titik/strip yang dibuang; huruf atau simbol lain membuat nomor tidak valid
        $phone = preg_replace('/[\s.\-]/', '', (string)$this->post('phone', ''));
        $message = $this->cleanBody($this->post('message', ''));

        $withBot = self::botEnabled();
        if ($name === '' || ($message === '' && !$withBot)) {
            $this->json(['error' => 'Nama dan pesan wajib diisi.'], 422);
        }
        if ($phone !== '' && !preg_match('/^\+?[0-9]{8,15}$/', $phone)) {
            $this->json(['error' => 'Nomor HP tidak valid (8–15 digit).'], 422);
        }

        $ip = Throttle::clientIp();
        if ($this->throttle->tooMany('chat_start_ip', $ip)) {
            $this->json(['error' => 'Terlalu banyak sesi chat dari jaringan Anda. Coba lagi nanti atau gunakan formulir laporan.'], 429);
        }
        $this->throttle->hit('chat_start_ip', $ip);

        $conv = $this->chat->start($name, $phone !== '' ? $phone : null, $message, $withBot);
        Session::set(self::SESSION_KEY, $conv);
        if ($withBot) {
            $this->bot->greet((int)$conv['id']);
            if ($message !== '') {
                $this->bot->respond((int)$conv['id'], $message);
            }
        }

        $this->json(['ok' => true] + $this->state($conv['id'], 0));
    }

    /**
     * Polling pesan baru (?after=ID pesan terakhir yang sudah diterima)
     */
    public function poll(): void
    {
        $conv = $this->ownConversation();
        if (!$conv) {
            $this->json(['active' => false]);
        }
        $this->json($this->state((int)$conv['id'], (int)$this->get('after', 0)));
    }

    public function send(): void
    {
        $this->requireJsonPost();
        $conv = $this->ownConversation();
        if (!$conv) {
            $this->json(['error' => 'Sesi chat tidak ditemukan. Silakan mulai percakapan baru.'], 404);
        }
        if ($conv['status'] === 'CLOSED') {
            $this->json(['error' => 'Percakapan sudah ditutup.'], 409);
        }

        $message = $this->cleanBody($this->post('message', ''));
        if ($message === '') {
            $this->json(['error' => 'Pesan tidak boleh kosong.'], 422);
        }
        // Tombol cepat chatbot mengirim kode intent bersama label tombol sebagai pesan warga
        $intent = strtoupper((string)$this->post('intent', ''));
        $intent = preg_match('/^[A-Z0-9_]{2,40}$/', $intent) ? $intent : null;

        $ip = Throttle::clientIp();
        if ($this->throttle->tooMany('chat_msg_ip', $ip)) {
            $this->json(['error' => 'Anda mengirim pesan terlalu cepat. Tunggu sebentar.'], 429);
        }
        $this->throttle->hit('chat_msg_ip', $ip);

        $this->chat->addMessage((int)$conv['id'], 'VISITOR', null, $message);
        if ($conv['status'] === 'BOT') {
            $this->bot->respond((int)$conv['id'], $message, $intent);
        } elseif ($conv['status'] === 'WAITING') {
            $this->bot->flagUrgentIfEmergency((int)$conv['id'], $message);
        }
        $this->json(['ok' => true] + $this->state((int)$conv['id'], (int)$this->post('after', 0)));
    }

    /**
     * Warga meminta dihubungkan dengan petugas (tombol tetap di widget selama dilayani chatbot)
     */
    public function handoff(): void
    {
        $this->requireJsonPost();
        $conv = $this->ownConversation();
        if (!$conv) {
            $this->json(['error' => 'Sesi chat tidak ditemukan. Silakan mulai percakapan baru.'], 404);
        }
        $this->bot->handoff((int)$conv['id'], false);
        $this->json(['ok' => true] + $this->state((int)$conv['id'], (int)$this->post('after', 0)));
    }

    /**
     * Warga mengakhiri percakapan (sesi chat dilepas dari browser)
     */
    public function end(): void
    {
        $this->requireJsonPost();
        $conv = $this->ownConversation();
        if ($conv && $this->chat->close((int)$conv['id'], 'VISITOR')) {
            $this->chat->addMessage((int)$conv['id'], 'SYSTEM', null, 'Warga mengakhiri percakapan.');
        }
        Session::remove(self::SESSION_KEY);
        $this->json(['ok' => true, 'active' => false]);
    }

    /**
     * Buka tiket hasil konversi chat: sesi pemilik chat diberi akses pelapor (halaman tiket + PIN)
     */
    public function ticket(): void
    {
        $this->requireJsonPost();
        $conv = $this->ownConversation();
        if (!$conv || empty($conv['report_ticket'])) {
            $this->json(['error' => 'Belum ada tiket laporan dari percakapan ini.'], 404);
        }

        ReportAccess::grantReporter($conv['report_ticket']);
        $this->json(['ok' => true, 'url' => '/report/success?ticket=' . rawurlencode($conv['report_ticket'])]);
    }

    private function state(int $conversationId, int $afterId): array
    {
        $conv = $this->chat->findById($conversationId);
        $messages = array_map(fn($m) => [
            'id'   => (int)$m['id'],
            'from' => strtolower($m['sender_type']),
            'name' => match ($m['sender_type']) {
                'AGENT' => trim(($m['sender_rank'] ?? '') . ' ' . ($m['sender_name'] ?? 'Petugas')),
                'BOT'   => Chatbot::BOT_NAME,
                default => null,
            },
            'body' => $m['body'],
            'meta' => $m['sender_type'] === 'BOT' && $m['meta'] ? json_decode($m['meta'], true) : null,
            'time' => date('H:i', strtotime($m['created_at'])),
        ], $this->chat->messagesAfter($conversationId, max(0, $afterId)));

        return [
            'active'   => true,
            'status'   => $conv['status'],
            'agent'    => $conv['agent_name'] ? trim(($conv['agent_rank'] ?? '') . ' ' . $conv['agent_name']) : null,
            'ticket'   => $conv['report_ticket'],
            'messages' => $messages,
        ];
    }

    /**
     * Chatbot melayani percakapan baru kecuali modulnya dinonaktifkan di Manajemen Modul
     */
    private static function botEnabled(): bool
    {
        return !class_exists(ModuleManager::class) || ModuleManager::isActive('op_chatbot');
    }

    private function ownConversation(): ?array
    {
        $own = Session::get(self::SESSION_KEY);
        if (!is_array($own) || empty($own['id']) || empty($own['token'])) {
            return null;
        }
        return $this->chat->findForVisitor((int)$own['id'], (string)$own['token']);
    }

    private function cleanBody(mixed $value): string
    {
        return mb_substr(trim((string)$value), 0, Chat::MAX_BODY);
    }

    private function requireJsonPost(): void
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Metode tidak diizinkan.'], 405);
        }
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Sesi keamanan berakhir. Muat ulang halaman.'], 403);
        }
    }
}
