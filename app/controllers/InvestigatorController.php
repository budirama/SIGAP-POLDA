<?php
/**
 * SIGAP-POLDA: Investigator Controller (Workspace Penyelidikan & Penerbitan SP2HP)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Config\Constants;
use App\Models\Report;

class InvestigatorController extends Controller
{
    private Report $reportModel;

    public function __construct()
    {
        Auth::requireRole([
            Constants::ROLE_INVESTIGATOR,
            Constants::ROLE_SATKER_ADMIN,
            Constants::ROLE_SUPERADMIN
        ]);

        $this->reportModel = new Report();
    }

    /**
     * Daftar Kasus yang Ditangani oleh Penyidik Ini
     */
    public function index(): void
    {
        $myId = Auth::id();
        $isSuper = Auth::role() === Constants::ROLE_SUPERADMIN;

        $reports = $this->reportModel->getFilteredReports([
            'investigator_id' => $isSuper ? null : $myId
        ]);

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
        if (!$report) {
            Session::setFlash('error', 'Data perkara tidak ditemukan.');
            $this->redirect('/investigator');
        }

        // Ambil timeline lengkap (termasuk rahasia internal)
        $timeline = $this->reportModel->getTimeline($id, true);
        $attachments = $this->reportModel->getAttachments($id);
        $messages = $this->reportModel->getCaseMessages($id);

        $this->render('backoffice/case_detail', [
            'pageTitle'   => "Workspace Kasus: {$report['ticket_number']}",
            'report'      => $report,
            'timeline'    => $timeline,
            'attachments' => $attachments,
            'messages'    => $messages
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
        $newStatus = $this->post('status', 'INVESTIGATING');

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

        if ($reportId <= 0 || empty($messageText)) {
            Session::setFlash('error', 'Pesan klarifikasi tidak boleh kosong.');
            $this->redirect("/investigator/detail/{$reportId}");
        }

        $user = Auth::user();
        $senderName = (!empty($user['rank']) ? $user['rank'] . ' ' : '') . $user['name'];

        $attachmentPath = null;
        if (!empty($_FILES['clarification_file']['name']) && $_FILES['clarification_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = \App\Config\Config::getUploadDir();
            $ext = strtolower(pathinfo($_FILES['clarification_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
                $newFileName = 'KLARIFIKASI_POLRI_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['clarification_file']['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $newFileName)) {
                    $attachmentPath = 'uploads/' . $newFileName;
                }
            }
        }

        $this->reportModel->addCaseMessage(
            $reportId,
            'POLICE',
            Auth::id(),
            $senderName,
            $messageText,
            $attachmentPath
        );

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

        $success = $this->reportModel->closeReport($reportId, Auth::id(), $resolutionNotes);

        if ($success) {
            Session::setFlash('success', 'Perkara resmi ditutup (CLOSED). Warga kini dapat memberikan penilaian Survei Kepuasan (SKM).');
        } else {
            Session::setFlash('error', 'Gagal menutup perkara.');
        }

        $this->redirect("/investigator/detail/{$reportId}");
    }
}
