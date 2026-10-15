<?php
/**
 * SIGAP-POLDA: Satker Controller (Inbox Disposisi & Penugasan Kanit)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\Report;
use App\Models\User;

class SatkerController extends Controller
{
    private Report $reportModel;
    private User $userModel;

    public function __construct()
    {
        Auth::requireRole([
            Constants::ROLE_SATKER_HEAD,
            Constants::ROLE_SATKER_ADMIN,
            Constants::ROLE_SUPERADMIN
        ]);

        $this->reportModel = new Report();
        $this->userModel = new User();
    }

    /**
     * Inbox Laporan Disposisi Tingkat Satker / Polres
     */
    public function index(): void
    {
        $isSuper = Auth::role() === Constants::ROLE_SUPERADMIN;

        $reports = $this->reportModel->getFilteredReports([
            'satker_id' => $isSuper ? null : (Auth::satkerId() ?? -1)
        ]);

        // Daftar penyidik: Satker sendiri, atau seluruh Satker untuk superadmin (difilter per laporan di modal)
        $investigators = $isSuper
            ? $this->userModel->getAllActiveInvestigators()
            : $this->userModel->getInvestigatorsBySatker((int)Auth::satkerId());

        $this->render('backoffice/satker_inbox', [
            'pageTitle'     => 'Inbox Disposisi Satker & Penugasan Kanit',
            'reports'       => $reports,
            'investigators' => $investigators
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
        $sprintgas = $this->post('sprintgas_number', 'SP.Gas/' . random_int(100, 999) . '/' . date('m/Y') . '/Satker');

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

        // Penyidik harus anggota aktif Satker pemilik laporan
        $investigatorIds = array_map('intval', array_column($this->userModel->getInvestigatorsBySatker($reportSatkerId), 'id'));
        if (!in_array($investigatorId, $investigatorIds, true)) {
            Session::setFlash('error', 'Penyidik yang dipilih bukan personil Satker pemilik laporan.');
            $this->redirect('/satker');
        }

        $success = $this->reportModel->assignInvestigator($reportId, $investigatorId, Auth::id(), $sprintgas);

        if ($success) {
            Session::setFlash('success', 'Surat Perintah Penugasan (Sprintgas) berhasil diterbitkan ke Penyidik.');
        } else {
            Session::setFlash('error', 'Gagal memproses penugasan penyidik.');
        }

        $this->redirect('/satker');
    }
}
