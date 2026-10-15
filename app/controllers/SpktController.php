<?php
/**
 * SIGAP-POLDA: Spkt Controller (Sentra Pelayanan Kepolisian Terpadu)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\Report;
use App\Models\Satker;

class SpktController extends Controller
{
    private Report $reportModel;
    private Satker $satkerModel;

    // Itwasda (pengawas internal) dapat melihat meja triage, tetapi hanya role ini yang dapat bertindak
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
    }

    /**
     * Meja Triage SPKT & Antrean Verifikasi Laporan Masuk
     */
    public function index(): void
    {
        $statusFilter = $this->get('status', '');
        $search = $this->get('search', '');

        $reports = $this->reportModel->getFilteredReports([
            'status' => $statusFilter,
            'search' => $search
        ]);

        $satkers = $this->satkerModel->getAll();

        $this->render('backoffice/spkt_triage', [
            'pageTitle'    => 'Meja Triage SPKT & Command Center Polda',
            'reports'      => $reports,
            'satkers'      => $satkers,
            'statusFilter' => $statusFilter,
            'search'       => $search,
            'canAct'       => Auth::hasRole(self::ACTION_ROLES)
        ], 'admin');
    }

    /**
     * Proses Disposisi Laporan ke Satuan Kerja (Ditreskrimum/Polres, dll)
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

        $success = $this->reportModel->dispatchReport($reportId, $satkerId, Auth::id(), $notes);

        if ($success) {
            Session::setFlash('success', 'Laporan berhasil didisposisikan ke Satuan Kerja terkait.');
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
            Session::setFlash('success', 'Laporan telah ditandai Ditolak.');
        } else {
            Session::setFlash('error', 'Gagal menolak laporan.');
        }

        $this->redirect('/spkt');
    }

    private function requireActionRole(): void
    {
        if (!Auth::hasRole(self::ACTION_ROLES)) {
            Session::setFlash('error', 'Akses Ditolak: peran pengawasan hanya dapat melihat, tidak dapat mengubah laporan.');
            $this->redirect('/spkt');
        }
    }
}
