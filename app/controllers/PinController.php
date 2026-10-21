<?php
/**
 * SIGAP-POLRI: Layanan PIN Pelapor (Operator SPKT & Superadmin)
 *
 * Untuk warga yang kehilangan PIN selama gateway SMS/WhatsApp belum terhubung. Petugas wajib
 * memverifikasi identitas pelapor & menuliskan alasan; setiap lihat/reset tercatat di Jejak Audit
 * dan dibatasi jumlahnya per petugas. PIN ditampilkan langsung di respons, tidak disimpan di sesi.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\PinDesk;
use App\Models\Throttle;

class PinController extends Controller
{
    private PinDesk $desk;
    private Throttle $throttle;

    public function __construct()
    {
        Auth::requireRole([Constants::ROLE_SPKT_OPERATOR, Constants::ROLE_SUPERADMIN]);
        $this->desk = new PinDesk();
        $this->throttle = new Throttle();
    }

    /**
     * Cari tiket (?ticket=) untuk verifikasi identitas pelapor
     */
    public function index(): void
    {
        $ticket = strtoupper(trim((string)$this->get('ticket', '')));
        $this->show($ticket, $ticket !== '' ? $this->desk->find($ticket) : null);
    }

    public function reveal(): void
    {
        [$ticket, $item, $reason] = $this->guardedRequest();
        if (!$item['can_reveal']) {
            $this->show($ticket, $item, error: 'PIN tiket jenis ini disimpan terenkripsi (hash) sehingga tidak dapat dilihat. Gunakan Reset PIN.');
        }

        $pin = $this->desk->revealPin($ticket);
        Audit::log('pin.reveal', (int)$item['id'], "Tiket {$ticket}. Alasan: {$reason}");
        $this->show($ticket, $item, result: ['action' => 'reveal', 'pin' => $pin]);
    }

    public function reset(): void
    {
        [$ticket, $item, $reason] = $this->guardedRequest();

        $pin = $this->desk->resetPin($ticket);
        Audit::log('pin.reset', $item['type'] === PinDesk::TYPE_LP ? (int)$item['id'] : null,
            "Tiket {$ticket}" . ($item['linked'] ? " (+ {$item['linked']})" : '') . ". Alasan: {$reason}");
        $this->show($ticket, $this->desk->find($ticket), result: ['action' => 'reset', 'pin' => $pin]);
    }

    /**
     * POST + CSRF + tiket ditemukan + identitas terverifikasi + alasan + batas per petugas
     *
     * @return array{0:string, 1:array, 2:string}
     */
    private function guardedRequest(): array
    {
        if (!$this->isPost()) {
            $this->redirect('/pin');
        }
        $ticket = strtoupper(trim((string)$this->post('ticket', '')));
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid. Silakan ulangi.');
            $this->redirect('/pin?ticket=' . urlencode($ticket));
        }

        $item = $this->desk->find($ticket);
        if (!$item) {
            $this->show($ticket, null, error: 'Nomor tiket tidak ditemukan.');
        }

        $reason = mb_substr(trim((string)$this->post('reason', '')), 0, 300);
        if (!$this->post('identity_verified')) {
            $this->show($ticket, $item, error: 'Centang konfirmasi bahwa identitas pelapor sudah diverifikasi (KTP/NIK & nomor HP cocok).');
        }
        if (mb_strlen($reason) < 10) {
            $this->show($ticket, $item, error: 'Tuliskan alasan / dasar permintaan (min. 10 karakter), mis. "Pelapor datang ke SPKT membawa KTP, PIN hilang".');
        }

        $key = (string)Auth::id();
        if ($this->throttle->tooMany('pin_desk_user', $key)) {
            $this->show($ticket, $item, error: 'Batas layanan PIN per petugas tercapai. Coba lagi dalam '
                . Throttle::windowMinutes('pin_desk_user') . ' menit atau hubungi Superadmin.');
        }
        $this->throttle->hit('pin_desk_user', $key);

        return [$ticket, $item, $reason];
    }

    private function show(string $ticket, ?array $item, ?array $result = null, ?string $error = null): never
    {
        if ($result !== null) {
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }
        $this->render('backoffice/pin_desk', [
            'pageTitle' => 'Layanan PIN Pelapor',
            'ticket'    => $ticket,
            'item'      => $item,
            'notFound'  => $ticket !== '' && $item === null,
            'result'    => $result,
            'error'     => $error,
        ], 'admin');
        exit;
    }
}
