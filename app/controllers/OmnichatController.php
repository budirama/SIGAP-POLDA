<?php
/**
 * SIGAP-POLRI: Omnichat Controller (Konsol agen Command Center untuk kanal chat warga)
 *
 * Operator SPKT, Humas & Superadmin melayani chat; Itwasum memantau (hanya lihat).
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Config\Constants;
use App\Models\Chat;
use App\Models\Report;

class OmnichatController extends Controller
{
    private const ACTION_ROLES = [
        Constants::ROLE_SPKT_OPERATOR,
        Constants::ROLE_CYBER_HUMAS,
        Constants::ROLE_SUPERADMIN,
    ];

    private Chat $chat;

    public function __construct()
    {
        Auth::requireRole(array_merge(self::ACTION_ROLES, [Constants::ROLE_INTERNAL_AFFAIRS]));
        $this->chat = new Chat();
    }

    /**
     * Halaman konsol Omnichat
     */
    public function index(): void
    {
        $this->render('backoffice/omnichat', [
            'pageTitle'  => 'Omnichat Command Center',
            'canAct'     => $this->canAct(),
            'categories' => (new Report())->getCategories(),
        ], 'admin');
    }

    /**
     * Daftar percakapan (?scope=open|WAITING|ACTIVE|CLOSED|mine)
     */
    public function conversations(): void
    {
        $scope = (string)$this->get('scope', 'open');
        $rows = $this->chat->listForConsole($scope, (int)Auth::id());

        $this->json([
            'waiting'       => $this->chat->countWaiting(),
            'conversations' => array_map(fn($c) => [
                'id'          => (int)$c['id'],
                'channel'     => $c['channel'],
                'visitor'     => $c['visitor_name'],
                'phone'       => $c['visitor_phone'],
                'status'      => $c['status'],
                'agent'       => $c['agent_name'] ? trim(($c['agent_rank'] ?? '') . ' ' . $c['agent_name']) : null,
                'mine'        => (int)$c['assigned_agent_id'] === Auth::id(),
                'ticket'      => $c['report_ticket'],
                'last_body'   => mb_substr((string)$c['last_body'], 0, 120),
                'last_sender' => strtolower((string)$c['last_sender']),
                'unanswered'  => (int)$c['unanswered'],
                'time'        => $c['last_message_at'] ? date('d/m H:i', strtotime($c['last_message_at'])) : '',
            ], $rows),
        ]);
    }

    /**
     * Detail percakapan + pesan baru (?after=ID)
     */
    public function messages(int $id): void
    {
        $conv = $this->findOr404($id);
        $messages = $this->chat->messagesAfter($id, max(0, (int)$this->get('after', 0)));

        $this->json([
            'conversation' => $this->present($conv),
            'messages'     => array_map(fn($m) => [
                'id'   => (int)$m['id'],
                'from' => strtolower($m['sender_type']),
                'name' => $m['sender_type'] === 'AGENT' ? trim(($m['sender_rank'] ?? '') . ' ' . ($m['sender_name'] ?? '')) : null,
                'body' => $m['body'],
                'time' => date('d/m H:i', strtotime($m['created_at'])),
            ], $messages),
        ]);
    }

    /**
     * Ambil alih percakapan yang menunggu
     */
    public function claim(): void
    {
        $conv = $this->actionConversation();
        if ($conv['status'] !== 'WAITING') {
            $this->json(['error' => 'Percakapan sudah ditangani petugas lain atau ditutup.'], 409);
        }
        if (!$this->chat->claim((int)$conv['id'], (int)Auth::id())) {
            $this->json(['error' => 'Percakapan baru saja diambil petugas lain.'], 409);
        }

        $this->chat->addMessage((int)$conv['id'], 'SYSTEM', null, $this->agentLabel() . ' bergabung dalam percakapan.');
        Audit::log('chat.claim', null, "Percakapan #{$conv['id']}");
        $this->json(['ok' => true]);
    }

    /**
     * Balas warga (percakapan menunggu otomatis diambil oleh pengirim)
     */
    public function send(): void
    {
        $conv = $this->actionConversation();
        $body = mb_substr(trim((string)$this->post('message', '')), 0, Chat::MAX_BODY);
        if ($body === '') {
            $this->json(['error' => 'Pesan tidak boleh kosong.'], 422);
        }
        if ($conv['status'] === 'CLOSED') {
            $this->json(['error' => 'Percakapan sudah ditutup.'], 409);
        }

        if ($conv['status'] === 'WAITING') {
            if (!$this->chat->claim((int)$conv['id'], (int)Auth::id())) {
                $this->json(['error' => 'Percakapan baru saja diambil petugas lain.'], 409);
            }
            $this->chat->addMessage((int)$conv['id'], 'SYSTEM', null, $this->agentLabel() . ' bergabung dalam percakapan.');
            Audit::log('chat.claim', null, "Percakapan #{$conv['id']}");
        } elseif (!$this->isHandler($conv)) {
            $this->json(['error' => 'Percakapan ini ditangani petugas lain.'], 403);
        }

        $this->chat->addMessage((int)$conv['id'], 'AGENT', (int)Auth::id(), $body);
        $this->json(['ok' => true]);
    }

    public function close(): void
    {
        $conv = $this->actionConversation();
        if (!$this->isHandler($conv)) {
            $this->json(['error' => 'Hanya petugas penangan yang dapat menutup percakapan.'], 403);
        }
        if ($this->chat->close((int)$conv['id'], 'AGENT')) {
            $this->chat->addMessage((int)$conv['id'], 'SYSTEM', null, 'Percakapan ditutup oleh petugas. Terima kasih telah menghubungi Polri.');
            Audit::log('chat.close', $conv['report_id'] ? (int)$conv['report_id'] : null, "Percakapan #{$conv['id']}");
        }
        $this->json(['ok' => true]);
    }

    /**
     * Konversi percakapan menjadi tiket laporan resmi (kanal LIVE_CHAT)
     */
    public function convert(): void
    {
        $conv = $this->actionConversation();
        if (!$this->isHandler($conv)) {
            $this->json(['error' => 'Ambil alih percakapan terlebih dahulu sebelum membuat tiket.'], 403);
        }
        if (!empty($conv['report_id'])) {
            $this->json(['error' => "Percakapan ini sudah menjadi tiket {$conv['report_ticket']}."], 409);
        }

        $reportModel = new Report();
        $categoryId = (int)$this->post('category_id', 0);
        $validCategories = array_map('intval', array_column($reportModel->getCategories(), 'id'));
        $title = mb_substr(trim((string)$this->post('title', '')), 0, 200);
        $location = mb_substr(trim((string)$this->post('incident_location', '')), 0, 255);

        if (!in_array($categoryId, $validCategories, true) || $title === '' || $location === '') {
            $this->json(['error' => 'Kategori, judul, dan lokasi kejadian wajib diisi.'], 422);
        }

        $transcript = $this->chat->visitorTranscript((int)$conv['id']);
        try {
            $ticket = $reportModel->createReport([
                'source_channel'    => Constants::CHANNEL_LIVECHAT,
                'actor_id'          => Auth::id(),
                'reporter_id'       => null,
                'is_anonymous'      => 0,
                'reporter_name_raw' => mb_substr($conv['visitor_name'], 0, 150),
                'reporter_nik_raw'  => null,
                'reporter_phone'    => $conv['visitor_phone'] ?: 'Live Chat',
                'category_id'       => $categoryId,
                'title'             => $title,
                'description'       => "Kronologi dari Live Chat #{$conv['id']}:\n" . $transcript,
                'incident_date'     => date('Y-m-d'),
                'incident_time'     => date('H:i:s'),
                'incident_location' => $location,
                'urgency_level'     => 'NORMAL',
            ]);
        } catch (\Throwable $e) {
            error_log('[SIGAP-POLRI] Gagal konversi live chat: ' . $e);
            $this->json(['error' => 'Gagal membuat tiket laporan. Silakan coba lagi.'], 500);
        }

        $report = $reportModel->findByTicket($ticket);
        $this->chat->attachReport((int)$conv['id'], (int)$report['id']);
        // PIN tidak ditulis di chat (agar tidak terlihat petugas); warga membukanya lewat tombol di widget
        $this->chat->addMessage((int)$conv['id'], 'SYSTEM', null,
            "Laporan Anda telah didaftarkan dengan nomor tiket {$ticket}. Tekan tombol \"Lihat Tiket & PIN\" untuk menyimpan PIN pelacakan.");
        Audit::log('chat.convert', (int)$report['id'], "Percakapan #{$conv['id']} → {$ticket}");

        $this->json(['ok' => true, 'ticket' => $ticket]);
    }

    private function present(array $conv): array
    {
        return [
            'id'      => (int)$conv['id'],
            'channel' => $conv['channel'],
            'visitor' => $conv['visitor_name'],
            'phone'   => $conv['visitor_phone'],
            'status'  => $conv['status'],
            'agent'   => $conv['agent_name'] ? trim(($conv['agent_rank'] ?? '') . ' ' . $conv['agent_name']) : null,
            'mine'    => $this->isHandler($conv),
            'ticket'  => $conv['report_ticket'],
            'started' => date('d/m/Y H:i', strtotime($conv['created_at'])),
        ];
    }

    /**
     * POST + CSRF + role penindak, lalu muat percakapan dari conversation_id
     */
    private function actionConversation(): array
    {
        if (!$this->isPost()) {
            $this->json(['error' => 'Metode tidak diizinkan.'], 405);
        }
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Sesi keamanan berakhir. Muat ulang halaman.'], 403);
        }
        if (!$this->canAct()) {
            $this->json(['error' => 'Peran pengawasan hanya dapat memantau percakapan.'], 403);
        }
        return $this->findOr404((int)$this->post('conversation_id', 0));
    }

    private function findOr404(int $id): array
    {
        $conv = $id > 0 ? $this->chat->findById($id) : null;
        if (!$conv) {
            $this->json(['error' => 'Percakapan tidak ditemukan.'], 404);
        }
        return $conv;
    }

    private function isHandler(array $conv): bool
    {
        return (int)$conv['assigned_agent_id'] === Auth::id() || Auth::role() === Constants::ROLE_SUPERADMIN;
    }

    private function canAct(): bool
    {
        return Auth::hasRole(self::ACTION_ROLES);
    }

    private function agentLabel(): string
    {
        $user = Auth::user();
        return trim(($user['rank'] ?? '') . ' ' . ($user['name'] ?? 'Petugas'));
    }
}
