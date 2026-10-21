<?php
/**
 * SIGAP-POLRI: Controller Layanan Terpadu "Satu Nomor, Satu Jejak" (v1.3)
 * Mengorkestrasi 8 Modul Quick Win & Program Prioritas Kapolri
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\ModuleManager;
use App\Core\OpsPdp;
use App\Core\OpsTracker;
use App\Models\OneService\OpsTrack;
use App\Models\OneService\OpsDispatch;
use App\Models\OneService\OpsCase;
use App\Models\OneService\OpsCyber;
use App\Models\OneService\OpsBhabin;
use App\Models\OneService\OpsDumas;
use App\Models\OneService\OpsAnalytics;
use App\Models\OneService\OpsDrill;
use App\Models\Satker;
use App\Config\Constants;

class OneServiceController extends Controller
{
    /** Personil Polri yang boleh membuka backoffice OneService (bukan warga / operator Posko) */
    private const PERSONNEL_ROLES = [
        Constants::ROLE_SPKT_OPERATOR,
        Constants::ROLE_CYBER_HUMAS,
        Constants::ROLE_SATKER_HEAD,
        Constants::ROLE_SATKER_ADMIN,
        Constants::ROLE_INVESTIGATOR,
        Constants::ROLE_INTERNAL_AFFAIRS,
        Constants::ROLE_EXECUTIVE,
        Constants::ROLE_SUPERADMIN,
    ];

    /** Pimpinan (Kapolri/PJU): melihat seluruh data hingga detail, tanpa mengubah.
     *  Itwasum tetap dapat bertindak (antara lain mendaftarkan aduan Dumas). */
    private const READ_ONLY_ROLES = [
        Constants::ROLE_EXECUTIVE,
    ];

    private Satker $satkerModel;

    public function __construct()
    {
        ModuleManager::requireActive('ONESERVICE');
        $this->satkerModel = new Satker();
    }

    /**
     * Beranda Hub Layanan Terpadu "Satu Nomor, Satu Jejak" (Khusus Backoffice Personil)
     */
    public function index(): void
    {
        ModuleManager::requireActive('oneservice_hub');
        $this->requirePersonnel();

        $report = OpsAnalytics::getAccountabilityReport();
        $stalledCount = OpsTrack::runSlaWatchdog();

        $this->render('oneservice/index', [
            'pageTitle' => 'Satu Nomor, Satu Jejak (One Police Service) - SIGAP POLRI',
            'report'    => $report,
            'stalled'   => $stalledCount,
            'clusters'  => OpsCyber::getSyndicateClusters(),
            'anomalies' => OpsDumas::detectAnomalies()
        ], 'admin');
    }

    /**
     * Pelacak Layanan Terpadu (Akses Publik Tanpa Login)
     * Mendukung Satu Nomor POL-YYYYMM-KANAL-XXXXX maupun Nomor Tiket Legacy LP-POLRI-*
     */
    public function track(): void
    {
        ModuleManager::requireActive('oneservice_track');
        $trackingNumber = trim((string)($_GET['ticket'] ?? $_POST['ticket'] ?? ''));
        $pin = trim((string)($_GET['pin'] ?? $_POST['pin'] ?? ''));
        $error = null;
        $caseData = null;
        $timeline = [];

        // Deteksi tiket legacy jika pengguna memasukkan nomor LP lama
        if ($trackingNumber !== '' && !str_starts_with($trackingNumber, 'POL-')) {
            $pdo = \App\Config\Database::getConnection();
            $stmt = $pdo->prepare("SELECT tracking_number FROM `ops_global_tracks` WHERE external_reference_no = ? LIMIT 1");
            $stmt->execute([$trackingNumber]);
            $resolvedTrack = $stmt->fetchColumn();
            if ($resolvedTrack) {
                $trackingNumber = (string)$resolvedTrack;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($trackingNumber !== '' && $pin !== '')) {
            if ($trackingNumber === '' || $pin === '') {
                $error = 'Nomor pelacakan dan PIN 6-digit wajib diisi.';
            } else {
                $isValidPin = OpsTrack::verifyPin($trackingNumber, $pin);
                if (!$isValidPin) {
                    $error = 'Nomor pelacakan atau PIN keamanan tidak sesuai.';
                } else {
                    $caseData = OpsTrack::findByTrackingNumber($trackingNumber);
                    if (!$caseData) {
                        $error = 'Data perkara tidak ditemukan dalam sistem.';
                    } else {
                        $timeline = OpsTrack::getTimeline($trackingNumber);

                        // Rekam audit jika dibuka oleh personil kepolisian yang login
                        if (Auth::check()) {
                            OpsPdp::logAccess(
                                $trackingNumber,
                                (int)$caseData['current_satker_id'],
                                'TRACKING_DETAIL',
                                'Pemantauan perkembangan perkara via portal lacak'
                            );
                        }
                    }
                }
            }
        }

        $this->render('oneservice/track', [
            'pageTitle'      => 'Pelacak Terpadu "Satu Nomor, Satu Jejak" - POLRI',
            'trackingNumber' => $trackingNumber,
            'pin'            => $pin,
            'error'          => $error,
            'caseData'       => $caseData,
            'timeline'       => $timeline
        ], Auth::check() ? 'admin' : 'main');
    }

    /**
     * Modul 2: Triase & Dispatch 110 CAD
     */
    public function dispatch(): void
    {
        ModuleManager::requireActive('oneservice_dispatch');
        $this->requirePersonnel();

        $message = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrf()) {
                Session::setFlash('error', 'Sesi keamanan berakhir (token CSRF tidak valid). Silakan ulangi.');
                $this->redirect(preg_replace('#^.*?/oneservice#', '/oneservice', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/oneservice'));
            }
            $action = $_POST['action'] ?? '';

            if ($action === 'create_call') {
                $res = OpsDispatch::createIncomingCall([
                    'caller_phone'      => trim((string)($_POST['caller_phone'] ?? '')),
                    'caller_name'       => trim((string)($_POST['caller_name'] ?? '')),
                    'urgency_level'     => $_POST['urgency_level'] ?? 'P1_EMERGENCY',
                    'incident_category' => $_POST['incident_category'] ?? 'DARURAT_110',
                    'call_summary'      => trim((string)($_POST['call_summary'] ?? '')),
                    'address'           => trim((string)($_POST['address'] ?? '')),
                    'satker_id'         => (int)($_POST['satker_id'] ?? 1)
                ]);

                if ($res['success']) {
                    $message = "Panggilan 110 berhasil diregistrasi dengan Nomor Lacak: {$res['tracking_number']} (PIN: {$res['security_pin']}).";
                } else {
                    $error = $res['error'] ?? 'Gagal membuat panggilan 110.';
                }
            } elseif ($action === 'dispatch_unit') {
                $res = OpsDispatch::dispatchUnit(
                    trim((string)($_POST['tracking_number'] ?? '')),
                    trim((string)($_POST['unit_callsign'] ?? 'PATROLI-SAMAPTA-01')),
                    $_POST['vehicle_type'] ?? 'SAMAPTA_CAR',
                    Auth::user()['nrp'] ?? '88030301'
                );

                if ($res['success']) {
                    $message = "Unit patroli {$res['cad_response']['assigned_unit']} berhasil didisposisi via CAD (ETA {$res['cad_response']['eta_minutes']} menit).";
                } else {
                    $error = $res['error'] ?? 'Gagal mendisposisikan unit patroli.';
                }
            } elseif ($action === 'mark_arrived') {
                $res = OpsDispatch::markArrivedOnScene(
                    trim((string)($_POST['tracking_number'] ?? '')),
                    trim((string)($_POST['scene_notes'] ?? 'Tiba di TKP dan melakukan pengamanan.'))
                );

                if ($res['success']) {
                    $message = "Konfirmasi tiba di TKP tercatat. Waktu respons: {$res['response_time_minutes']} menit ({$res['response_time_seconds']} detik).";
                } else {
                    $error = $res['error'] ?? 'Gagal mencatat konfirmasi tiba di TKP.';
                }
            }
        }

        // Ambil panggilan 110 terkini
        $pdo = \App\Config\Database::getConnection();
        $calls = $pdo->query(
            "SELECT d.*, t.incident_summary, t.incident_address, t.current_status, t.created_at, s.name as satker_name
             FROM `ops_dispatch_110` d
             JOIN `ops_global_tracks` t ON d.tracking_number = t.tracking_number
             LEFT JOIN `satkers` s ON t.current_satker_id = s.id
             ORDER BY d.call_received_at DESC LIMIT 30"
        )->fetchAll();

        $stats = OpsDispatch::getResponseStatistics();

        $this->render('oneservice/dispatch', [
            'pageTitle' => 'Triase & CAD 110 Presisi - SIGAP POLRI',
            'calls'     => $calls,
            'stats'     => $stats,
            'message'   => $message,
            'error'     => $error,
            'satkers'   => $this->satkerModel->getAllActive()
        ], 'admin');
    }

    /**
     * Modul 3: Penerimaan SPKT Ramah Korban (No Rejection, Jalur PPA & Proxy Entry)
     */
    public function spkt(): void
    {
        ModuleManager::requireActive('oneservice_spkt');
        $this->requirePersonnel();

        $message = null;
        $error = null;
        $ticketInfo = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrf()) {
                Session::setFlash('error', 'Sesi keamanan berakhir (token CSRF tidak valid). Silakan ulangi.');
                $this->redirect(preg_replace('#^.*?/oneservice#', '/oneservice', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/oneservice'));
            }

            $res = OpsTrack::createTrack([
                'source_channel'     => 'SPKT_LOKET',
                'current_status'     => 'VERIFIED_SPKT',
                'priority_level'     => $_POST['priority_level'] ?? 'P3_ROUTINE',
                'incident_category'  => $_POST['incident_category'] ?? 'KRIMINAL_UMUM',
                'incident_summary'   => trim((string)($_POST['incident_summary'] ?? '')),
                'incident_address'   => trim((string)($_POST['incident_address'] ?? '')),
                'citizen_name'       => trim((string)($_POST['citizen_name'] ?? '')),
                'citizen_nik'        => trim((string)($_POST['citizen_nik'] ?? '')),
                'citizen_phone'      => trim((string)($_POST['citizen_phone'] ?? '')),
                'is_vulnerable_group'=> !empty($_POST['is_vulnerable_group']) ? 1 : 0,
                'is_whistleblower'   => !empty($_POST['is_whistleblower']) ? 1 : 0,
                'current_satker_id'  => (int)($_POST['satker_id'] ?? 1),
                'assigned_officer_nrp' => Auth::user()['nrp'] ?? null
            ]);

            if ($res['success']) {
                $ticketInfo = $res;
                $message = "Laporan berhasil diterima di SPKT (Standar No-Rejection). Nomor Lacak resmi: {$res['tracking_number']}.";

                // Kirim notifikasi SMS/WA resmi ke pelapor
                $smsText = sprintf(
                    "SPKT POLRI: Laporan Anda diterima resmi dengan No. Lacak: %s dan PIN Keamanan: %s. Pantau progres perkara Anda di https://polri.go.id/lacak",
                    $res['tracking_number'],
                    $res['security_pin']
                );
                \App\Core\OpsAdapter::sendCitizenNotice(trim((string)$_POST['citizen_phone']), $smsText);
            } else {
                $error = $res['error'] ?? 'Gagal menerima laporan SPKT.';
            }
        }

        $this->render('oneservice/spkt', [
            'pageTitle'   => 'Penerimaan SPKT Ramah Korban (No-Rejection) - POLRI',
            'message'     => $message,
            'error'       => $error,
            'ticketInfo'  => $ticketInfo,
            'satkers'     => $this->satkerModel->getAllActive()
        ], 'admin');
    }

    /**
     * Modul 4 & Modul 1: Case Management & Penerbitan SP2HP Elektronik
     */
    public function cases(): void
    {
        ModuleManager::requireActive('oneservice_cases');
        $this->requirePersonnel();

        $message = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrf()) {
                Session::setFlash('error', 'Sesi keamanan berakhir (token CSRF tidak valid). Silakan ulangi.');
                $this->redirect(preg_replace('#^.*?/oneservice#', '/oneservice', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/oneservice'));
            }
            $action = $_POST['action'] ?? '';

            if ($action === 'issue_sp2hp') {
                $trackingNumber = trim((string)($_POST['tracking_number'] ?? ''));
                $stage = $_POST['stage_code'] ?? 'A2';
                $title = trim((string)($_POST['title'] ?? 'Perkembangan Penyelidikan'));
                $publicNotes = trim((string)($_POST['public_notes'] ?? ''));
                $internalNotes = trim((string)($_POST['internal_notes'] ?? ''));
                $jpuStatus = $_POST['jpu_status'] ?? null;

                $res = OpsCase::issueSp2hp(
                    $trackingNumber,
                    $stage,
                    $title,
                    $publicNotes,
                    $internalNotes,
                    Auth::user()['nrp'] ?? '91040701',
                    $jpuStatus
                );

                if ($res['success']) {
                    $message = "SP2HP Elektronik {$stage} berhasil diterbitkan dengan QR Hash: " . substr($res['qr_verification_hash'], 0, 12) . "... Notifikasi terkirim ke pelapor.";
                } else {
                    $error = $res['error'] ?? 'Gagal menerbitkan SP2HP.';
                }
            } elseif ($action === 'run_watchdog') {
                $count = OpsTrack::runSlaWatchdog();
                $message = "SLA Watchdog berhasil dijalankan. {$count} perkara yang melewati batas SLA telah ditandai STALLED untuk atensi pimpinan.";
            }
        }

        // Ambil daftar kasus aktif
        $pdo = \App\Config\Database::getConnection();
        $cases = $pdo->query(
            "SELECT t.*, s.name as satker_name, u.name as officer_name,
                    (SELECT COUNT(*) FROM `ops_case_milestones` m WHERE m.tracking_number = t.tracking_number) as milestone_count
             FROM `ops_global_tracks` t
             LEFT JOIN `satkers` s ON t.current_satker_id = s.id
             LEFT JOIN `users` u ON t.assigned_officer_nrp = u.nrp
             ORDER BY t.is_stalled DESC, t.created_at DESC LIMIT 50"
        )->fetchAll();

        $this->render('oneservice/cases', [
            'pageTitle' => 'Manajemen Perkara & SP2HP Elektronik - SIGAP POLRI',
            'cases'     => $cases,
            'message'   => $message,
            'error'     => $error
        ], 'admin');
    }

    /**
     * Modul 6: Aduan Penipuan Daring & Blokir Cepat OJK/Bank
     */
    public function cyber(): void
    {
        ModuleManager::requireActive('oneservice_cyber');
        $message = null;
        $error = null;
        $fraudResult = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrf()) {
                Session::setFlash('error', 'Sesi keamanan berakhir (token CSRF tidak valid). Silakan ulangi.');
                $this->redirect(preg_replace('#^.*?/oneservice#', '/oneservice', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/oneservice'));
            }

            $res = OpsCyber::reportFraud([
                'citizen_name'        => trim((string)($_POST['citizen_name'] ?? '')),
                'citizen_nik'         => trim((string)($_POST['citizen_nik'] ?? '')),
                'citizen_phone'       => trim((string)($_POST['citizen_phone'] ?? '')),
                'bank_name'           => $_POST['bank_name'] ?? 'BANK_BCA',
                'account_number'      => trim((string)($_POST['account_number'] ?? '')),
                'account_holder_name' => trim((string)($_POST['account_holder_name'] ?? '')),
                'fraud_scheme'        => $_POST['fraud_scheme'] ?? 'ONLINE_SHOP',
                'loss_amount'         => (float)($_POST['loss_amount'] ?? 0.0)
            ]);

            if ($res['success']) {
                $fraudResult = $res;
                $message = "Aduan penipuan daring berhasil diproses. Rekening penipu telah diteruskan ke Bank dengan status: {$res['freeze_status']}.";
            } else {
                $error = $res['error'] ?? 'Gagal memproses aduan siber.';
            }
        }

        // Daftar aduan & kluster rekening terlapor (belum terverifikasi) hanya untuk personil
        $frauds = [];
        $clusters = [];
        if (Auth::hasRole(self::PERSONNEL_ROLES)) {
            $pdo = \App\Config\Database::getConnection();
            $frauds = $pdo->query(
                "SELECT f.*, t.incident_summary, t.created_at as reported_at
                 FROM `ops_cyber_fraud` f
                 JOIN `ops_global_tracks` t ON f.tracking_number = t.tracking_number
                 ORDER BY f.created_at DESC LIMIT 30"
            )->fetchAll();
            $clusters = OpsCyber::getSyndicateClusters();
        }

        $this->render('oneservice/cyber', [
            'pageTitle'   => 'Aduan Penipuan Daring & Blokir Cepat Rekening - POLRI',
            'frauds'      => $frauds,
            'clusters'    => $clusters,
            'message'     => $message,
            'error'       => $error,
            'fraudResult' => $fraudResult
        ], Auth::check() ? 'admin' : 'main');
    }

    /**
     * Modul 7: Peta Kerawanan & Catatan Solusi Bhabin
     */
    public function bhabin(): void
    {
        ModuleManager::requireActive('oneservice_bhabin');
        $this->requirePersonnel();

        $message = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrf()) {
                Session::setFlash('error', 'Sesi keamanan berakhir (token CSRF tidak valid). Silakan ulangi.');
                $this->redirect(preg_replace('#^.*?/oneservice#', '/oneservice', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/oneservice'));
            }

            $res = OpsBhabin::recordResolution([
                'bhabin_nrp'        => Auth::user()['nrp'] ?? 'BHABIN-01',
                'village_name'      => trim((string)($_POST['village_name'] ?? '')),
                'satker_id'         => (int)(Auth::user()['satker_id'] ?? 1),
                'conflict_category' => $_POST['conflict_category'] ?? 'Sengketa Warga',
                'parties_involved'  => trim((string)($_POST['parties_involved'] ?? '')),
                'mediation_outcome' => trim((string)($_POST['mediation_outcome'] ?? '')),
                'citizen_phone'     => trim((string)($_POST['citizen_phone'] ?? '-'))
            ]);

            if ($res['success']) {
                $message = $res['message'];
            } else {
                $error = $res['error'];
            }
        }

        $pdo = \App\Config\Database::getConnection();
        $resolutions = $pdo->query(
            "SELECT b.*, s.name as satker_name, u.name as bhabin_name, u.rank as bhabin_rank
             FROM `ops_bhabin_resolutions` b
             LEFT JOIN `satkers` s ON b.satker_id = s.id
             LEFT JOIN `users` u ON b.bhabin_nrp = u.nrp
             ORDER BY b.recorded_at DESC LIMIT 30"
        )->fetchAll();

        $heatmap = OpsBhabin::getKerawananHeatmap();

        $this->render('oneservice/bhabin', [
            'pageTitle'   => 'Peta Kerawanan & Catatan Solusi Bhabinkamtibmas - POLRI',
            'resolutions' => $resolutions,
            'heatmap'     => $heatmap,
            'message'     => $message,
            'error'       => $error
        ], 'admin');
    }

    /**
     * Modul 5: Dumas Terpadu & Peringatan Dini Integritas (Khusus Backoffice Personil)
     */
     public function dumas(): void
     {
         ModuleManager::requireActive('oneservice_dumas');
         $this->requirePersonnel();

         $message = null;
         $error = null;
         $dumasResult = null;

         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             if (!Session::validateCsrf()) {
                 Session::setFlash('error', 'Sesi keamanan berakhir (token CSRF tidak valid). Silakan ulangi.');
                 $this->redirect(preg_replace('#^.*?/oneservice#', '/oneservice', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/oneservice'));
             }

             $res = OpsDumas::reportDumas([
                 'reported_satker_id'     => (int)($_POST['reported_satker_id'] ?? 1),
                 'reported_service_loket' => trim((string)($_POST['reported_service_loket'] ?? 'Loket Layanan')),
                 'allegation_type'        => $_POST['allegation_type'] ?? 'PUNGLI',
                 'summary'                => trim((string)($_POST['summary'] ?? '')),
                 'reporter_name'          => trim((string)($_POST['reporter_name'] ?? 'Whistleblower')),
                 'reporter_nik'           => trim((string)($_POST['reporter_nik'] ?? '')),
                 'reporter_phone'         => trim((string)($_POST['reporter_phone'] ?? '-')),
                 'is_whistleblower'       => !empty($_POST['is_whistleblower']) ? 1 : 0
             ]);

             if ($res['success']) {
                 $dumasResult = $res;
                 $message = $res['message'];
             } else {
                 $error = $res['error'] ?? 'Gagal mendaftarkan aduan Dumas.';
             }
         }

         $anomalies = OpsDumas::detectAnomalies();

         $pdo = \App\Config\Database::getConnection();
         $cases = $pdo->query(
             "SELECT d.*, s.name as satker_name, t.incident_summary, t.created_at as reported_at
              FROM `ops_dumas_cases` d
              JOIN `satkers` s ON d.reported_satker_id = s.id
              JOIN `ops_global_tracks` t ON d.tracking_number = t.tracking_number
              ORDER BY d.created_at DESC LIMIT 30"
         )->fetchAll();

         $this->render('oneservice/dumas', [
             'pageTitle'   => 'Dumas Terpadu & Peringatan Dini Integritas - POLRI',
             'cases'       => $cases,
             'anomalies'   => $anomalies,
             'satkers'     => $this->satkerModel->getAllActive(),
             'message'     => $message,
             'error'       => $error,
             'dumasResult' => $dumasResult
         ], 'admin');
     }

    /**
     * Modul 8: Dashboard Akuntabilitas 100 Hari (Menjawab 6 Pertanyaan Halaman 41)
     */
    public function dashboard(): void
    {
        ModuleManager::requireActive('oneservice_dashboard');
        $report = OpsAnalytics::getAccountabilityReport();
        $isExecutive = Auth::hasRole([\App\Config\Constants::ROLE_EXECUTIVE, \App\Config\Constants::ROLE_SUPERADMIN]);

        $this->render('oneservice/dashboard', [
            'pageTitle'   => 'Dashboard Akuntabilitas 100 Hari: "Satu Nomor, Satu Jejak" - POLRI',
            'report'      => $report,
            'isExecutive' => $isExecutive,
            // Publik melihat angka ringkasan; personil dapat menelusuri hingga detail perkara
            'canDrill'    => Auth::hasRole(self::PERSONNEL_ROLES),
        ], Auth::check() ? 'admin' : 'main');
    }

    /**
     * Drill-down: ringkasan per satker + daftar perkara terfilter (?status, channel, priority,
     * satker, stalled, vulnerable, q, page). Untuk seluruh personil termasuk pimpinan.
     */
    public function daftar(): void
    {
        ModuleManager::requireActive('oneservice_cases');
        $this->requirePersonnel();

        $filters = OpsDrill::filtersFrom($_GET);
        $this->render('oneservice/daftar', [
            'pageTitle' => 'Daftar Perkara Satu Nomor - SIGAP POLRI',
            'filters'   => $filters,
            'pager'     => OpsDrill::paginate($filters, (int)($_GET['page'] ?? 1)),
            'summary'   => OpsDrill::satkerSummary(),
            'readOnly'  => $this->isReadOnly(),
        ], 'admin');
    }

    /**
     * Drill-down: detail lengkap satu nomor lacak (tanpa PIN, khusus personil)
     */
    public function detail(string $trackingNumber = ''): void
    {
        ModuleManager::requireActive('oneservice_cases');
        $this->requirePersonnel();

        $trackingNumber = strtoupper(trim($trackingNumber));
        $data = OpsTracker::isValidNumber($trackingNumber) ? OpsDrill::detail($trackingNumber) : null;
        if ($data === null) {
            Session::setFlash('error', 'Nomor lacak tidak ditemukan.');
            $this->redirect('/oneservice/daftar');
        }

        // Pembukaan halaman detail tercatat di jejak audit UU PDP (data pribadi tetap tersamar)
        OpsPdp::logAccess(
            $trackingNumber,
            (int)$data['track']['current_satker_id'],
            'CASE_DETAIL',
            'Drill-down detail perkara oleh ' . (Auth::user()['role'] ?? 'personil')
        );

        $this->render('oneservice/detail', [
            'pageTitle' => "Detail {$trackingNumber} - Satu Nomor, Satu Jejak",
            'csrf'      => Session::csrfToken(),
            'readOnly'  => $this->isReadOnly(),
        ] + $data, 'admin');
    }

    /**
     * Hanya personil Polri; pimpinan (Kapolri/PJU) tidak dapat mengirim aksi perubahan
     */
    private function requirePersonnel(): void
    {
        Auth::requireRole(self::PERSONNEL_ROLES);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $this->isReadOnly()) {
            Session::setFlash('error', 'Mode pantau pimpinan: data dapat dilihat hingga detail, perubahan dilakukan oleh satuan pelaksana.');
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/oneservice';
            $this->redirect(preg_replace('#^.*?/oneservice#', '/oneservice', $path));
        }
    }

    private function isReadOnly(): bool
    {
        return in_array(Auth::role(), self::READ_ONLY_ROLES, true);
    }

    /**
     * Endpoint API Aman: Buka Masking NIK (UU PDP Dual-Control Audit)
     */
    public function apiRevealPii(): void
    {
        if (!Auth::hasRole(self::PERSONNEL_ROLES)) {
            $this->json(['success' => false, 'error' => 'Akses ditolak'], 403);
            return;
        }
        if (!Session::validateCsrf()) {
            $this->json(['success' => false, 'error' => 'Token CSRF tidak valid'], 403);
            return;
        }

        $trackingNumber = trim((string)($_POST['tracking_number'] ?? ''));
        $justification = trim((string)($_POST['justification'] ?? 'Pemeriksaan berkas resmi'));

        if ($trackingNumber === '') {
            $this->json(['success' => false, 'error' => 'Nomor lacak wajib disertakan'], 400);
            return;
        }

        $case = OpsTrack::findByTrackingNumber($trackingNumber);
        if (!$case) {
            $this->json(['success' => false, 'error' => 'Kasus tidak ditemukan'], 404);
            return;
        }

        // Catat ke ops_audit_pdp_logs
        OpsPdp::logAccess(
            $trackingNumber,
            (int)$case['current_satker_id'],
            'NIK_REVEAL',
            $justification
        );

        $this->json([
            'success'       => true,
            'tracking_number' => $trackingNumber,
            'citizen_nik'   => $case['citizen_nik_decrypted'] ?? '-',
            'citizen_name'  => $case['citizen_name'],
            'citizen_phone' => $case['citizen_phone']
        ]);
    }
}
