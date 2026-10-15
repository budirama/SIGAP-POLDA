<?php
/**
 * SIGAP-POLRI: Home Controller (Landing Page Publik)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Report;
use App\Models\Satker;

class HomeController extends Controller
{
    private Report $reportModel;
    private Satker $satkerModel;

    public function __construct()
    {
        $this->reportModel = new Report();
        $this->satkerModel = new Satker();
    }

    public function index(): void
    {
        $this->render('public/index', [
            'pageTitle' => 'Layanan Pengaduan Masyarakat & Management Posko Terpadu - POLRI',
            'stats'     => $this->reportModel->getExecutiveStats(),
            'units'     => $this->satkerModel->countActiveByType()
        ], 'main');
    }
}
