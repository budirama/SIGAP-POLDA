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
        $mySatkerId = Auth::satkerId() ?? 2; // Default Ditreskrimum jika superadmin belum diset satker

        $reports = $this->reportModel->getFilteredReports([
            'satker_id' => (Auth::role() === Constants::ROLE_SUPERADMIN) ? null : $mySatkerId
        ]);

        // Daftar personil penyidik di satker ini
        $investigators = $this->userModel->getInvestigatorsBySatker($mySatkerId);

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

        $success = $this->reportModel->assignInvestigator($reportId, $investigatorId, Auth::id(), $sprintgas);

        if ($success) {
            Session::setFlash('success', 'Surat Perintah Penugasan (Sprintgas) berhasil diterbitkan ke Penyidik.');
        } else {
            Session::setFlash('error', 'Gagal memproses penugasan penyidik.');
        }

        $this->redirect('/satker');
    }
}
