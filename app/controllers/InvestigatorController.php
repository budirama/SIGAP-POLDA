<?php
/**
 * SIGAP-POLDA: Investigator Controller (Workspace Penyelidikan & Penerbitan SP2HP)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\ReportAccess;
use App\Core\Session;
use App\Core\Upload;
use App\Config\Constants;
use App\Models\Report;

class InvestigatorController extends Controller
{
    private Report $reportModel;

    public function __construct()
    {
        // Penangan kasus + pembaca berkas perkara (pimpinan & pengawas, mode baca)
        Auth::requireRole([
            Constants::ROLE_INVESTIGATOR,
            Constants::ROLE_SATKER_ADMIN,
            Constants::ROLE_SATKER_HEAD,
            Constants::ROLE_EXECUTIVE,
            Constants::ROLE_INTERNAL_AFFAIRS,
            Constants::ROLE_SUPERADMIN
        ]);

        $this->reportModel = new Report();
    }

    /**
     * Daftar Kasus yang Ditangani oleh Penyidik Ini
     */
    public function index(): void
    {
        Auth::requireRole([Constants::ROLE_INVESTIGATOR, Constants::ROLE_SATKER_ADMIN, Constants::ROLE_SUPERADMIN], $this->homeUrl());

        // Penyidik: kasus yang ditugaskan kepadanya; Kanit: seluruh kasus Satker-nya
        $filters = match (Auth::role()) {
            Constants::ROLE_INVESTIGATOR => ['investigator_id' => Auth::id()],
            Constants::ROLE_SATKER_ADMIN => ['satker_id' => Auth::satkerId() ?? -1],
            default => [],
        };
        $reports = $this->reportModel->getFilteredReports($filters);

        $this->render('backoffice/investigator_list', [
            'pageTitle' => 'Workspace Kasus Aktif Penyidik',
            'reports'   => $reports
        ], 'admin');
    }

    /**
     * Workspace Lengkap Penanganan Kasus Tertentu
     */
    public function detail(int $id): void
    {
        $report = $this->reportModel->findById($id);
        if (!$report || !ReportAccess::canViewCaseFile($report)) {
            Session::setFlash('error', 'Data perkara tidak ditemukan atau bukan dalam kewenangan Anda.');
            $this->redirect($this->homeUrl());
        }

        Audit::log('case.view', $id, $report['is_anonymous'] ? 'Berkas whistleblower (identitas pelapor dirahasiakan)' : null);

        // Ambil timeline lengkap (termasuk rahasia internal)
        $timeline = $this->reportModel->getTimeline($id, true);
        $attachments = $this->reportModel->getAttachments($id);
        $messages = $this->reportModel->getCaseMessages($id);

        $this->render('backoffice/case_detail', [
            'pageTitle'   => "Workspace Kasus: {$report['ticket_number']}",
            'report'      => $report,
            'timeline'    => $timeline,
            'attachments' => $attachments,
            'messages'    => $messages,
            'canHandle'   => $this->canHandle($report),
            'backUrl'     => $this->homeUrl()
        ], 'admin');
    }

    /**
     * Terbitkan SP2HP Elektronik Baru
     */
    public function updateSp2hp(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/investigator');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/investigator');
        }

        $reportId = (int)$this->post('report_id', 0);
        $sp2hpNumber = $this->post('sp2hp_number', 'SP2HP/' . random_int(100, 999) . '/' . date('m/Y') . '/Ditreskrimum');
        $publicNotes = $this->post('public_notes', '');
        $internalNotes = $this->post('internal_notes', '');
        $newStatus = $this->post('status', Constants::STATUS_INVESTIGATING);

        $report = $this->findAuthorizedReport($reportId);
        $this->requireActiveCase($report);

        if (!in_array($newStatus, [Constants::STATUS_INVESTIGATING, Constants::STATUS_CLOSED], true)) {
            Session::setFlash('error', 'Status perkara tidak valid.');
            $this->redirect("/investigator/detail/{$reportId}");
        }

        if (empty($publicNotes)) {
            Session::setFlash('error', 'Catatan perkembangan resmi (publik) wajib diisi untuk pelapor.');
            $this->redirect("/investigator/detail/{$reportId}");
        }

        $success = $this->reportModel->issueSp2hp(
            $reportId,
            Auth::id(),
            $sp2hpNumber,
            $publicNotes,
            $internalNotes,
            $newStatus
        );

        if ($success) {
            Audit::log('report.sp2hp', $reportId, "{$sp2hpNumber} → {$newStatus}");
            Session::setFlash('success', "SP2HP Elektronik ({$sp2hpNumber}) berhasil diterbitkan dan terkirim ke Pelapor.");
        } else {
            Session::setFlash('error', 'Gagal menerbitkan SP2HP.');
        }

        $this->redirect("/investigator/detail/{$reportId}");
    }

    /**
     * Kirim Pesan Klarifikasi Resmi Penyidik ke Warga Pelapor (Polisi Bersama Masyarakat)
     */
    public function sendMessage(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/investigator');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/investigator');
        }

        $reportId = (int)$this->post('report_id', 0);
        $messageText = trim($this->post('message_text', ''));

        $this->findAuthorizedReport($reportId);

        if (empty($messageText)) {
            Session::setFlash('error', 'Pesan klarifikasi tidak boleh kosong.');
            $this->redirect("/investigator/detail/{$reportId}");
        }

        $user = Auth::user();
        $senderName = (!empty($user['rank']) ? $user['rank'] . ' ' : '') . $user['name'];

        $attachmentPath = null;
        if (!empty($_FILES['clarification_file']['name'])) {
            $stored = Upload::store(
                $_FILES['clarification_file']['tmp_name'],
                $_FILES['clarification_file']['error'],
                (int)$_FILES['clarification_file']['size'],
                ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
            );
            if ($stored === null) {
                Session::setFlash('error', 'Lampiran tidak dapat dikirim (format harus JPG/PNG/WEBP/PDF, maksimal 20 MB).');
                $this->redirect("/investigator/detail/{$reportId}#ruang-klarifikasi");
            }
            $attachmentPath = $stored['file_path'];
        }

        $this->reportModel->addCaseMessage(
            $reportId,
            'POLICE',
            Auth::id(),
            $senderName,
            $messageText,
            $attachmentPath
        );

        Audit::log('case.message', $reportId, $attachmentPath ? 'Pesan dengan lampiran' : null);
        Session::setFlash('success', 'Pesan klarifikasi resmi berhasil terkirim ke Pelapor.');
        $this->redirect("/investigator/detail/{$reportId}#ruang-klarifikasi");
    }

    /**
     * Penutupan Kasus dengan Berita Acara (BAP)
     */
    public function closeCase(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/investigator');
        }

        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/investigator');
        }

        $reportId = (int)$this->post('report_id', 0);
        $resolutionNotes = $this->post('resolution_notes', 'Tersangka dan barang bukti telah diamankan secara lengkap.');

        $report = $this->findAuthorizedReport($reportId);
        $this->requireActiveCase($report);

        $success = $this->reportModel->closeReport($reportId, Auth::id(), $resolutionNotes);

        if ($success) {
            Audit::log('report.close', $reportId);
            Session::setFlash('success', 'Perkara resmi ditutup (CLOSED). Warga kini dapat memberikan penilaian Survei Kepuasan (SKM).');
        } else {
            Session::setFlash('error', 'Gagal menutup perkara.');
        }

        $this->redirect("/investigator/detail/{$reportId}");
    }

    /**
     * Ambil laporan dan pastikan pengguna berwenang menanganinya:
     * Penyidik hanya kasus yang ditugaskan kepadanya, Kanit hanya kasus di Satker-nya.
     */
    private function findAuthorizedReport(int $reportId): array
    {
        $report = $reportId > 0 ? $this->reportModel->findById($reportId) : null;

        if (!$report || !$this->canHandle($report)) {
            Session::setFlash('error', 'Data perkara tidak ditemukan atau bukan dalam kewenangan Anda.');
            $this->redirect($this->homeUrl());
        }

        return $report;
    }

    private function canHandle(array $report): bool
    {
        return match (Auth::role()) {
            Constants::ROLE_SUPERADMIN => true,
            Constants::ROLE_INVESTIGATOR => (int)($report['assigned_investigator_id'] ?? 0) === Auth::id(),
            Constants::ROLE_SATKER_ADMIN => !empty($report['current_satker_id'])
                && (int)$report['current_satker_id'] === Auth::satkerId(),
            default => false,
        };
    }

    /**
     * SP2HP & penutupan hanya untuk perkara yang sedang ditangani penyidik
     */
    private function requireActiveCase(array $report): void
    {
        if (!in_array($report['status'], [Constants::STATUS_ASSIGNED, Constants::STATUS_INVESTIGATING], true)) {
            Session::setFlash('error', 'Perkara ini tidak dalam tahap penyelidikan aktif.');
            $this->redirect("/investigator/detail/{$report['id']}");
        }
    }

    /**
     * Halaman asal sesuai role (tujuan kembali / pengalihan saat akses ditolak)
     */
    private function homeUrl(): string
    {
        return match (Auth::role()) {
            Constants::ROLE_EXECUTIVE => '/executive',
            Constants::ROLE_SATKER_HEAD => '/satker',
            Constants::ROLE_INTERNAL_AFFAIRS => '/spkt',
            default => '/investigator',
        };
    }
}
