<?php
/**
 * SIGAP-POLRI: File Controller (Unduhan Lampiran Bukti & Klarifikasi dengan Kontrol Akses)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\ReportAccess;
use App\Core\Upload;
use App\Models\Report;

class FileController extends Controller
{
    private Report $reportModel;

    public function __construct()
    {
        $this->reportModel = new Report();
    }

    /**
     * Lampiran bukti laporan (report_attachments)
     */
    public function attachment(int $id): void
    {
        $attachment = $this->reportModel->findAttachment($id);
        $this->authorize($attachment ? (int)$attachment['report_id'] : 0);

        Audit::log('file.download', (int)$attachment['report_id'], 'Lampiran bukti #' . $id);
        $this->stream($attachment['file_path'], $attachment['file_name_original'] ?: basename($attachment['file_path']));
    }

    /**
     * Lampiran pesan klarifikasi (case_messages)
     */
    public function message(int $id): void
    {
        $message = $this->reportModel->findCaseMessage($id);
        if ($message && empty($message['attachment_path'])) {
            $message = null;
        }
        $this->authorize($message ? (int)$message['report_id'] : 0);

        Audit::log('file.download', (int)$message['report_id'], 'Lampiran pesan klarifikasi #' . $id);
        $this->stream($message['attachment_path'], basename($message['attachment_path']));
    }

    /**
     * Respon 404 yang sama untuk "tidak ada" dan "tidak berhak" agar keberadaan file tidak bocor
     */
    private function authorize(int $reportId): void
    {
        $report = $reportId > 0 ? $this->reportModel->findById($reportId) : null;
        if (!$report || !(ReportAccess::officerCanView($report) || ReportAccess::reporterCanView($report))) {
            $this->notFound();
        }
    }

    private function stream(string $storedPath, string $downloadName): never
    {
        $file = Upload::absolutePath($storedPath);
        if (!is_file($file)) {
            $this->notFound();
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        $inline = isset(Upload::MIME_EXTENSIONS[$mime]);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName) ?: 'lampiran';

        header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $safeName . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($file);
        exit;
    }

    private function notFound(): never
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'File tidak ditemukan.';
        exit;
    }
}
