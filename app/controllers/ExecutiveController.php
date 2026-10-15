<?php
/**
 * SIGAP-POLDA: Executive Controller (Command Center Khusus Kapolda & PJU)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\Report;
use App\Models\Satker;

class ExecutiveController extends Controller
{
    private Report $reportModel;
    private Satker $satkerModel;

    public function __construct()
    {
        Auth::requireRole([
            Constants::ROLE_EXECUTIVE,
            Constants::ROLE_SUPERADMIN
        ]);

        $this->reportModel = new Report();
        $this->satkerModel = new Satker();
    }

    /**
     * Dashboard Executive Kapolda (GIS Heatmap & SLA Command Center)
     */
    public function index(): void
    {
        $stats = $this->reportModel->getExecutiveStats();
        $heatmapPoints = $this->reportModel->getHeatmapPoints();
        $satkers = $this->satkerModel->getAll();

        // Ambil laporan-laporan yang sedang menjadi Atensi Kapolda
        $attentionReports = $this->reportModel->getFilteredReports([
            'is_kapolda_attention' => 1,
            'active_only'          => 1
        ]);

        // Ambil laporan terbaru
        $recentReports = $this->reportModel->getFilteredReports([]);

        $this->render('executive/dashboard', [
            'pageTitle'        => 'Dashboard Eksekutif Kapolda - Peta Kerawanan Kamtibmas & Komando',
            'stats'            => $stats,
            'heatmapPoints'    => $heatmapPoints,
            'satkers'          => $satkers,
            'attentionReports' => $attentionReports,
            'slaWatchlist'     => $this->reportModel->getSlaWatchlist(),
            'spktBacklog'      => $this->reportModel->getSpktBacklog(),
            'recentReports'    => $recentReports
        ], 'executive');
    }

    /**
     * Berikan Instruksi Langsung "Atensi Kapolda" (Bintang Merah)
     */
    public function giveAttention(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/executive');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/executive');
        }

        $reportId = (int)$this->post('report_id', 0);
        $directiveText = $this->post('directive_text', 'Laporan ini menjadi atensi langsung Kapolda. Segera tuntaskan dan laporkan hasil penyelidikan 1x24 jam!');

        if ($reportId <= 0 || empty($directiveText)) {
            Session::setFlash('error', 'Laporan atau teks instruksi Atensi Kapolda tidak boleh kosong.');
            $this->redirect('/executive');
        }

        $success = $this->reportModel->giveKapoldaAttention($reportId, Auth::id(), $directiveText);

        if ($success) {
            Session::setFlash('success', 'Instruksi "Atensi Kapolda" berhasil diterbitkan dan ditandai Bintang Merah pada sistem.');
        } else {
            Session::setFlash('error', 'Gagal membubuhkan Atensi Kapolda.');
        }

        $this->redirect('/executive');
    }

    /**
     * API JSON Endpoint Titik Koordinat GIS Heatmap
     */
    public function apiHeatmap(): void
    {
        $points = $this->reportModel->getHeatmapPoints();
        $this->json([
            'status' => 'success',
            'total'  => count($points),
            'data'   => $points
        ]);
    }
}
