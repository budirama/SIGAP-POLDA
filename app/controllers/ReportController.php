<?php
/**
 * SIGAP-POLDA: Report Controller (Pengaduan & Pelacakan Warga)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Core\ReportAccess;
use App\Core\Upload;
use App\Config\Config;
use App\Config\Constants;
use App\Models\Report;
use App\Models\Feedback;
use App\Models\Throttle;

class ReportController extends Controller
{
    private const MAX_PROOF_FILES = 5;

    private Report $reportModel;
private Feedback $feedbackModel;
    private Throttle $throttle;

    public function __construct()
    {
        $this->reportModel = new Report();
$this->feedbackModel = new Feedback();
        $this->throttle = new Throttle();
    }

    /**
     * Halaman Formulir Pengaduan Multi-Step
     */
    public function create(): void
    {
        // Ambil daftar kategori perkara
        $categories = $this->reportModel->getCategories();

        $old = Session::get('_old_input', []);
        Session::remove('_old_input');

        $this->render('public/create_report', [
            'pageTitle'  => 'Formulir Pengaduan Tindak Pidana & Gangguan Kamtibmas',
            'categories' => $categories,
            'authUser'   => Auth::user(),
            'old'        => is_array($old) ? $old : []
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
        $nik = preg_replace('/\s+/', '', (string)$this->post('reporter_nik', ''));
        $phone = $this->post('reporter_phone', '');
        $isAnonymous = (int)$this->post('is_anonymous', 0);
        $categoryId = (int)$this->post('category_id', 0);
        $title = $this->post('title', '');
        $description = $this->post('description', '');
        $incidentDate = $this->post('incident_date', date('Y-m-d'));
        $incidentTime = (string)$this->post('incident_time', '');
        $location = $this->post('incident_location', '');
        $lat = $this->post('latitude', null);
        $lng = $this->post('longitude', null);
        $urgency = $this->post('urgency_level', 'NORMAL');
        if (!in_array($urgency, ['NORMAL', 'HIGH', 'EMERGENCY'], true)) {
            $urgency = 'NORMAL';
        }

        $ip = Throttle::clientIp();
        if ($this->throttle->tooMany('report_ip', $ip)) {
            Session::setFlash('error', 'Batas pengiriman laporan dari jaringan Anda tercapai. Silakan coba lagi dalam '
                . Throttle::windowMinutes('report_ip') . ' menit atau hubungi Call Center 110 untuk keadaan darurat.');
            $this->redirect('/report/create');
        }

        // Validasi server (input disimpan agar formulir terisi kembali bila gagal)
        $error = $this->validateReportInput($nik, $name, $phone, $categoryId, $title, $description, $location, $incidentDate, $incidentTime);
        if ($error !== null) {
            $this->failWithOldInput($error);
        }

        // Laporan hanya ditautkan ke akun warga yang sedang login. NIK yang diketik di formulir
        // publik tidak dipakai untuk membuat/menautkan akun (cegah pelaporan atas nama orang lain).
        $reporterId = (Auth::check() && Auth::role() === Constants::ROLE_CITIZEN) ? Auth::id() : null;

        // Handle File Upload Bukti (disimpan privat, tipe divalidasi dari isi file)
        $attachments = [];
        $rejectedFiles = 0;
        if (!empty($_FILES['proof_files']['name'][0])) {
            $totalFiles = count($_FILES['proof_files']['name']);
            if ($totalFiles > self::MAX_PROOF_FILES) {
                $this->failWithOldInput('Maksimal ' . self::MAX_PROOF_FILES . ' file bukti per laporan.');
            }
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['proof_files']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $stored = Upload::store(
                    $_FILES['proof_files']['tmp_name'][$i],
                    $_FILES['proof_files']['error'][$i],
                    (int)$_FILES['proof_files']['size'][$i],
                    array_keys(Upload::MIME_EXTENSIONS)
                );

                if ($stored === null) {
                    $rejectedFiles++;
                    continue;
                }

                $fileType = match ($stored['mime']) {
                    'video/mp4' => 'VIDEO',
                    'application/pdf' => 'DOCUMENT_PDF',
                    default => 'IMAGE',
                };

                $attachments[] = [
                    'file_type'          => $fileType,
                    'file_path'          => $stored['file_path'],
                    'file_name_original' => mb_substr($_FILES['proof_files']['name'][$i], 0, 200),
                    'file_size_kb'       => (int)round($stored['size'] / 1024),
                    'caption'            => 'Bukti lampiran pelapor'
                ];
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

            $this->throttle->hit('report_ip', $ip);

            // Pelapor yang baru membuat laporan langsung mendapat akses ke tiketnya
            ReportAccess::grantReporter($ticketNumber);

            Session::setFlash('success', 'Laporan Anda berhasil dikirim ke Command Center SPKT Polda!');
            if ($rejectedFiles > 0) {
                Session::setFlash('error', "{$rejectedFiles} file bukti tidak dapat dilampirkan (format harus JPG/PNG/WEBP/PDF/MP4, maksimal 20 MB per file).");
            }
            $this->redirect("/report/success?ticket={$ticketNumber}");
        } catch (\Throwable $e) {
            error_log('[SIGAP-POLDA] Gagal membuat laporan: ' . $e);
            $this->failWithOldInput('Gagal memproses laporan. Silakan coba lagi atau hubungi Call Center 110.');
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
        // Halaman ini menampilkan PIN, hanya untuk pelapor (bukan personil)
        if (!$report || !ReportAccess::reporterCanView($report)) {
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
        // PIN dikirim via POST (bukan URL) agar tidak tercatat di log/riwayat browser
        if ($this->isPost()) {
            $this->unlockTicket();
        }

        $ticketNumber = trim((string)$this->get('ticket', ''));

        $report = null;
        $timeline = [];
        $attachments = [];
        $feedback = null;
        $errorMessage = null;

        if ($ticketNumber !== '') {
            $found = $this->reportModel->findByTicket($ticketNumber);
            if ($found && $this->canAccessReport($found)) {
                $report = $found;
                $timeline = $this->reportModel->getTimeline($report['id'], false); // Hanya catatan publik
                $attachments = $this->reportModel->getAttachments($report['id']);
                $feedback = $this->feedbackModel->getByReportId($report['id']);
                $messages = $this->reportModel->getCaseMessages($report['id']);
            } elseif (!Session::hasFlash('error')) {
                // Pesan sama untuk tiket ada maupun tidak (cegah enumerasi nomor tiket)
                $errorMessage = "Masukkan PIN Keamanan untuk membuka nomor tiket {$ticketNumber}.";
            }
        }

        $this->render('public/track', [
            'pageTitle'    => 'Pelacakan Status Penyelidikan & SP2HP Elektronik',
            'ticketNumber' => $ticketNumber,
            'report'       => $report,
            'timeline'     => $timeline,
            'attachments'  => $attachments,
            'messages'     => $messages ?? [],
            'feedback'     => $feedback,
            'errorMessage' => $errorMessage,
            'isReporter'   => $report !== null && ReportAccess::reporterCanView($report)
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
        // Pesan warga hanya dapat dikirim oleh pelapor (personil memakai workspace kasus)
        if (!$report || !ReportAccess::reporterCanView($report)) {
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

        $attachmentPath = $this->storeClarificationFile();

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
        // SKM hanya dari pelapor, bukan personil yang dinilai
        if (!$report || !ReportAccess::reporterCanView($report)) {
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

        $sp2hp = $this->reportModel->getLatestSp2hp((int)$report['id']);
        if (!$sp2hp || empty($report['assigned_investigator_id'])) {
            Session::setFlash('error', 'Dokumen SP2HP tersedia setelah penyidik menerbitkan Surat Pemberitahuan Perkembangan pertama.');
            $this->redirect('/report/track?ticket=' . urlencode($report['ticket_number']));
        }

        $timeline = $this->reportModel->getTimeline($report['id'], false);

        $this->render('public/sp2hp_print', [
            'pageTitle' => "SP2HP Digital - {$report['ticket_number']}",
            'report'    => $report,
            'timeline'  => $timeline,
            'sp2hp'     => $sp2hp
        ], 'none'); // Print layout tanpa navbar
    }

    /**
     * Hak baca halaman publik laporan (catatan publik): personil yang berwenang atas
     * laporan ini, akun pelapor pemilik laporan, atau sesi yang telah memverifikasi PIN.
     */
    private function canAccessReport(array $report): bool
    {
        return ReportAccess::officerCanView($report) || ReportAccess::reporterCanView($report);
    }

    private function storeClarificationFile(): ?string
    {
        if (empty($_FILES['clarification_file']['name'])) {
            return null;
        }

        $stored = Upload::store(
            $_FILES['clarification_file']['tmp_name'],
            $_FILES['clarification_file']['error'],
            (int)$_FILES['clarification_file']['size'],
            ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
        );

        if ($stored === null) {
            Session::setFlash('error', 'Lampiran tidak dapat dikirim (format harus JPG/PNG/WEBP/PDF, maksimal 20 MB).');
        }

        return $stored['file_path'] ?? null;
    }

    private function isPinLocked(string $ticket, string $ip): bool
    {
        return $this->throttle->tooMany('pin_ticket_ip', "{$ticket}|{$ip}")
            || $this->throttle->tooMany('pin_ip', $ip)
            || $this->throttle->tooMany('pin_ticket', $ticket);
    }

    /**
     * Verifikasi PIN tiket (POST), lalu kembali ke halaman tiket tanpa PIN di URL
     */
    private function unlockTicket(): never
    {
        $ticket = strtoupper(trim((string)$this->post('ticket', '')));
        $pin = trim((string)$this->post('pin', ''));
        $back = '/report/track?ticket=' . urlencode($ticket);

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token keamanan CSRF tidak valid. Silakan coba lagi.');
            $this->redirect($back);
        }
        if ($ticket === '' || $pin === '') {
            Session::setFlash('error', 'Nomor tiket dan PIN Keamanan wajib diisi.');
            $this->redirect($back);
        }

        $report = $this->reportModel->findByTicket($ticket);
        if ($report && $this->canAccessReport($report)) {
            $this->redirect('/report/track?ticket=' . urlencode($report['ticket_number']));
        }

        $ip = Throttle::clientIp();
        if ($this->isPinLocked($ticket, $ip)) {
            Session::setFlash('error', 'Terlalu banyak percobaan PIN yang salah. Silakan coba lagi dalam '
                . Throttle::windowMinutes('pin_ticket_ip') . ' menit.');
            $this->redirect($back);
        }

        if ($report && hash_equals((string)$report['security_pin'], $pin)) {
            $this->throttle->clear('pin_ticket_ip', "{$ticket}|{$ip}");
            ReportAccess::grantReporter($report['ticket_number']);
            $this->redirect('/report/track?ticket=' . urlencode($report['ticket_number']));
        }

        // Tiket tak dikenal & PIN salah diperlakukan sama (pesan & hitungan)
        $this->throttle->hit('pin_ticket_ip', "{$ticket}|{$ip}");
        $this->throttle->hit('pin_ip', $ip);
        $this->throttle->hit('pin_ticket', $ticket);
        Session::setFlash('error', 'Nomor tiket atau PIN Keamanan tidak cocok.');
        $this->redirect($back);
    }

    /**
     * Validasi formulir laporan publik; null bila valid, atau pesan kesalahan
     */
    private function validateReportInput(string $nik, string $name, string $phone, int $categoryId, string $title, string $description, string $location, string $incidentDate, string $incidentTime): ?string
    {
        if ($name === '' || $phone === '' || $categoryId <= 0 || $title === '' || $description === '' || $location === '') {
            return 'Mohon lengkapi seluruh kolom formulir wajib bertanda bintang (*).';
        }
        if (!preg_match('/^\d{16}$/', $nik)) {
            return 'NIK harus terdiri dari 16 digit angka.';
        }
        if (!preg_match('/^\+?\d{9,15}$/', preg_replace('/[\s-]/', '', $phone))) {
            return 'Nomor WhatsApp tidak valid (9-15 digit angka).';
        }
        if (mb_strlen($name) > 150 || mb_strlen($title) > 200 || mb_strlen($description) > 10000 || mb_strlen($location) > 1000) {
            return 'Isian formulir melebihi panjang maksimum yang diizinkan.';
        }
        if (!in_array($categoryId, array_map('intval', array_column($this->reportModel->getCategories(), 'id')), true)) {
            return 'Kategori perkara tidak valid.';
        }
        $date = \DateTime::createFromFormat('!Y-m-d', $incidentDate);
        if (!$date || $date->format('Y-m-d') !== $incidentDate || $incidentDate > date('Y-m-d') || $incidentDate < '2000-01-01') {
            return 'Tanggal kejadian tidak valid.';
        }
        if ($incidentTime !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $incidentTime)) {
            return 'Waktu kejadian tidak valid.';
        }
        return null;
    }

    /**
     * Kembali ke formulir dengan pesan kesalahan; isian teks disimpan agar tidak hilang
     */
    private function failWithOldInput(string $message): never
    {
        $keys = ['reporter_nik', 'reporter_name', 'reporter_phone', 'is_anonymous', 'category_id', 'title', 'description',
                 'incident_date', 'incident_time', 'incident_location', 'latitude', 'longitude', 'urgency_level'];
        $old = [];
        foreach ($keys as $key) {
            if (isset($_POST[$key]) && is_string($_POST[$key])) {
                $old[$key] = mb_substr($_POST[$key], 0, 10000);
            }
        }
        Session::set('_old_input', $old);
        Session::setFlash('error', $message);
        $this->redirect('/report/create');
    }
}
