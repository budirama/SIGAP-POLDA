<?php
/**
 * SIGAP-POLRI: Executive Controller (Command Center Khusus Kapolri & PJU)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\Report;
use App\Models\Satker;
use App\Models\KapoldaDirective;

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
     * Dashboard Executive Kapolri (GIS Heatmap & SLA Command Center)
     */
    public function index(): void
    {
        $stats = $this->reportModel->getExecutiveStats();
        $heatmapPoints = $this->reportModel->getHeatmapPoints();
        $satkers = $this->satkerModel->getAll();

        // Ambil laporan-laporan yang sedang menjadi Atensi Kapolri
        $attentionReports = $this->reportModel->getFilteredReports([
            'is_kapolda_attention' => 1,
            'active_only'          => 1
        ]);

        // Pilihan laporan untuk Atensi Kapolri: hanya perkara aktif
        $recentReports = $this->reportModel->getFilteredReports(['active_only' => 1]);

        $this->render('executive/dashboard', [
            'pageTitle'        => 'Dashboard Eksekutif Kapolri - Peta Kerawanan Kamtibmas & Komando',
            'stats'            => $stats,
            'heatmapPoints'    => $heatmapPoints,
            'satkers'          => $satkers,
            'attentionReports' => $attentionReports,
            'directiveStatus'  => (new KapoldaDirective())->latestByReports(array_column($attentionReports, 'id')),
            'slaWatchlist'     => $this->reportModel->getSlaWatchlist(),
            'spktBacklog'      => $this->reportModel->getSpktBacklog(),
            'recentReports'    => $recentReports
        ], 'executive');
    }

    /**
     * Berikan Instruksi Langsung "Atensi Kapolri" (Bintang Merah)
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
        $directiveText = $this->post('directive_text', 'Laporan ini menjadi atensi langsung Kapolri. Segera tuntaskan dan laporkan hasil penyelidikan 1x24 jam!');

        if ($reportId <= 0 || empty($directiveText)) {
            Session::setFlash('error', 'Laporan atau teks instruksi Atensi Kapolri tidak boleh kosong.');
            $this->redirect('/executive');
        }

        $report = $this->reportModel->findById($reportId);
        if (!$report || in_array($report['status'], [Constants::STATUS_CLOSED, Constants::STATUS_REJECTED], true)) {
            Session::setFlash('error', 'Atensi Kapolri hanya dapat diberikan pada perkara yang masih aktif.');
            $this->redirect('/executive');
        }

        $success = $this->reportModel->giveKapoldaAttention($reportId, Auth::id(), $directiveText);

        if ($success) {
            Audit::log('report.attention', $reportId, $directiveText);
            Session::setFlash('success', 'Instruksi "Atensi Kapolri" berhasil diterbitkan dan ditandai Bintang Merah pada sistem.');
        } else {
            Session::setFlash('error', 'Perkara ini masih memiliki instruksi Atensi Kapolri yang belum diselesaikan.');
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
