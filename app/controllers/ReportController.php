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

class ReportController extends Controller
{
    private Report $reportModel;
    private User $userModel;
    private Feedback $feedbackModel;

    public function __construct()
    {
        $this->reportModel = new Report();
        $this->userModel = new User();
        $this->feedbackModel = new Feedback();
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
        if (!$report) {
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
                // Verifikasi PIN hanya jika laporan tidak dibuka oleh personil dinas
                $isOfficer = Auth::check() && Auth::role() !== Constants::ROLE_CITIZEN;
                if (!$isOfficer && !empty($report['security_pin']) && !empty($pin) && $report['security_pin'] !== $pin) {
                    $errorMessage = "PIN Keamanan tidak cocok untuk nomor tiket {$ticketNumber}.";
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
        $this->validateCsrf();
        $ticket = trim($this->post('ticket_number', ''));
        $messageText = trim($this->post('message_text', ''));

        if (empty($ticket) || empty($messageText)) {
            Session::setFlash('error', 'Pesan klarifikasi tidak boleh kosong.');
            $this->redirect("/report/track?ticket={$ticket}");
        }

        $report = $this->reportModel->findByTicket($ticket);
        if (!$report) {
            Session::setFlash('error', 'Laporan tidak ditemukan.');
            $this->redirect('/report/track');
        }

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
        $this->redirect("/report/track?ticket={$ticket}#ruang-klarifikasi");
    }

    /**
     * Submit Ulasan Survei Kepuasan Masyarakat (SKM)
     */
    public function submitFeedback(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/report/track');
        }

        $reportId = (int)$this->post('report_id', 0);
        $ticket = $this->post('ticket_number', '');
        $rating = (int)$this->post('rating_stars', 5);
        $speed = (int)$this->post('speed_score', 5);
        $fairness = (int)$this->post('fairness_score', 5);
        $comments = $this->post('comments', '');

        if ($reportId > 0) {
            $this->feedbackModel->saveFeedback($reportId, $rating, $speed, $fairness, $comments);
            Session::setFlash('success', 'Terima kasih! Ulasan Survei Kepuasan Masyarakat (SKM) Anda telah kami terima.');
        }

        $this->redirect("/report/track?ticket={$ticket}");
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
        if (!$report) {
            $this->redirect('/report/track');
        }

        $timeline = $this->reportModel->getTimeline($report['id'], false);

        $this->render('public/sp2hp_print', [
            'pageTitle' => "SP2HP Digital - {$ticket}",
            'report'    => $report,
            'timeline'  => $timeline
        ], 'none'); // Print layout tanpa navbar
    }
}
