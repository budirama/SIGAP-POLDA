<?php
/**
 * SIGAP-POLRI: Ekspor laporan posko ke Excel (.xlsx, PhpSpreadsheet) & PDF (Dompdf) (P5-04, asumsi A11)
 * Pustaka dipasang lewat Composer (`composer install`); bila vendor/ belum ada, available() = false.
 */

namespace App\Core;

use App\Config\Constants;
use App\Models\PoskoReport;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PoskoExport
{
    public const HEADERS = [
        'No', 'Nomor Laporan', 'Satker', 'Tingkat', 'Jenis Laporan', 'Bentuk',
        'Periode / Waktu Kejadian', 'Prioritas', 'Status', 'Tenggat Waktu',
        'Waktu Dikirim', 'Pembuat', 'Pemegang Berkas', 'Versi',
        'Ringkasan Situasi', 'Uraian Kejadian / Kegiatan', 'Kendala',
        'Tindak Lanjut', 'Bantuan Yang Diperlukan', 'Alasan Nihil', 'Tiket Terkait',
    ];

    private const WIDTHS = [5, 26, 30, 12, 26, 10, 24, 11, 16, 17, 17, 24, 24, 7, 40, 40, 30, 30, 30, 30, 18];

    public static function available(): bool
    {
        return class_exists(Spreadsheet::class) && class_exists(Dompdf::class);
    }

    /**
     * Satu baris data per laporan, urut sesuai HEADERS
     */
    public static function rows(array $items): array
    {
        $rows = [];
        foreach ($items as $i => $r) {
            $periode = $r['period_start']
                ? PoskoSchedule::label($r['period_unit'], $r['period_start'], $r['period_end'])
                : ($r['event_time'] ? date('d/m/Y H:i', strtotime($r['event_time'])) : '-');
            $level = match ((int)$r['satker_level']) {
                Constants::LEVEL_POLSEK => 'Polsek',
                Constants::LEVEL_POLRES => 'Polres',
                Constants::LEVEL_POLDA  => 'Polda',
                Constants::LEVEL_PUSAT  => 'Pusat / Mabes',
                default                 => '-',
            };
            $rows[] = [
                $i + 1,
                $r['report_number'] ?: ('DRAFT #' . $r['id']),
                $r['satker_name'],
                $level,
                $r['type_name'],
                (int)$r['is_recap'] ? 'Rekap' : ((int)$r['is_nil'] ? 'Nihil' : 'Sumber'),
                $periode,
                $r['priority'],
                PoskoReport::STATUS_LABELS[$r['status']] ?? $r['status'],
                $r['deadline_at'] ? date('d/m/Y H:i', strtotime($r['deadline_at'])) : '-',
                $r['submitted_at'] ? date('d/m/Y H:i', strtotime($r['submitted_at'])) : '-',
                trim(($r['creator_rank'] ?? '') . ' ' . ($r['creator_name'] ?? '-')),
                $r['holder_name'] ?: '-',
                'v' . (int)$r['version'],
                (string)$r['situation_summary'],
                (string)$r['description'],
                (string)$r['obstacles'],
                (string)$r['follow_up'],
                (string)$r['assistance_needed'],
                (int)$r['is_nil'] ? (string)$r['situation_summary'] : '',
                (string)$r['reference_ticket'],
            ];
        }
        return $rows;
    }

    /**
     * Kirim berkas .xlsx ke browser lalu hentikan request
     */
    public static function sendXlsx(array $items, string $filterDesc, string $exportedBy): never
    {
        $book = new Spreadsheet();
        $book->getProperties()->setCreator('SIGAP POLRI')->setTitle('Ekspor Laporan Posko SIGAP-POLRI');
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Laporan Posko');
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(self::HEADERS));

        $sheet->setCellValue('A1', 'KEPOLISIAN NEGARA REPUBLIK INDONESIA - LAPORAN POSKO PRESISI');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'Tanggal Ekspor: ' . date('Y-m-d H:i:s') . ' WIB | Oleh: ' . $exportedBy
            . ($filterDesc !== '' ? ' | Filter: ' . $filterDesc : '') . ' | Total: ' . count($items) . ' Data');
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('475569');

        $sheet->fromArray(self::HEADERS, null, 'A4');
        $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $rows = self::rows($items);
        if ($rows) {
            // Teks bebas petugas disimpan sebagai string (bukan rumus) agar "=..." tidak dieksekusi Excel
            foreach ($rows as $i => $row) {
                foreach ($row as $c => $value) {
                    $sheet->setCellValueExplicit([$c + 1, $i + 5], $value, is_int($value)
                        ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                        : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
            $last = count($rows) + 4;
            $sheet->getStyle("A4:{$lastCol}{$last}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $sheet->getStyle("A5:{$lastCol}{$last}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        }
        foreach (self::WIDTHS as $i => $w) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
        $sheet->freezePane('C5');
        $sheet->setAutoFilter("A4:{$lastCol}" . max(4, count($rows) + 4));

        $filename = 'laporan_posko_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0, no-cache, must-revalidate');
        (new Xlsx($book))->save('php://output');
        exit;
    }

    /**
     * Render HTML (template pdf_*) menjadi PDF A4 lalu kirim sebagai unduhan
     */
    public static function sendPdf(string $html, string $filename, string $orientation = 'portrait'): never
    {
        $publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
        $options = new Options();
        $options->setIsRemoteEnabled(false);      // tidak mengambil sumber dari internet
        $options->setIsPhpEnabled(false);
        $options->setChroot($publicDir);          // gambar lokal hanya dari public/
        $options->setDefaultFont('DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', $orientation);
        $pdf->render();

        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0, no-cache, must-revalidate');
        echo $pdf->output();
        exit;
    }

    /**
     * Path berkas logo kop untuk <img> di template PDF (di dalam chroot public/)
     */
    public static function logoPath(): string
    {
        return dirname(__DIR__, 2) . '/public/assets/img/logo-polisi-masyarakat-transparent.png';
    }
}
