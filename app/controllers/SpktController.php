<?php
/**
 * SIGAP-POLRI: Spkt Controller (Sentra Pelayanan Kepolisian Terpadu)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\Disposition;
use App\Models\Report;
use App\Models\Satker;

class SpktController extends Controller
{
    private Report $reportModel;
    private Satker $satkerModel;
    private Disposition $dispositionModel;

    // Itwasum (pengawas internal) dapat melihat meja triage, tetapi hanya role ini yang dapat bertindak
    private const ACTION_ROLES = [
        Constants::ROLE_SPKT_OPERATOR,
        Constants::ROLE_SUPERADMIN,
    ];

    public function __construct()
    {
        Auth::requireRole([
            Constants::ROLE_SPKT_OPERATOR,
            Constants::ROLE_SUPERADMIN,
            Constants::ROLE_INTERNAL_AFFAIRS
        ]);

        $this->reportModel = new Report();
        $this->satkerModel = new Satker();
        $this->dispositionModel = new Disposition();
    }

    /**
     * Meja Triage SPKT & Antrean Verifikasi Laporan Masuk
     */
    public function index(): void
    {
        $statusFilter = $this->get('status', '');
        $search = $this->get('search', '');

        $pager = $this->reportModel->paginateFilteredReports([
            'status' => $statusFilter,
            'search' => $search
        ], (int)$this->get('page', 1));

        $this->dispositionModel->escalateOverdue();

        // Rekomendasi tujuan hanya untuk laporan yang masih dapat didisposisikan
        $recommendations = [];
        foreach ($pager['items'] as $r) {
            if (in_array($r['status'], Constants::DISPATCHABLE_STATUSES, true)) {
                $recommendations[(int)$r['id']] = $this->dispositionModel->recommend($r);
            }
        }

        $this->render('backoffice/spkt_triage', [
            'pageTitle'    => 'Meja Triage SPKT & Command Center Polri',
            'reports'      => $pager['items'],
            'pager'        => $pager,
            'territories'  => $this->dispositionModel->territorialUnits(),
            'recommendations' => $recommendations,
            'tickets'      => $this->dispositionModel->latestForReports(array_column($pager['items'], 'id')),
            'escalated'    => $this->dispositionModel->escalatedWithin(null),
            'returned'     => $this->dispositionModel->returnedAwaitingSpkt(),
            'statusFilter' => $statusFilter,
            'search'       => $search,
            'canAct'       => Auth::hasRole(self::ACTION_ROLES)
        ], 'admin');
    }

    /**
     * Proses Disposisi Laporan ke Satuan Kerja (Bareskrim, Korlantas, Polda jajaran, dll)
     */
    public function dispatch(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/spkt');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/spkt');
        }

        $this->requireActionRole();

        $reportId = (int)$this->post('report_id', 0);
        $satkerId = (int)$this->post('satker_id', 0);
        $notes = $this->post('dispatch_notes', 'Disposisi untuk penyelidikan lebih lanjut.');

        if ($reportId <= 0 || !$this->satkerModel->findById($satkerId)) {
            Session::setFlash('error', 'Pilih Satker tujuan disposisi yang sah.');
            $this->redirect('/spkt');
        }

        $report = $this->reportModel->findById($reportId);
        if (!$report || !in_array($report['status'], Constants::DISPATCHABLE_STATUSES, true)) {
            Session::setFlash('error', 'Laporan yang sudah dalam penyidikan, selesai, atau ditolak tidak dapat didisposisikan.');
            $this->redirect('/spkt');
        }

        if ((int)$report['current_satker_id'] === $satkerId) {
            Session::setFlash('error', 'Laporan sudah berada di Satker tersebut.');
            $this->redirect('/spkt');
        }

        $success = $this->dispositionModel->dispatch($reportId, $satkerId, Auth::id(), (string)$notes);

        if ($success) {
            Audit::log('report.dispatch', $reportId, 'Satker tujuan #' . $satkerId);
            Session::setFlash('success', 'Tiket disposisi terkirim ke Satuan Kerja terkait.');
        } else {
            Session::setFlash('error', 'Gagal memproses disposisi laporan.');
        }

        $this->redirect('/spkt');
    }

    /**
     * Tolak Laporan (Hoax / Tidak Memenuhi Unsur Pidana)
     */
    public function reject(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/spkt');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/spkt');
        }

        $this->requireActionRole();

        $reportId = (int)$this->post('report_id', 0);
        $reason = trim($this->post('rejection_reason', ''));

        if ($reportId <= 0 || $reason === '') {
            Session::setFlash('error', 'Alasan penolakan laporan wajib diisi.');
            $this->redirect('/spkt');
        }

        $report = $this->reportModel->findById($reportId);
        if (!$report || !in_array($report['status'], Constants::REJECTABLE_STATUSES, true)) {
            Session::setFlash('error', 'Hanya laporan yang menunggu verifikasi yang dapat ditolak.');
            $this->redirect('/spkt');
        }

        if ($this->reportModel->rejectReport($reportId, Auth::id(), $reason)) {
            Audit::log('report.reject', $reportId, $reason);
            Session::setFlash('success', 'Laporan telah ditandai Ditolak.');
        } else {
            Session::setFlash('error', 'Gagal menolak laporan.');
        }

        $this->redirect('/spkt');
    }

    /**
     * JSON: satuan penerima tiket dalam satu wilayah (wilayah itu sendiri + satuan fungsinya)
     */
    public function units(int $territoryId): void
    {
        $this->json(['units' => array_map(fn($u) => [
            'id'       => (int)$u['id'],
            'name'     => $u['name'],
            'function' => $u['function_code'] ? (Disposition::FUNCTIONS[$u['function_code']] ?? $u['function_code']) : null,
        ], $this->dispositionModel->unitsInTerritory($territoryId))]);
    }

    private function requireActionRole(): void
    {
        if (!Auth::hasRole(self::ACTION_ROLES)) {
            Session::setFlash('error', 'Akses Ditolak: peran pengawasan hanya dapat melihat, tidak dapat mengubah laporan.');
            $this->redirect('/spkt');
        }
    }
}
