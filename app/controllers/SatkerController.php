<?php
/**
 * SIGAP-POLRI: Satker Controller (Inbox Disposisi & Penugasan Kanit)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\Disposition;
use App\Models\Report;
use App\Models\User;
use App\Models\KapoldaDirective;

class SatkerController extends Controller
{
    private Report $reportModel;
    private User $userModel;
    private Disposition $dispositionModel;

    public function __construct()
    {
        Auth::requireRole([
            Constants::ROLE_SATKER_HEAD,
            Constants::ROLE_SATKER_ADMIN,
            Constants::ROLE_SUPERADMIN
        ]);

        $this->reportModel = new Report();
        $this->userModel = new User();
        $this->dispositionModel = new Disposition();
    }

    /**
     * Inbox Laporan Disposisi Tingkat Satker / Polda
     */
    public function index(): void
    {
        $isSuper = Auth::role() === Constants::ROLE_SUPERADMIN;
        $this->dispositionModel->escalateOverdue();

        $pager = $this->reportModel->paginateFilteredReports([
            'satker_id' => $isSuper ? null : (Auth::satkerId() ?? -1)
        ], (int)$this->get('page', 1));

        // Daftar penyidik: Satker sendiri, atau seluruh Satker untuk superadmin (difilter per laporan di modal)
        $investigators = $isSuper
            ? $this->userModel->getAllActiveInvestigators()
            : $this->userModel->getInvestigatorsBySatker((int)Auth::satkerId());

        // Tujuan terusan per Satker pemegang laporan (superadmin melihat laporan dari banyak Satker)
        $forwardTargets = [];
        foreach (array_unique(array_filter(array_map('intval', array_column($pager['items'], 'current_satker_id')))) as $sid) {
            $forwardTargets[$sid] = $this->dispositionModel->forwardTargets($sid);
        }

        $this->render('backoffice/satker_inbox', [
            'pageTitle'     => 'Inbox Disposisi Satker & Penugasan Kanit',
            'reports'       => $pager['items'],
            'tickets'       => $this->dispositionModel->latestForReports(array_column($pager['items'], 'id')),
            'forwardTargets' => $forwardTargets,
            'escalated'     => $this->dispositionModel->escalatedWithin($isSuper ? null : (Auth::satkerId() ?? -1)),
            'pager'         => $pager,
            'investigators' => $investigators,
            'directives'    => (new KapoldaDirective())->getOpenForSatker($isSuper ? null : (Auth::satkerId() ?? -1))
        ], 'admin');
    }

    /**
     * Penugasan Penyidik (Sprintgas) oleh Kanit / Kasatker
     */
    public function assign(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/satker');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/satker');
        }

        $reportId = (int)$this->post('report_id', 0);
        $investigatorId = (int)$this->post('investigator_id', 0);
        $sprintgas = (string)$this->post('sprintgas_number', '');

        if ($reportId <= 0 || $investigatorId <= 0) {
            Session::setFlash('error', 'Pilih penyidik yang sah.');
            $this->redirect('/satker');
        }

        // Laporan harus berada di Satker pengguna dan dalam tahap disposisi/penugasan
        $report = $this->reportModel->findById($reportId);
        $isSuper = Auth::role() === Constants::ROLE_SUPERADMIN;
        $reportSatkerId = (int)($report['current_satker_id'] ?? 0);

        if (!$report || $reportSatkerId <= 0 || (!$isSuper && $reportSatkerId !== Auth::satkerId())) {
            Session::setFlash('error', 'Laporan tidak ditemukan atau bukan disposisi untuk Satker Anda.');
            $this->redirect('/satker');
        }

        if (!in_array($report['status'], [Constants::STATUS_DISPATCHED, Constants::STATUS_ASSIGNED], true)) {
            Session::setFlash('error', 'Penugasan penyidik hanya untuk laporan yang telah didisposisikan.');
            $this->redirect('/satker');
        }

        if ($sprintgas === '') {
            $sprintgas = Constants::documentNumber('SP.Gas', $report['satker_code'] ?? null);
        } elseif (mb_strlen($sprintgas) > 100) {
            Session::setFlash('error', 'Nomor Sprintgas maksimal 100 karakter.');
            $this->redirect('/satker');
        }

        // Penyidik harus anggota aktif Satker pemilik laporan
        $investigatorIds = array_map('intval', array_column($this->userModel->getInvestigatorsBySatker($reportSatkerId), 'id'));
        if (!in_array($investigatorId, $investigatorIds, true)) {
            Session::setFlash('error', 'Penyidik yang dipilih bukan personil Satker pemilik laporan.');
            $this->redirect('/satker');
        }

        $success = $this->reportModel->assignInvestigator($reportId, $investigatorId, Auth::id(), $sprintgas);

        if ($success) {
            // Menunjuk penyidik berarti tiket diterima Satker
            $this->dispositionModel->accept($reportId, $reportSatkerId, Auth::id(), 'Diterima bersamaan dengan penerbitan Sprintgas');
            Audit::log('report.assign', $reportId, "Penyidik #{$investigatorId}, {$sprintgas}");
            Session::setFlash('success', 'Surat Perintah Penugasan (Sprintgas) berhasil diterbitkan ke Penyidik.');
        } else {
            Session::setFlash('error', 'Gagal memproses penugasan penyidik.');
        }

        $this->redirect('/satker');
    }

    /**
     * Terima tiket disposisi
     */
    public function terima(): void
    {
        $report = $this->ticketFromPost();
        $notes = mb_substr(trim((string)$this->post('notes', '')), 0, 1000);

        if ($this->dispositionModel->accept((int)$report['id'], (int)$report['current_satker_id'], Auth::id(), $notes)) {
            Audit::log('disposition.accept', (int)$report['id'], $report['satker_name'] ?? null);
            Session::setFlash('success', "Tiket {$report['ticket_number']} diterima. Segera tunjuk penyidik pelaksana.");
        } else {
            Session::setFlash('error', 'Tiket sudah diterima atau tidak lagi berada di Satker ini.');
        }
        $this->redirect('/satker');
    }

    /**
     * Kembalikan tiket ke SPKT (bukan kewenangan / salah sasaran)
     */
    public function kembalikan(): void
    {
        $report = $this->ticketFromPost();
        $reason = mb_substr(trim((string)$this->post('notes', '')), 0, 1000);
        if (mb_strlen($reason) < 10) {
            Session::setFlash('error', 'Alasan pengembalian ke SPKT wajib diisi (min. 10 karakter).');
            $this->redirect('/satker');
        }

        if ($this->dispositionModel->returnToSpkt((int)$report['id'], (int)$report['current_satker_id'], Auth::id(), $reason)) {
            Audit::log('disposition.return', (int)$report['id'], $reason);
            Session::setFlash('success', "Tiket {$report['ticket_number']} dikembalikan ke SPKT untuk disposisi ulang.");
        } else {
            Session::setFlash('error', 'Hanya tiket yang belum ditangani penyidik yang dapat dikembalikan ke SPKT.');
        }
        $this->redirect('/satker');
    }

    /**
     * Teruskan tiket ke satuan jajaran (mis. Ditreskrimum Polda → Satreskrim Polres)
     */
    public function teruskan(): void
    {
        $report = $this->ticketFromPost();
        $fromId = (int)$report['current_satker_id'];
        $targetId = (int)$this->post('target_satker_id', 0);
        $notes = mb_substr(trim((string)$this->post('notes', '')), 0, 1000);

        if (!in_array($targetId, array_map('intval', array_column($this->dispositionModel->forwardTargets($fromId), 'id')), true)) {
            Session::setFlash('error', 'Satuan tujuan terusan harus berada di jajaran Satker Anda.');
            $this->redirect('/satker');
        }
        if ($notes === '') {
            Session::setFlash('error', 'Catatan / instruksi terusan wajib diisi.');
            $this->redirect('/satker');
        }

        if ($this->dispositionModel->dispatch((int)$report['id'], $targetId, Auth::id(), $notes, $fromId)) {
            Audit::log('disposition.forward', (int)$report['id'], "Diteruskan ke Satker #{$targetId}");
            Session::setFlash('success', "Tiket {$report['ticket_number']} diteruskan ke satuan jajaran.");
        } else {
            Session::setFlash('error', 'Tiket tidak dapat diteruskan pada status ini.');
        }
        $this->redirect('/satker');
    }

    /**
     * Guard aksi tiket: POST + CSRF + laporan milik Satker pengguna (superadmin: Satker mana pun)
     */
    private function ticketFromPost(): array
    {
        if (!$this->isPost()) {
            $this->redirect('/satker');
        }
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/satker');
        }

        $report = $this->reportModel->findById((int)$this->post('report_id', 0));
        $isSuper = Auth::role() === Constants::ROLE_SUPERADMIN;
        $satkerId = (int)($report['current_satker_id'] ?? 0);
        if (!$report || $satkerId <= 0 || (!$isSuper && $satkerId !== Auth::satkerId())) {
            Session::setFlash('error', 'Laporan tidak ditemukan atau bukan disposisi untuk Satker Anda.');
            $this->redirect('/satker');
        }
        return $report;
    }

    /**
     * Tanggapi Instruksi Atensi Kapolri: terima (acknowledge) atau selesaikan (resolve)
     */
    public function directive(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/satker');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/satker');
        }

        $directiveModel = new KapoldaDirective();
        $directive = $directiveModel->findById((int)$this->post('directive_id', 0));
        $isSuper = Auth::role() === Constants::ROLE_SUPERADMIN;
        if (!$directive || (!$isSuper && (int)$directive['target_satker_id'] !== Auth::satkerId())) {
            Session::setFlash('error', 'Instruksi tidak ditemukan atau bukan untuk Satker Anda.');
            $this->redirect('/satker');
        }

        $action = $this->post('action', '');
        $notes = trim((string)$this->post('response_notes', ''));
        $newStatus = match ($action) {
            'acknowledge' => 'ACKNOWLEDGED',
            'resolve' => 'RESOLVED',
            default => null,
        };

        if ($newStatus === null) {
            Session::setFlash('error', 'Aksi tidak valid.');
            $this->redirect('/satker');
        }
        if ($newStatus === 'RESOLVED' && $notes === '') {
            Session::setFlash('error', 'Laporan tanggapan wajib diisi untuk menyelesaikan instruksi Kapolri.');
            $this->redirect('/satker');
        }

        if ($directiveModel->respond((int)$directive['id'], Auth::id(), $newStatus, $notes)) {
            Audit::log('directive.respond', (int)$directive['report_id'], "Instruksi #{$directive['id']} → {$newStatus}");
            Session::setFlash('success', $newStatus === 'ACKNOWLEDGED'
                ? 'Instruksi Atensi Kapolri telah diterima.'
                : 'Instruksi Atensi Kapolri dilaporkan selesai.');
        } else {
            Session::setFlash('error', 'Instruksi sudah ditanggapi sebelumnya.');
        }

        $this->redirect('/satker');
    }
}
