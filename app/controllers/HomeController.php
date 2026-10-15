<?php
/**
 * SIGAP-POLDA: Home Controller (Landing Page Publik)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Report;

class HomeController extends Controller
{
    private Report $reportModel;

    public function __construct()
    {
        $this->reportModel = new Report();
    }

    public function index(): void
    {
        $stats = $this->reportModel->getExecutiveStats();

        $this->render('public/index', [
            'pageTitle' => 'Layanan Pengaduan Masyarakat Terpadu - POLDA',
            'stats'     => $stats
        ], 'main');
    }
}
