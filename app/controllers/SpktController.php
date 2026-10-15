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
            'search'       => $search
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

        $reportId = (int)$this->post('report_id', 0);
        $satkerId = (int)$this->post('satker_id', 0);
        $notes = $this->post('dispatch_notes', 'Disposisi untuk penyelidikan lebih lanjut.');

        if ($reportId <= 0 || $satkerId <= 0) {
            Session::setFlash('error', 'Pilih Satker tujuan disposisi yang sah.');
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

        $reportId = (int)$this->post('report_id', 0);
        $reason = trim($this->post('rejection_reason', ''));

        if ($reportId <= 0 || $reason === '') {
            Session::setFlash('error', 'Alasan penolakan laporan wajib diisi.');
            $this->redirect('/spkt');
        }

        $report = $this->reportModel->findById($reportId);
        if (!$report || !in_array($report['status'], [Constants::STATUS_SUBMITTED, Constants::STATUS_VERIFIED_SPKT], true)) {
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
}
