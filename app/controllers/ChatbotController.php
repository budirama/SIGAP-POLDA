<?php
/**
 * SIGAP-POLRI: Chatbot Controller (Basis Pengetahuan Asisten Virtual)
 *
 * Operator SPKT, Humas & Superadmin mengelola jawaban chatbot, menguji pertanyaan,
 * dan menindaklanjuti pertanyaan warga yang belum terjawab. Itwasum hanya melihat.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\Chatbot;

class ChatbotController extends Controller
{
    private const ACTION_ROLES = [
        Constants::ROLE_SPKT_OPERATOR,
        Constants::ROLE_CYBER_HUMAS,
        Constants::ROLE_SUPERADMIN,
    ];

    private Chatbot $bot;

    public function __construct()
    {
        Auth::requireRole([...self::ACTION_ROLES, Constants::ROLE_INTERNAL_AFFAIRS]);
        $this->bot = new Chatbot();
    }

    public function index(): void
    {
        $editId = (int)$this->get('edit', 0);
        $old = Session::get('_old_intent');
        Session::remove('_old_intent');

        $this->render('backoffice/chatbot', [
            'pageTitle' => 'Basis Pengetahuan Chatbot',
            'intents'   => $this->bot->all(),
            'misses'    => $this->bot->openMisses(),
            'stats'     => $this->bot->stats(),
            'editing'   => is_array($old) ? $old : ($editId > 0 ? $this->bot->findById($editId) : null),
            'prefill'   => mb_substr((string)$this->get('from_miss', ''), 0, 300),
            'canAct'    => Auth::hasRole(self::ACTION_ROLES),
        ], 'admin');
    }

    /**
     * Uji pertanyaan: intent yang akan dipilih chatbot beserta skornya (JSON)
     */
    public function test(): void
    {
        $question = mb_substr((string)$this->get('q', ''), 0, 500);
        $match = Chatbot::match($question, $this->bot->activeIntents());
        $this->json($match === null
            ? ['matched' => false, 'min_score' => Chatbot::MIN_SCORE]
            : ['matched' => true, 'code' => $match['intent']['code'], 'label' => $match['intent']['label'],
               'score' => $match['score'], 'answer' => $match['intent']['answer']]);
    }

    public function save(): void
    {
        $this->requireAction();
        $id = (int)$this->post('id', 0);
        $existing = $id > 0 ? $this->bot->findById($id) : null;
        if ($id > 0 && !$existing) {
            Session::setFlash('error', 'Intent tidak ditemukan.');
            $this->redirect('/chatbot');
        }

        $known = array_column($this->bot->all(), 'code');
        $codes = array_values(array_unique(array_filter(
            array_map(fn($c) => strtoupper(trim($c)), explode(',', (string)$this->post('quick_replies', ''))),
            fn($c) => $c !== '' && in_array($c, $known, true)
        )));
        $link = trim((string)$this->post('link_url', ''));
        $data = [
            'code'          => strtoupper(trim((string)$this->post('code', ''))),
            'label'         => mb_substr(trim((string)$this->post('label', '')), 0, 60),
            'keywords'      => mb_substr(trim((string)$this->post('keywords', '')), 0, 2000),
            'answer'        => mb_substr(trim((string)$this->post('answer', '')), 0, 2000),
            'link_url'      => $link !== '' ? mb_substr($link, 0, 255) : null,
            'link_label'    => ($l = mb_substr(trim((string)$this->post('link_label', '')), 0, 60)) !== '' ? $l : null,
            'quick_replies' => $codes ? implode(',', $codes) : null,
            'action'        => array_key_exists((string)$this->post('action'), Chatbot::ACTIONS) ? (string)$this->post('action') : 'NONE',
            'show_in_menu'  => (int)(bool)$this->post('show_in_menu', 0),
            'is_active'     => (int)(bool)$this->post('is_active', 0),
            'sort_order'    => max(0, min(9999, (int)$this->post('sort_order', 100))),
        ];

        $error = match (true) {
            !preg_match('/^[A-Z0-9_]{2,40}$/', $data['code']) => 'Kode hanya huruf kapital, angka, dan garis bawah (2–40 karakter).',
            $this->bot->codeTaken($data['code'], $id) => 'Kode intent sudah dipakai.',
            mb_strlen($data['label']) < 2 => 'Teks tombol wajib diisi.',
            trim(str_replace(',', '', $data['keywords'])) === '' => 'Isi minimal satu kata kunci.',
            mb_strlen($data['answer']) < 5 => 'Jawaban wajib diisi.',
            $data['link_url'] !== null && !preg_match('#^(/[^/]|https://|tel:)#i', $data['link_url'])
                => 'Tautan harus berupa path internal (/report/create), alamat https://, atau tel:.',
            default => null,
        };
        if ($error !== null) {
            Session::set('_old_intent', $data + ['id' => $id]);
            Session::setFlash('error', $error);
            $this->redirect('/chatbot#form');
        }

        $savedId = $this->bot->save($id, $data, (int)Auth::id());
        $missId = (int)$this->post('miss_id', 0);
        if ($missId > 0) {
            $this->bot->resolveMiss($missId);
        }
        Audit::log($id > 0 ? 'chatbot.update' : 'chatbot.create', null, "Intent {$data['code']} #{$savedId}");
        Session::setFlash('success', "Jawaban chatbot {$data['code']} tersimpan.");
        $this->redirect('/chatbot');
    }

    public function delete(): void
    {
        $this->requireAction();
        $intent = $this->bot->findById((int)$this->post('id', 0));
        if ($intent && $this->bot->delete((int)$intent['id'])) {
            Audit::log('chatbot.delete', null, "Intent {$intent['code']}");
            Session::setFlash('success', "Intent {$intent['code']} dihapus.");
        } else {
            Session::setFlash('error', 'Intent tidak ditemukan.');
        }
        $this->redirect('/chatbot');
    }

    /**
     * Tandai pertanyaan tak terjawab sudah ditindaklanjuti / diabaikan
     */
    public function resolve(): void
    {
        $this->requireAction();
        $this->bot->resolveMiss((int)$this->post('miss_id', 0));
        $this->redirect('/chatbot#misses');
    }

    private function requireAction(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/chatbot');
        }
        if (!Auth::hasRole(self::ACTION_ROLES)) {
            Session::setFlash('error', 'Akses Ditolak: peran pengawasan hanya dapat melihat.');
            $this->redirect('/chatbot');
        }
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/chatbot');
        }
    }
}
