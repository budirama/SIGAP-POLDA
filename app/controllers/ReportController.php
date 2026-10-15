<?php
/**
 * SIGAP-POLDA: Report Controller (Pengaduan & Pelacakan Warga)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Config\Config;
use App\Config\Constants;
use App\Models\Report;
use App\Models\User;
use App\Models\Feedback;
use App\Models\PinAttempt;

class ReportController extends Controller
{
    private Report $reportModel;
    private User $userModel;
    private Feedback $feedbackModel;
    private PinAttempt $pinAttemptModel;

    public function __construct()
    {
        $this->reportModel = new Report();
        $this->userModel = new User();
        $this->feedbackModel = new Feedback();
        $this->pinAttemptModel = new PinAttempt();
    }

    /**
     * Halaman Formulir Pengaduan Multi-Step
     */
    public function create(): void
    {
        // Ambil daftar kategori perkara
        $categories = $this->reportModel->getCategories();

        $this->render('public/create_report', [
            'pageTitle'  => 'Formulir Pengaduan Tindak Pidana & Gangguan Kamtibmas',
            'categories' => $categories,
            'authUser'   => Auth::user()
        ], 'main');
    }

    /**
     * Proses Simpan Laporan Baru
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/report/create');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid. Silakan ulangi pengisian.');
            $this->redirect('/report/create');
        }

        // Ambil data form
        $name = $this->post('reporter_name', '');
        $nik = $this->post('reporter_nik', '');
        $phone = $this->post('reporter_phone', '');
        $isAnonymous = (int)$this->post('is_anonymous', 0);
        $categoryId = (int)$this->post('category_id', 0);
        $title = $this->post('title', '');
        $description = $this->post('description', '');
        $incidentDate = $this->post('incident_date', date('Y-m-d'));
        $incidentTime = $this->post('incident_time', date('H:i:s'));
        $location = $this->post('incident_location', '');
        $lat = $this->post('latitude', null);
        $lng = $this->post('longitude', null);
        $urgency = $this->post('urgency_level', 'NORMAL');

        // Validasi input wajib
        if (empty($name) || empty($phone) || empty($categoryId) || empty($title) || empty($description) || empty($location)) {
            Session::setFlash('error', 'Mohon lengkapi seluruh kolom formulir wajib bertanda bintang (*).');
            $this->redirect('/report/create');
        }

        // Handle akun pelapor
        $reporterId = null;
        if (!empty($nik)) {
            $citizenUser = $this->userModel->findOrCreateCitizen($nik, $name, $phone);
            $reporterId = $citizenUser['id'];
        } elseif (Auth::check()) {
            $reporterId = Auth::id();
        }

        // Handle File Upload Bukti
        $attachments = [];
        if (!empty($_FILES['proof_files']['name'][0])) {
            $uploadDir = Config::getUploadDir();
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $totalFiles = count($_FILES['proof_files']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['proof_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $originalName = $_FILES['proof_files']['name'][$i];
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'pdf'];

                    if (in_array($ext, $allowedExts, true)) {
                        $newFileName = 'BUKTI_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
                        $destination = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

                        if (move_uploaded_file($_FILES['proof_files']['tmp_name'][$i], $destination)) {
                            $fileType = 'IMAGE';
                            if (in_array($ext, ['mp4', 'mov'], true)) $fileType = 'VIDEO';
                            if ($ext === 'pdf') $fileType = 'DOCUMENT_PDF';

                            $attachments[] = [
                                'file_type'          => $fileType,
                                'file_path'          => 'uploads/' . $newFileName,
                                'file_name_original' => $originalName,
                                'file_size_kb'       => (int)round($_FILES['proof_files']['size'][$i] / 1024),
                                'caption'            => 'Bukti lampiran pelapor'
                            ];
                        }
                    }
                }
            }
        }

        try {
            $ticketNumber = $this->reportModel->createReport([
                'source_channel'    => Constants::CHANNEL_WEB,
                'reporter_id'       => $reporterId,
                'is_anonymous'      => $isAnonymous,
                'reporter_name_raw' => $name,
                'reporter_nik_raw'  => $nik,
                'reporter_phone'    => $phone,
                'category_id'       => $categoryId,
                'title'             => $title,
                'description'       => $description,
                'incident_date'     => $incidentDate,
                'incident_time'     => $incidentTime,
                'incident_location' => $location,
                'latitude'          => $lat ? (float)$lat : null,
                'longitude'         => $lng ? (float)$lng : null,
                'urgency_level'     => $urgency,
                'attachments'       => $attachments
            ]);

            // Pelapor yang baru membuat laporan langsung mendapat akses ke tiketnya
            $this->grantReportAccess($ticketNumber);

            Session::setFlash('success', 'Laporan Anda berhasil dikirim ke Command Center SPKT Polda!');
            $this->redirect("/report/success?ticket={$ticketNumber}");
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Gagal memproses laporan: ' . $e->getMessage());
            $this->redirect('/report/create');
        }
    }

    /**
     * Halaman Sukses Pembuatan Laporan
     */
    public function success(): void
    {
        $ticketNumber = $this->get('ticket', '');
        if (empty($ticketNumber)) {
            $this->redirect('/report/create');
        }

        $report = $this->reportModel->findByTicket($ticketNumber);
        // Halaman ini menampilkan PIN, hanya untuk pembuat laporan di sesi yang sama
        if (!$report || !$this->canAccessReport($report)) {
            $this->redirect('/');
        }

        $this->render('public/success', [
            'pageTitle' => 'Laporan Berhasil Terdaftar',
            'report'    => $report
        ], 'main');
    }

    /**
     * Halaman Pelacakan Tiket (Live Ticket Tracker)
     */
    public function track(): void
    {
        $ticketNumber = trim($this->get('ticket', ''));
        $pin = trim($this->get('pin', ''));

        $report = null;
        $timeline = [];
        $attachments = [];
        $feedback = null;
        $errorMessage = null;

        if (!empty($ticketNumber)) {
            $report = $this->reportModel->findByTicket($ticketNumber);
            if ($report) {
                $isLocked = false;
                if ($pin !== '' && !$this->canAccessReport($report)) {
                    $ticket = $report['ticket_number'];
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

                    if ($this->pinAttemptModel->isLocked($ticket, $ip)) {
                        $isLocked = true;
                    } elseif (hash_equals((string)$report['security_pin'], $pin)) {
                        $this->pinAttemptModel->clearFailures($ticket);
                        $this->grantReportAccess($ticket);
                    } else {
                        $this->pinAttemptModel->recordFailure($ticket, $ip);
                    }
                }

                if ($isLocked) {
                    $errorMessage = 'Terlalu banyak percobaan PIN yang salah. Silakan coba lagi dalam '
                        . PinAttempt::WINDOW_MINUTES . ' menit.';
                    $report = null;
                } elseif (!$this->canAccessReport($report)) {
                    $errorMessage = $pin === ''
                        ? "Masukkan PIN Keamanan untuk membuka nomor tiket {$ticketNumber}."
                        : "PIN Keamanan tidak cocok untuk nomor tiket {$ticketNumber}.";
                    $report = null;
                } else {
                    $timeline = $this->reportModel->getTimeline($report['id'], false); // Hanya catatan publik
                    $attachments = $this->reportModel->getAttachments($report['id']);
                    $feedback = $this->feedbackModel->getByReportId($report['id']);
                    $messages = $this->reportModel->getCaseMessages($report['id']);
                }
            } else {
                $errorMessage = "Nomor tiket {$ticketNumber} tidak ditemukan dalam database.";
            }
        }

        $this->render('public/track', [
            'pageTitle'    => 'Pelacakan Status Penyelidikan & SP2HP Elektronik',
            'ticketNumber' => $ticketNumber,
            'pin'          => $pin,
            'report'       => $report,
            'timeline'     => $timeline,
            'attachments'  => $attachments,
            'messages'     => $messages ?? [],
            'feedback'     => $feedback,
            'errorMessage' => $errorMessage
        ], 'main');
    }

    /**
     * Kirim Pesan Klarifikasi Warga ke Penyidik (Polisi Bersama Masyarakat)
     */
    public function sendMessage(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/report/track');
        }

        $ticket = trim($this->post('ticket_number', ''));
        $messageText = trim($this->post('message_text', ''));

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid. Silakan coba lagi.');
            $this->redirect('/report/track?ticket=' . urlencode($ticket));
        }

        if (empty($ticket) || empty($messageText)) {
            Session::setFlash('error', 'Pesan klarifikasi tidak boleh kosong.');
            $this->redirect('/report/track?ticket=' . urlencode($ticket));
        }

        $report = $this->reportModel->findByTicket($ticket);
        if (!$report || !$this->canAccessReport($report)) {
            Session::setFlash('error', 'Laporan tidak ditemukan atau PIN Keamanan belum diverifikasi.');
            $this->redirect('/report/track');
        }
        $ticket = $report['ticket_number'];

        $senderName = $report['reporter_name_raw'] ?? 'Pelapor';
        $senderId = null;
        if (Auth::check()) {
            $senderId = Auth::id();
            $senderName = Auth::user()['name'];
        }

        $attachmentPath = null;
        if (!empty($_FILES['clarification_file']['name']) && $_FILES['clarification_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = Config::getUploadDir();
            $ext = strtolower(pathinfo($_FILES['clarification_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
                $newFileName = 'KLARIFIKASI_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['clarification_file']['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $newFileName)) {
                    $attachmentPath = 'uploads/' . $newFileName;
                }
            }
        }

        $this->reportModel->addCaseMessage(
            $report['id'],
            'CITIZEN',
            $senderId,
            $senderName,
            $messageText,
            $attachmentPath
        );

        Session::setFlash('success', 'Pesan klarifikasi Anda berhasil dikirimkan ke Tim Penyidik Polri.');
        $this->redirect('/report/track?ticket=' . urlencode($ticket) . '#ruang-klarifikasi');
    }

    /**
     * Submit Ulasan Survei Kepuasan Masyarakat (SKM)
     */
    public function submitFeedback(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/report/track');
        }

        $ticket = trim($this->post('ticket_number', ''));

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid. Silakan coba lagi.');
            $this->redirect('/report/track?ticket=' . urlencode($ticket));
        }

        // Laporan diidentifikasi dari tiket yang aksesnya sudah diverifikasi, bukan report_id dari form
        $report = $ticket !== '' ? $this->reportModel->findByTicket($ticket) : null;
        if (!$report || !$this->canAccessReport($report)) {
            Session::setFlash('error', 'Laporan tidak ditemukan atau PIN Keamanan belum diverifikasi.');
            $this->redirect('/report/track');
        }

        if ($report['status'] !== Constants::STATUS_CLOSED) {
            Session::setFlash('error', 'Survei kepuasan hanya dapat diisi setelah perkara dinyatakan selesai.');
            $this->redirect('/report/track?ticket=' . urlencode($report['ticket_number']));
        }

        $clamp = fn(int $v): int => max(1, min(5, $v));
        $rating = $clamp((int)$this->post('rating_stars', 5));
        $speed = $clamp((int)$this->post('speed_score', 5));
        $fairness = $clamp((int)$this->post('fairness_score', 5));
        $comments = $this->post('comments', '');

        $this->feedbackModel->saveFeedback((int)$report['id'], $rating, $speed, $fairness, $comments);
        Session::setFlash('success', 'Terima kasih! Ulasan Survei Kepuasan Masyarakat (SKM) Anda telah kami terima.');

        $this->redirect('/report/track?ticket=' . urlencode($report['ticket_number']));
    }

    /**
     * Cetak / Download Dokumen Resmi SP2HP Elektronik
     */
    public function sp2hpPrint(): void
    {
        $ticket = $this->get('ticket', '');
        if (empty($ticket)) {
            $this->redirect('/report/track');
        }

        $report = $this->reportModel->findByTicket($ticket);
        if (!$report || !$this->canAccessReport($report)) {
            Session::setFlash('error', 'Masukkan PIN Keamanan untuk membuka dokumen SP2HP.');
            $this->redirect('/report/track?ticket=' . urlencode($ticket));
        }

        $timeline = $this->reportModel->getTimeline($report['id'], false);

        $this->render('public/sp2hp_print', [
            'pageTitle' => "SP2HP Digital - {$ticket}",
            'report'    => $report,
            'timeline'  => $timeline
        ], 'none'); // Print layout tanpa navbar
    }

    /**
     * Cek hak akses ke laporan: personil dinas, akun pelapor pemilik laporan,
     * atau sesi yang telah memverifikasi PIN tiket tersebut.
     */
    private function canAccessReport(array $report): bool
    {
        if (Auth::check()) {
            if (Auth::role() !== Constants::ROLE_CITIZEN) {
                return true;
            }
            if (!empty($report['reporter_id']) && (int)$report['reporter_id'] === Auth::id()) {
                return true;
            }
        }

        $granted = Session::get('report_access', []);
        return !empty($granted[$report['ticket_number']]);
    }

    private function grantReportAccess(string $ticketNumber): void
    {
        $granted = Session::get('report_access', []);
        $granted[$ticketNumber] = true;
        Session::set('report_access', $granted);
    }
}
