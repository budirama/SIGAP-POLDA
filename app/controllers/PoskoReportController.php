<?php
/**
 * SIGAP-POLRI: Laporan Posko (Tahap 2 — input, draft, pratinjau, lampiran & pengiriman)
 * Route: /posko-laporan/*
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\PoskoAccess;
use App\Core\PoskoSchedule;
use App\Core\Session;
use App\Core\Upload;
use App\Config\Constants;
use App\Models\PoskoNotification;
use App\Models\PoskoRecap;
use App\Models\PoskoReport;
use App\Models\PoskoReportType;
use App\Models\Satker;

class PoskoReportController extends Controller
{
    private const TEXT_MAX = 5000;

    private PoskoReport $reports;
    private PoskoReportType $types;

    public function __construct()
    {
        Auth::requireRole(array_merge(Constants::POSKO_ROLES, [Constants::ROLE_SUPERADMIN]));
        if (PoskoAccess::mustChangePassword()) {
            Session::setFlash('error', 'Demi keamanan, ganti kata sandi sementara Anda terlebih dahulu.');
            $this->redirect('/posko/password');
        }
        $this->reports = new PoskoReport();
        $this->types = new PoskoReportType();
    }

    /**
     * Daftar laporan dalam wilayah pengguna (P2-07)
     */
    public function index(): void
    {
        $filters = [
            'type'   => (int)$this->get('type', 0),
            'status' => (string)$this->get('status', ''),
            'satker' => (int)$this->get('satker', 0),
            'month'  => (string)$this->get('month', ''),
            'search' => trim((string)$this->get('search', '')),
            'kind'   => (string)$this->get('kind', ''),
        ];
        $scope = PoskoAccess::scopeSatkerIds();

        $this->render('posko/reports', [
            'pageTitle' => 'Laporan Posko',
            'pager'     => $this->reports->paginate($scope, PoskoAccess::unitId(), $filters, (int)$this->get('page', 1)),
            'filters'   => $filters,
            'types'     => $this->types->all(),
            'units'     => count($scope) > 1 ? $this->unitOptions($scope) : [],
            'canCreate' => $this->canCreate(),
        ], 'admin');
    }

    /**
     * Pilih jenis laporan lalu isi formulir (P2-04)
     */
    public function create(): void
    {
        $this->requireCreator();
        $level = (int)PoskoAccess::level();
        $available = $this->types->forLevel($level);
        $typeId = (int)$this->get('type', 0);
        $type = $typeId ? $this->types->find($typeId) : null;

        if (!$type || !in_array($typeId, array_map('intval', array_column($available, 'id')), true)) {
            $this->render('posko/report_choose', [
                'pageTitle' => 'Buat Laporan Posko',
                'types'     => $available,
            ], 'admin');
            return;
        }

        $this->renderForm($type, null, Session::get('_posko_old', []) ?: [
            'period_date' => date('Y-m-d'),
            'period_month' => date('Y-m'),
        ]);
        Session::remove('_posko_old');
    }

    public function edit(int $id): void
    {
        $report = $this->editableReport($id);
        if ((int)$report['is_recap']) {
            $this->redirect("/posko-rekap/edit/{$id}");
        }
        $type = $this->types->find((int)$report['report_type_id']);
        $old = Session::get('_posko_old', []);
        Session::remove('_posko_old');
        $this->renderForm($type, $report, $old ?: $this->inputFromReport($report, $type));
    }

    /**
     * Simpan draft (baru atau perubahan). Draft boleh belum lengkap, tetapi isian yang diisi harus valid.
     */
    public function save(): void
    {
        $this->requirePost('/posko-laporan');
        $id = (int)$this->post('id', 0);
        $report = $id ? $this->editableReport($id) : null;
        if (!$report) {
            $this->requireCreator();
        } elseif ((int)$report['is_recap']) {
            Session::setFlash('error', 'Rekap diubah dari halaman rekap; nilai agregat dihitung otomatis.');
            $this->redirect("/posko-rekap/edit/{$id}");
        }

        $typeId = $report ? (int)$report['report_type_id'] : (int)$this->post('report_type_id', 0);
        $type = $this->types->find($typeId);
        $allowed = array_map('intval', array_column($this->types->forLevel((int)PoskoAccess::level()), 'id'));
        if (!$type || (!$report && !in_array($typeId, $allowed, true))) {
            Session::setFlash('error', 'Jenis laporan tidak tersedia untuk tingkat posko Anda.');
            $this->redirect('/posko-laporan/create');
        }

        $satkerId = $report ? (int)$report['satker_id'] : (int)Auth::satkerId();
        [$general, $values, $errors] = $this->validateInput($type, $satkerId, $id, false);
        $back = $report ? "/posko-laporan/edit/{$id}" : "/posko-laporan/create?type={$typeId}";

        if ($errors) {
            Session::set('_posko_old', $_POST);
            Session::setFlash('error', implode(' ', $errors));
            $this->redirect($back);
        }

        if ($report) {
            if (!$this->reports->updateEditable($id, $general, $values, (int)Auth::id())) {
                Session::setFlash('error', 'Laporan tidak lagi dapat diubah (sudah dikirim atau diproses).');
                $this->redirect("/posko-laporan/show/{$id}");
            }
            Audit::log('posko.report.update', null, "Laporan posko #{$id}");
        } else {
            try {
                $id = $this->reports->createDraft($typeId, $satkerId, $general, $values, (int)Auth::id());
            } catch (\PDOException $e) {
                // Pelanggaran unik periode pada penyimpanan bersamaan
                Session::set('_posko_old', $_POST);
                Session::setFlash('error', 'Laporan untuk jenis & periode ini sudah ada.');
                $this->redirect($back);
            }
            $this->reports->logCreate($id, (int)Auth::id(), $satkerId);
            Audit::log('posko.report.create', null, "Laporan posko #{$id} ({$type['code']})");
        }

        $preview = $this->post('next', '') === 'preview';
        Session::setFlash('success', $preview ? 'Draft disimpan. Periksa pratinjau sebelum mengirim.' : 'Draft laporan disimpan.');
        $this->redirect($preview ? "/posko-laporan/show/{$id}" : "/posko-laporan/edit/{$id}");
    }

    /**
     * Detail / pratinjau laporan
     */
    public function show(int $id): void
    {
        $report = $this->viewableReport($id);
        $type = $this->types->find((int)$report['report_type_id']);

        $this->render('posko/report_show', [
            'pageTitle'   => $report['report_number'] ? 'Laporan ' . $report['report_number'] : 'Pratinjau Laporan Posko',
            'report'      => $report,
            'type'        => $type,
            'fields'      => $this->types->fields((int)$type['id'], false),
            'values'      => $this->reports->values($id),
            'attachments' => $this->reports->attachments($id),
            'canEdit'     => $this->canEditReport($report),
            'missing'     => $this->canEditReport($report) ? $this->missingForSubmit($report, $type) : [],
            'history'     => $this->reports->history($id),
            'recapSources' => (int)$report['is_recap'] ? (new PoskoRecap())->sources($id) : [],
            'versionCount' => count($this->reports->versions($id)),
            'actions'     => $this->availableActions($report),
        ], 'admin');
    }

    /**
     * Tampilan cetak resmi dokumen laporan posko dengan kop resmi Polri (P5-03)
     */
    public function print(int $id): void
    {
        $report = $this->viewableReport($id);
        $type = $this->types->find((int)$report['report_type_id']);

        $this->render('posko/report_print', [
            'report'       => $report,
            'type'         => $type,
            'fields'       => $this->types->fields((int)$type['id'], false),
            'values'       => $this->reports->values($id),
            'attachments'  => $this->reports->attachments($id),
            'recapSources' => (int)$report['is_recap'] ? (new PoskoRecap())->sources($id) : [],
        ], 'none');
    }

    /**
     * Alias rute untuk print
     */
    public function printReport(int $id): void
    {
        $this->print($id);
    }

    /**
     * Tampilan cetak daftar/rekapitulasi laporan terfilter (P5-03)
     */
    public function printList(): void
    {
        $filters = [
            'type'   => (int)$this->get('type', 0),
            'status' => (string)$this->get('status', ''),
            'satker' => (int)$this->get('satker', 0),
            'month'  => (string)$this->get('month', ''),
            'search' => trim((string)$this->get('search', '')),
            'kind'   => (string)$this->get('kind', ''),
        ];
        $scope = PoskoAccess::scopeSatkerIds();
        $items = $this->reports->allForExport($scope, PoskoAccess::unitId(), $filters);

        $currentSatker = (new Satker())->findById(PoskoAccess::unitId() ?? 1);
        $satkerName = $currentSatker ? $currentSatker['name'] : 'MABES POLRI';

        $this->render('posko/report_list_print', [
            'items'             => $items,
            'filters'           => $filters,
            'filterDesc'        => $this->describeFilters($filters),
            'currentSatkerName' => $satkerName,
        ], 'none');
    }

    /**
     * Ekspor daftar laporan posko ke format Excel (SpreadsheetML XML 2003) (P5-04)
     * Mengikuti filter & scopeSatkerIds() aktif, mencatat log audit posko.export.
     */
    public function exportExcel(): void
    {
        $filters = [
            'type'   => (int)$this->get('type', 0),
            'status' => (string)$this->get('status', ''),
            'satker' => (int)$this->get('satker', 0),
            'month'  => (string)$this->get('month', ''),
            'search' => trim((string)$this->get('search', '')),
            'kind'   => (string)$this->get('kind', ''),
        ];
        $scope = PoskoAccess::scopeSatkerIds();
        $items = $this->reports->allForExport($scope, PoskoAccess::unitId(), $filters);

        $filterDesc = $this->describeFilters($filters);
        $count = count($items);
        Audit::log('posko.export', null, "Ekspor Excel {$count} baris laporan posko" . ($filterDesc ? " ({$filterDesc})" : ''));

        $filename = 'laporan_posko_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0, no-cache, must-revalidate');
        header('Pragma: public');

        echo $this->generateSpreadsheetXml($items, $filterDesc);
        exit;
    }

    /**
     * Rangkum kriteria filter yang aktif untuk judul cetak & log audit
     */
    private function describeFilters(array $filters): string
    {
        $parts = [];
        if (!empty($filters['type'])) {
            $t = $this->types->find((int)$filters['type']);
            if ($t) $parts[] = 'Jenis: ' . $t['name'];
        }
        if (!empty($filters['status']) && isset(PoskoReport::STATUS_LABELS[$filters['status']])) {
            $parts[] = 'Status: ' . PoskoReport::STATUS_LABELS[$filters['status']];
        }
        if (($filters['kind'] ?? '') === 'recap') {
            $parts[] = 'Bentuk: Rekap';
        } elseif (($filters['kind'] ?? '') === 'nil') {
            $parts[] = 'Bentuk: Nihil';
        } elseif (($filters['kind'] ?? '') === 'source') {
            $parts[] = 'Bentuk: Laporan Sumber';
        }
        if (!empty($filters['satker'])) {
            $s = (new Satker())->findById((int)$filters['satker']);
            if ($s) $parts[] = 'Unit: ' . $s['name'];
        }
        if (!empty($filters['month'])) {
            $parts[] = 'Bulan: ' . $filters['month'];
        }
        if (!empty($filters['search'])) {
            $parts[] = 'Cari: "' . $filters['search'] . '"';
        }
        return implode(', ', $parts);
    }

    /**
     * Bangun dokumen SpreadsheetML 2003 XML standar (kompatibel Excel, LibreOffice, WPS, Google Sheets)
     */
    private function generateSpreadsheetXml(array $items, string $filterDesc): string
    {
        $xmlEscape = fn(?string $s): string => htmlspecialchars((string)$s, ENT_XML1, 'UTF-8');
        $dateNow = date('Y-m-d H:i:s');
        $user = Auth::user();
        $userName = $xmlEscape(($user['rank'] ?? '') . ' ' . ($user['name'] ?? 'Petugas'));

        $rows = [];
        // Header dokumen
        $rows[] = '<Row ss:Height="24"><Cell ss:StyleID="Title" ss:MergeAcross="12"><Data ss:Type="String">KEPOLISIAN NEGARA REPUBLIK INDONESIA - LAPORAN POSKO PRESISI</Data></Cell></Row>';
        $rows[] = '<Row ss:Height="18"><Cell ss:StyleID="SubTitle" ss:MergeAcross="12"><Data ss:Type="String">Tanggal Ekspor: ' . $xmlEscape($dateNow) . ' WIB | Oleh: ' . $userName . ($filterDesc ? ' | Filter: ' . $xmlEscape($filterDesc) : '') . ' | Total: ' . count($items) . ' Data</Data></Cell></Row>';
        $rows[] = '<Row ss:Height="8"/>'; // spasi kosong

        // Header kolom tabel
        $headers = [
            'No', 'Nomor Laporan', 'Satker', 'Tingkat', 'Jenis Laporan', 'Bentuk',
            'Periode / Waktu Kejadian', 'Prioritas', 'Status', 'Tenggat Waktu',
            'Waktu Dikirim', 'Pembuat', 'Pemegang Berkas', 'Versi',
            'Ringkasan Situasi', 'Uraian Kejadian / Kegiatan', 'Kendala',
            'Tindak Lanjut', 'Bantuan Yang Diperlukan', 'Alasan Nihil', 'Tiket Terkait'
        ];
        $headerCells = [];
        foreach ($headers as $h) {
            $headerCells[] = '<Cell ss:StyleID="Header"><Data ss:Type="String">' . $xmlEscape($h) . '</Data></Cell>';
        }
        $rows[] = '<Row ss:Height="22">' . implode('', $headerCells) . '</Row>';

        // Baris data
        $no = 1;
        foreach ($items as $r) {
            $periodeStr = $r['period_start']
                ? PoskoSchedule::label($r['period_unit'], $r['period_start'], $r['period_end'])
                : ($r['event_time'] ? date('d/m/Y H:i', strtotime($r['event_time'])) : '-');

            $bentuk = (int)$r['is_recap'] ? 'Rekap' : ((int)$r['is_nil'] ? 'Nihil' : 'Sumber');
            $statusLabel = PoskoReport::STATUS_LABELS[$r['status']] ?? $r['status'];
            $creator = trim(($r['creator_rank'] ?? '') . ' ' . ($r['creator_name'] ?? '-'));

            $levelLabel = match ((int)$r['satker_level']) {
                Constants::LEVEL_POLSEK => 'Polsek',
                Constants::LEVEL_POLRES => 'Polres',
                Constants::LEVEL_POLDA  => 'Polda',
                Constants::LEVEL_PUSAT  => 'Pusat / Mabes',
                default                 => '-',
            };

            $cells = [
                '<Cell ss:StyleID="CellCenter"><Data ss:Type="Number">' . ($no++) . '</Data></Cell>',
                '<Cell ss:StyleID="CellMono"><Data ss:Type="String">' . $xmlEscape($r['report_number'] ?: ('DRAFT #' . $r['id'])) . '</Data></Cell>',
                '<Cell ss:StyleID="CellBold"><Data ss:Type="String">' . $xmlEscape($r['satker_name']) . '</Data></Cell>',
                '<Cell ss:StyleID="CellCenter"><Data ss:Type="String">' . $xmlEscape($levelLabel) . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($r['type_name']) . '</Data></Cell>',
                '<Cell ss:StyleID="CellCenter"><Data ss:Type="String">' . $xmlEscape($bentuk) . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($periodeStr) . '</Data></Cell>',
                '<Cell ss:StyleID="CellCenter"><Data ss:Type="String">' . $xmlEscape($r['priority']) . '</Data></Cell>',
                '<Cell ss:StyleID="CellCenter"><Data ss:Type="String">' . $xmlEscape($statusLabel) . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($r['deadline_at'] ? date('d/m/Y H:i', strtotime($r['deadline_at'])) : '-') . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($r['submitted_at'] ? date('d/m/Y H:i', strtotime($r['submitted_at'])) : '-') . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($creator) . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($r['holder_name'] ?: '-') . '</Data></Cell>',
                '<Cell ss:StyleID="CellCenter"><Data ss:Type="String">v' . (int)$r['version'] . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($r['situation_summary']) . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($r['description']) . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($r['obstacles']) . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($r['follow_up']) . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape($r['assistance_needed']) . '</Data></Cell>',
                '<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $xmlEscape((int)$r['is_nil'] ? $r['situation_summary'] : '') . '</Data></Cell>',
                '<Cell ss:StyleID="CellMono"><Data ss:Type="String">' . $xmlEscape($r['reference_ticket']) . '</Data></Cell>',
            ];

            $rows[] = '<Row ss:Height="18">' . implode('', $cells) . '</Row>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<?mso-application progid="Excel.Sheet"?>' . "\n"
             . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n"
             . ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n"
             . ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n"
             . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n"
             . ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n"
             . '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">' . "\n"
             . ' <Title>Ekspor Laporan Posko SIGAP-POLRI</Title>' . "\n"
             . ' <Author>SIGAP POLRI</Author>' . "\n"
             . ' <Created>' . date('c') . '</Created>' . "\n"
             . '</DocumentProperties>' . "\n"
             . '<Styles>' . "\n"
             . ' <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#000000"/></Style>' . "\n"
             . ' <Style ss:ID="Title"><Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1" ss:Color="#0F172A"/><Alignment ss:Vertical="Center"/></Style>' . "\n"
             . ' <Style ss:ID="SubTitle"><Font ss:FontName="Calibri" ss:Size="10" ss:Italic="1" ss:Color="#475569"/><Alignment ss:Vertical="Center"/></Style>' . "\n"
             . ' <Style ss:ID="Header"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1E293B" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F172A"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F172A"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F172A"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0F172A"/></Borders></Style>' . "\n"
             . ' <Style ss:ID="Cell"><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#0F172A"/><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>' . "\n"
             . ' <Style ss:ID="CellBold"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>' . "\n"
             . ' <Style ss:ID="CellCenter"><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#0F172A"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>' . "\n"
             . ' <Style ss:ID="CellMono"><Font ss:FontName="Consolas" ss:Size="9" ss:Bold="1" ss:Color="#0F172A"/><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>' . "\n"
             . '</Styles>' . "\n"
             . '<Worksheet ss:Name="Laporan Posko">' . "\n"
             . ' <Table x:FullColumns="1" x:FullRows="1" ss:DefaultRowHeight="16">' . "\n"
             . '  <Column ss:Width="30"/>' . "\n"  // No
             . '  <Column ss:Width="160"/>' . "\n" // Nomor Laporan
             . '  <Column ss:Width="170"/>' . "\n" // Satker
             . '  <Column ss:Width="70"/>' . "\n"  // Tingkat
             . '  <Column ss:Width="150"/>' . "\n" // Jenis Laporan
             . '  <Column ss:Width="65"/>' . "\n"  // Bentuk
             . '  <Column ss:Width="130"/>' . "\n" // Periode
             . '  <Column ss:Width="65"/>' . "\n"  // Prioritas
             . '  <Column ss:Width="90"/>' . "\n"  // Status
             . '  <Column ss:Width="110"/>' . "\n" // Tenggat
             . '  <Column ss:Width="110"/>' . "\n" // Dikirim
             . '  <Column ss:Width="130"/>' . "\n" // Pembuat
             . '  <Column ss:Width="130"/>' . "\n" // Holder
             . '  <Column ss:Width="45"/>' . "\n"  // Versi
             . '  <Column ss:Width="200"/>' . "\n" // Ringkasan Situasi
             . '  <Column ss:Width="200"/>' . "\n" // Uraian
             . '  <Column ss:Width="150"/>' . "\n" // Kendala
             . '  <Column ss:Width="150"/>' . "\n" // Tindak Lanjut
             . '  <Column ss:Width="150"/>' . "\n" // Bantuan
             . '  <Column ss:Width="150"/>' . "\n" // Alasan Nihil
             . '  <Column ss:Width="100"/>' . "\n" // Tiket Terkait
             . implode("\n", $rows) . "\n"
             . ' </Table>' . "\n"
             . '</Worksheet>' . "\n"
             . '</Workbook>';

        return $xml;
    }

    /**
     * Kirim laporan ke tingkat atas: validasi lengkap, nomor laporan & waktu kirim tercatat
     */
    public function submit(): void
    {
        $this->requirePost('/posko-laporan');
        $id = (int)$this->post('id', 0);
        $report = $this->editableReport($id);
        $type = $this->types->find((int)$report['report_type_id']);

        if ((int)$report['is_recap']) {
            $count = (new PoskoRecap())->compute($id);
            if (!$count) {
                Session::setFlash('error', 'Rekap belum memiliki laporan sumber yang disetujui untuk periode ini.');
                $this->redirect("/posko-laporan/show/{$id}");
            }
            $report = $this->reports->find($id);
        }

        $missing = $this->missingForSubmit($report, $type);
        if ($missing) {
            Session::setFlash('error', 'Laporan belum lengkap: ' . implode(', ', $missing) . '.');
            $this->redirect("/posko-laporan/edit/{$id}");
        }

        $number = $this->reports->submit($id, (int)Auth::id(), (int)Auth::satkerId(), $this->parentOf((int)$report['satker_id']));
        if ($number === null) {
            Session::setFlash('error', 'Laporan tidak dapat dikirim pada status saat ini.');
            $this->redirect("/posko-laporan/show/{$id}");
        }

        Audit::log($report['status'] === 'DRAFT' ? 'posko.report.submit' : 'posko.report.resubmit', null, "Laporan posko #{$id} → {$number}");
        $this->notifyAction($id, $report['status'] === 'DRAFT' ? 'submit' : 'resubmit');
        Session::setFlash('success', "Laporan {$number} terkirim ke unit induk untuk diverifikasi.");
        $this->redirect("/posko-laporan/show/{$id}");
    }

    /**
     * Nyatakan nihil (BRD 2.3, 4.5): pernyataan resmi tidak ada kejadian untuk jenis & periode ini.
     * Dibuat dan langsung dikirim ke induk untuk diverifikasi; tidak pernah dibuat otomatis.
     */
    public function nil(): void
    {
        $this->requirePost('/posko-laporan');
        $this->requireCreator();
        $typeId = (int)$this->post('report_type_id', 0);
        $type = $this->types->find($typeId);
        $allowed = array_map('intval', array_column($this->types->forLevel((int)PoskoAccess::level()), 'id'));
        if (!$type || !in_array($typeId, $allowed, true) || !(int)$type['allows_nil'] || $type['period_unit'] === 'NONE') {
            Session::setFlash('error', 'Jenis laporan ini tidak dapat dinyatakan nihil.');
            $this->redirect('/posko-laporan/create');
        }

        $satkerId = (int)Auth::satkerId();
        $reason = mb_substr(trim((string)$this->post('nil_reason', '')), 0, 1000);
        [$general, , $errors] = $this->validateInput($type, $satkerId, 0, false);
        if ($reason === '') {
            $errors[] = 'Alasan/keterangan nihil wajib diisi.';
        }
        if ($errors) {
            Session::setFlash('error', implode(' ', $errors));
            $this->redirect("/posko-laporan/create?type={$typeId}");
        }

        $general = array_merge($general, [
            'region' => Auth::user()['satker_name'] ?? null,
            'situation_summary' => $reason,
            'description' => 'NIHIL — tidak terdapat kejadian/kegiatan yang perlu dilaporkan pada periode ini.',
            'priority' => 'NORMAL',
        ]);
        $id = $this->reports->createDraft($typeId, $satkerId, $general, [], (int)Auth::id(), false, true);
        $this->reports->logCreate($id, (int)Auth::id(), $satkerId);
        $number = $this->reports->submit($id, (int)Auth::id(), $satkerId, $this->parentOf($satkerId));
        Audit::log('posko.report.nil', null, "Laporan nihil #{$id} {$number}");
        $this->notifyAction($id, 'nil');
        Session::setFlash('success', "Pernyataan nihil {$number} terkirim ke unit induk untuk diverifikasi.");
        $this->redirect("/posko-laporan/show/{$id}");
    }

    // ------------------------------------------------------------------ Workflow berjenjang (Tahap 3)

    /**
     * Antrean tindakan: laporan yang sedang dipegang unit pengguna (P3-03)
     */
    public function inbox(): void
    {
        $unit = PoskoAccess::unitId();
        $this->render('posko/inbox', [
            'pageTitle' => 'Antrean Verifikasi Posko',
            'items'     => $unit ? $this->reports->inbox($unit) : [],
            'unitName'  => Auth::user()['satker_name'] ?? '',
        ], 'admin');
    }

    public function startReview(): void
    {
        $report = $this->actionReport('start_review');
        $ok = $this->reports->transition((int)$report['id'], 'start_review', ['SUBMITTED'], 'IN_REVIEW',
            (int)Auth::id(), (int)Auth::satkerId(), ['reviewed_by' => (int)Auth::id()], null, $this->holderGuard());
        $this->finish($ok, $report, 'posko.report.start_review', 'Verifikasi dimulai.');
    }

    public function approve(): void
    {
        $report = $this->actionReport('approve');
        $notes = $this->notes(false);
        $ok = $this->reports->transition((int)$report['id'], 'approve', ['SUBMITTED', 'IN_REVIEW'], 'APPROVED',
            (int)Auth::id(), (int)Auth::satkerId(), ['approved_by' => (int)Auth::id(), 'approved_at' => date('Y-m-d H:i:s')],
            $notes, $this->holderGuard());
        $this->finish($ok, $report, 'posko.report.approve', 'Laporan disetujui. Teruskan ke tingkat atas bila sudah sesuai.');
    }

    /**
     * Kembalikan untuk perbaikan (catatan koreksi wajib) — kembali ke unit pembuat
     */
    public function returnReport(): void
    {
        $report = $this->actionReport('return');
        $notes = $this->notes(true);
        $ok = $this->reports->transition((int)$report['id'], 'return', ['SUBMITTED', 'IN_REVIEW', 'APPROVED', 'FORWARDED'], 'NEEDS_REVISION',
            (int)Auth::id(), (int)Auth::satkerId(),
            ['holder_satker_id' => null, 'approved_by' => null, 'approved_at' => null, 'reviewed_by' => null],
            $notes, $this->holderGuard());
        $this->finish($ok, $report, 'posko.report.return', 'Laporan dikembalikan ke ' . $report['satker_name'] . ' untuk perbaikan.');
    }

    /**
     * Teruskan ke unit induk pemegang saat ini (berjenjang, tidak melompati tingkat) — P3-04
     */
    public function forward(): void
    {
        $report = $this->actionReport('forward');
        $holder = (int)$report['holder_satker_id'];
        $next = $this->parentOf($holder);
        if ($next === $holder) {
            Session::setFlash('error', 'Laporan sudah berada di tingkat tertinggi (Pusat).');
            $this->redirect("/posko-laporan/show/{$report['id']}");
        }

        // Yang meneruskan dianggap telah menerima laporan (eskalasi tercatat per tingkat)
        if ($report['status'] === 'FORWARDED') {
            $this->reports->acknowledge((int)$report['id'], (int)Auth::id(), (int)Auth::satkerId());
        }
        $notes = $this->notes(false);
        $from = $report['status'] === 'FORWARDED' ? ['FORWARDED'] : (
            (int)$report['requires_approval'] ? ['APPROVED'] : ['SUBMITTED', 'IN_REVIEW', 'APPROVED']
        );
        $ok = $this->reports->transition((int)$report['id'], 'forward', $from, 'FORWARDED',
            (int)Auth::id(), (int)Auth::satkerId(), ['holder_satker_id' => $next, 'forwarded_at' => date('Y-m-d H:i:s')],
            $notes, $this->holderGuard());
        $name = (new Satker())->findById($next)['name'] ?? 'unit induk';
        $this->finish($ok, $report, 'posko.report.forward', "Laporan diteruskan ke {$name}.");
    }

    /**
     * Tandai laporan yang diteruskan telah diterima unit ini (penerima eskalasi tercatat)
     */
    public function acknowledge(): void
    {
        $report = $this->actionReport('acknowledge');
        $ok = $this->reports->acknowledge((int)$report['id'], (int)Auth::id(), (int)Auth::satkerId()) ? $report : null;
        $this->finish($ok, $report, 'posko.report.acknowledge', 'Penerimaan laporan dicatat.');
    }

    /**
     * Ajukan revisi atas laporan yang sudah disetujui/diteruskan (P3-05): versi baru, diverifikasi ulang
     */
    public function revise(): void
    {
        $report = $this->actionReport('revise');
        $notes = $this->notes(true);
        $ok = $this->reports->transition((int)$report['id'], 'revise', ['APPROVED', 'FORWARDED'], 'NEEDS_REVISION',
            (int)Auth::id(), (int)Auth::satkerId(),
            fn($row) => ['version' => (int)$row['version'] + 1, 'holder_satker_id' => null,
                         'approved_by' => null, 'approved_at' => null, 'reviewed_by' => null],
            $notes, fn($row) => (int)$row['satker_id'] === Auth::satkerId());
        if ($ok) {
            $flagged = (new PoskoRecap())->flagContaining((int)$report['id']);
            if ($flagged) {
                Audit::log('posko.recap.flag_review', null, 'Rekap #' . implode(', #', $flagged) . " karena revisi laporan #{$report['id']}");
            }
        }
        $this->finish($ok, $report, 'posko.report.revise', 'Revisi dibuka sebagai versi baru. Perbarui laporan lalu kirim ulang untuk diverifikasi.', true);
    }

    /**
     * Batalkan laporan yang belum diproses (alasan wajib). Tidak dihapus; periodenya dilepas.
     */
    public function cancel(): void
    {
        $report = $this->actionReport('cancel');
        $notes = $this->notes(true);
        $ok = $this->reports->transition((int)$report['id'], 'cancel', ['DRAFT', 'NEEDS_REVISION'], 'CANCELLED',
            (int)Auth::id(), (int)Auth::satkerId(),
            ['period_key' => null, 'holder_satker_id' => null, 'cancel_reason' => mb_substr($notes, 0, 500)],
            $notes, fn($row) => (int)$row['satker_id'] === Auth::satkerId());
        $this->finish($ok, $report, 'posko.report.cancel', 'Laporan dibatalkan.');
    }

    /**
     * Perbandingan isi antarversi laporan (P3-05)
     */
    public function versions(int $id): void
    {
        $report = $this->viewableReport($id);
        $type = $this->types->find((int)$report['report_type_id']);
        $this->render('posko/report_versions', [
            'pageTitle' => 'Riwayat Versi ' . ($report['report_number'] ?: 'Laporan'),
            'report'    => $report,
            'versions'  => $this->reports->versions($id),
            'fields'    => $this->types->fields((int)$type['id'], false),
        ], 'admin');
    }

    /**
     * Aksi workflow yang tersedia untuk pengguna atas laporan ini (dipakai tampilan & penjaga server)
     *
     * @return string[]
     */
    private function availableActions(array $report): array
    {
        if (!PoskoAccess::isOperational()) {
            return [];
        }
        $unit = (int)Auth::satkerId();
        $status = $report['status'];
        $isHolder = (int)$report['holder_satker_id'] === $unit && $report['holder_satker_id'] !== null;
        $isDirectParent = $isHolder && ((int)$report['satker_parent_id'] === $unit || (int)$report['satker_id'] === $unit);
        $isOwner = (int)$report['satker_id'] === $unit;
        $hasParent = $isHolder && $report['holder_parent_id'] !== null;

        $actions = [];
        if ($isDirectParent && $status === 'SUBMITTED') {
            $actions[] = 'start_review';
        }
        if ($isDirectParent && in_array($status, ['SUBMITTED', 'IN_REVIEW'], true)) {
            $actions[] = 'approve';
        }
        if ($isHolder && in_array($status, ['SUBMITTED', 'IN_REVIEW', 'APPROVED', 'FORWARDED'], true)) {
            $actions[] = 'return';
        }
        if ($hasParent && ($status === 'APPROVED' || $status === 'FORWARDED'
                || ($isDirectParent && !(int)$report['requires_approval'] && in_array($status, ['SUBMITTED', 'IN_REVIEW'], true)))) {
            $actions[] = 'forward';
        }
        if ($isHolder && $status === 'FORWARDED'
                && !$this->reports->acknowledgedBy((int)$report['id'], $unit, (int)$report['version'])) {
            $actions[] = 'acknowledge';
        }
        if ($isOwner && in_array($status, ['APPROVED', 'FORWARDED'], true)) {
            $actions[] = 'revise';
        }
        if ($isOwner && in_array($status, ['DRAFT', 'NEEDS_REVISION'], true)) {
            $actions[] = 'cancel';
        }
        return $actions;
    }

    /**
     * POST + CSRF + laporan dapat dilihat + aksi tersedia untuk pengguna
     */
    private function actionReport(string $action): array
    {
        $this->requirePost('/posko-laporan');
        $report = $this->viewableReport((int)$this->post('id', 0));
        if (!in_array($action, $this->availableActions($report), true)) {
            Session::setFlash('error', 'Tindakan tidak tersedia untuk laporan ini pada status saat ini atau di luar kewenangan unit Anda.');
            $this->redirect("/posko-laporan/show/{$report['id']}");
        }
        return $report;
    }

    /**
     * Penjaga atomik: pemegang laporan masih unit pengguna ketika baris dikunci
     */
    private function holderGuard(): callable
    {
        $unit = (int)Auth::satkerId();
        return fn($row) => (int)$row['holder_satker_id'] === $unit;
    }

    private function notes(bool $required): ?string
    {
        $notes = mb_substr(trim((string)$this->post('notes', '')), 0, 2000);
        if ($required && $notes === '') {
            Session::setFlash('error', 'Catatan/alasan wajib diisi untuk tindakan ini.');
            $this->redirect('/posko-laporan/show/' . (int)$this->post('id', 0));
        }
        return $notes !== '' ? $notes : null;
    }

    private function finish(?array $ok, array $report, string $audit, string $message, bool $toEdit = false): never
    {
        $id = (int)$report['id'];
        if (!$ok) {
            Session::setFlash('error', 'Tindakan gagal: status laporan sudah berubah. Muat ulang halaman.');
            $this->redirect("/posko-laporan/show/{$id}");
        }
        Audit::log($audit, null, 'Laporan posko #' . $id . ($report['report_number'] ? " {$report['report_number']}" : ''));
        $action = substr($audit, strrpos($audit, '.') + 1);
        if (in_array($action, ['approve', 'return', 'forward'], true)) {
            $this->notifyAction($id, $action);
        }
        Session::setFlash('success', $message);
        $this->redirect($toEdit ? "/posko-laporan/edit/{$id}" : "/posko-laporan/show/{$id}");
    }

    /**
     * Notifikasi bagi pihak berwenang atas laporan setelah aksi (kegagalan notifikasi tidak boleh menggagalkan aksi)
     */
    private function notifyAction(int $id, string $action): void
    {
        try {
            $fresh = $this->reports->find($id);
            if ($fresh) {
                (new PoskoNotification())->onAction($fresh, $action, (int)Auth::id());
            }
        } catch (\Throwable $e) {
            error_log('Notifikasi posko gagal: ' . $e->getMessage());
        }
    }

    /**
     * Unit induk langsung; unit tertinggi (Pusat) memegang laporannya sendiri
     */
    private function parentOf(int $satkerId): int
    {
        $unit = (new Satker())->findById($satkerId);
        return $unit && $unit['parent_id'] !== null ? (int)$unit['parent_id'] : $satkerId;
    }

    // ------------------------------------------------------------------ Lampiran

    public function upload(): void
    {
        $this->requirePost('/posko-laporan');
        $id = (int)$this->post('id', 0);
        $this->editableReport($id);

        $files = $_FILES['files'] ?? null;
        $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
        if ($count === 0 || ($count === 1 && $files['name'][0] === '')) {
            Session::setFlash('error', 'Pilih berkas yang akan dilampirkan.');
            $this->redirect("/posko-laporan/edit/{$id}");
        }
        if ($this->reports->countAttachments($id) + $count > PoskoReport::MAX_ATTACHMENTS) {
            Session::setFlash('error', 'Maksimal ' . PoskoReport::MAX_ATTACHMENTS . ' lampiran per laporan.');
            $this->redirect("/posko-laporan/edit/{$id}");
        }

        $saved = 0;
        $rejected = 0;
        for ($i = 0; $i < $count; $i++) {
            $stored = Upload::store($files['tmp_name'][$i], (int)$files['error'][$i], (int)$files['size'][$i], array_keys(Upload::MIME_EXTENSIONS));
            if ($stored === null) {
                $rejected++;
                continue;
            }
            $attId = $this->reports->addAttachment($id, $stored, (string)$files['name'][$i], (int)Auth::id());
            Audit::log('posko.attachment.upload', null, "Lampiran #{$attId} laporan posko #{$id}");
            $saved++;
        }

        if ($saved) {
            Session::setFlash('success', "{$saved} lampiran tersimpan.");
        }
        if ($rejected) {
            Session::setFlash('error', "{$rejected} berkas ditolak (format JPG/PNG/WEBP/PDF/MP4, maksimal 20 MB).");
        }
        $this->redirect("/posko-laporan/edit/{$id}");
    }

    public function attachment(int $id): void
    {
        $att = $this->reports->findAttachment($id);
        $report = $att ? $this->reports->find((int)$att['report_id']) : null;
        if (!$report || !$this->canViewReport($report)) {
            $this->notFound();
        }
        Audit::log('posko.attachment.download', null, "Lampiran #{$id} laporan posko #{$report['id']}");
        Upload::stream($att['file_path'], $att['file_name_original'] ?: basename($att['file_path']));
        $this->notFound();
    }

    public function deleteAttachment(): void
    {
        $this->requirePost('/posko-laporan');
        $att = $this->reports->findAttachment((int)$this->post('attachment_id', 0));
        if (!$att) {
            $this->redirect('/posko-laporan');
        }
        $this->editableReport((int)$att['report_id']);

        $this->reports->deleteAttachment((int)$att['id']);
        @unlink(Upload::absolutePath($att['file_path']));
        Audit::log('posko.attachment.delete', null, "Lampiran #{$att['id']} laporan posko #{$att['report_id']}");
        Session::setFlash('success', 'Lampiran dihapus.');
        $this->redirect("/posko-laporan/edit/{$att['report_id']}");
    }

    // ------------------------------------------------------------------ Validasi

    /**
     * Baca & validasi input formulir. $strict = aturan kirim (wajib lengkap); draft hanya memeriksa format.
     *
     * @return array{0: array, 1: array, 2: string[]}
     */
    private function validateInput(array $type, int $satkerId, int $reportId, bool $strict): array
    {
        $errors = [];
        $general = [
            'region'            => mb_substr(trim((string)$this->post('region', '')), 0, 255),
            'situation_summary' => mb_substr(trim((string)$this->post('situation_summary', '')), 0, self::TEXT_MAX),
            'description'       => mb_substr(trim((string)$this->post('description', '')), 0, self::TEXT_MAX),
            'obstacles'         => mb_substr(trim((string)$this->post('obstacles', '')), 0, self::TEXT_MAX),
            'follow_up'         => mb_substr(trim((string)$this->post('follow_up', '')), 0, self::TEXT_MAX),
            'assistance_needed' => mb_substr(trim((string)$this->post('assistance_needed', '')), 0, self::TEXT_MAX),
            'priority'          => (string)$this->post('priority', 'NORMAL'),
            'reference_ticket'  => strtoupper(trim((string)$this->post('reference_ticket', ''))),
        ];
        if (!in_array($general['priority'], ['NORMAL', 'HIGH', 'CRITICAL'], true)) {
            $errors[] = 'Prioritas tidak valid.';
        }
        if ($general['reference_ticket'] !== '' && !preg_match('/^LP-[A-Z]+-\d{6}-\d{4}$/', $general['reference_ticket'])) {
            $errors[] = 'Format nomor tiket aduan rujukan tidak valid (contoh LP-POLRI-202610-0001).';
        }

        // Periode atau waktu kejadian
        $today = date('Y-m-d');
        if ($type['period_unit'] === 'NONE') {
            $raw = trim((string)$this->post('event_time', ''));
            $ts = $raw !== '' ? strtotime($raw) : false;
            if ($raw !== '' && $ts === false) {
                $errors[] = 'Waktu kejadian tidak valid.';
            } elseif ($ts !== false && $ts > time() + 300) {
                $errors[] = 'Waktu kejadian tidak boleh di masa depan.';
            }
            $general['event_time'] = $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
            $general['period_start'] = $general['period_end'] = null;
            $general['deadline_at'] = PoskoSchedule::deadline($type, null, $general['event_time']);
        } else {
            $raw = $type['period_unit'] === 'MONTH'
                ? trim((string)$this->post('period_month', '')) . '-01'
                : trim((string)$this->post('period_date', ''));
            $date = \DateTime::createFromFormat('!Y-m-d', $raw);
            if (!$date || $date->format('Y-m-d') !== $raw) {
                $errors[] = 'Periode laporan tidak valid.';
                $period = null;
            } else {
                $period = PoskoSchedule::periodFor($type['period_unit'], $raw);
                if ($period['start'] > $today) {
                    $errors[] = 'Periode laporan belum dimulai.';
                } elseif ($this->reports->existsForPeriod($satkerId, (int)$type['id'], $period['start'], $reportId)) {
                    $errors[] = 'Unit Anda sudah memiliki laporan ' . $type['name'] . ' untuk periode '
                        . PoskoSchedule::label($type['period_unit'], $period['start'], $period['end']) . '.';
                }
            }
            $general['period_start'] = $period['start'] ?? null;
            $general['period_end'] = $period['end'] ?? null;
            $general['event_time'] = null;
            $general['deadline_at'] = $period ? PoskoSchedule::deadline($type, $period['end'], null) : null;
        }

        // Isian dinamis
        $values = [];
        $input = (array)($this->post('values', []) ?: []);
        foreach ($this->types->fields((int)$type['id']) as $f) {
            $raw = trim((string)($input[$f['field_key']] ?? ''));
            $label = $f['label'];
            if ($raw === '') {
                if ($strict && (int)$f['required']) {
                    $errors[] = "{$label} wajib diisi.";
                }
                continue;
            }
            switch ($f['type']) {
                case 'number':
                    // Nilai input type="number" selalu memakai titik sebagai pemisah desimal
                    $normalized = $raw;
                    if (!preg_match('/^\d{1,16}(\.\d{1,2})?$/', $normalized)) {
                        $errors[] = "{$label} harus berupa angka tidak negatif.";
                        continue 2;
                    }
                    $values[$f['field_key']] = ['number' => $normalized, 'text' => null];
                    break;
                case 'select':
                    if (!in_array($raw, PoskoReportType::optionsOf($f), true)) {
                        $errors[] = "Pilihan {$label} tidak valid.";
                        continue 2;
                    }
                    $values[$f['field_key']] = ['number' => null, 'text' => $raw];
                    break;
                case 'date':
                    $d = \DateTime::createFromFormat('!Y-m-d', $raw);
                    if (!$d || $d->format('Y-m-d') !== $raw) {
                        $errors[] = "{$label} harus berupa tanggal yang valid.";
                        continue 2;
                    }
                    $values[$f['field_key']] = ['number' => null, 'text' => $raw];
                    break;
                default:
                    $values[$f['field_key']] = ['number' => null, 'text' => mb_substr($raw, 0, $f['type'] === 'text' ? 255 : self::TEXT_MAX)];
            }
        }

        return [$general, $values, $errors];
    }

    /**
     * Daftar isian yang masih kurang untuk dikirim (berdasarkan data tersimpan)
     *
     * @return string[]
     */
    private function missingForSubmit(array $report, array $type): array
    {
        if ((int)$report['is_nil']) {
            return trim((string)$report['situation_summary']) === '' ? ['alasan nihil'] : [];
        }
        if ((int)$report['is_recap']) {
            $missing = [];
            foreach (['situation_summary' => 'ringkasan situasi wilayah', 'description' => 'analisis/narasi rekap'] as $col => $label) {
                if (trim((string)$report[$col]) === '') {
                    $missing[] = $label;
                }
            }
            return $missing;
        }
        $missing = [];
        if ($type['period_unit'] === 'NONE' && empty($report['event_time'])) {
            $missing[] = 'waktu kejadian';
        }
        if ($type['period_unit'] !== 'NONE' && empty($report['period_start'])) {
            $missing[] = 'periode laporan';
        }
        foreach (['region' => 'wilayah kejadian', 'situation_summary' => 'ringkasan situasi', 'description' => 'uraian kegiatan/kejadian'] as $col => $label) {
            if (trim((string)$report[$col]) === '') {
                $missing[] = $label;
            }
        }
        $values = $this->reports->values((int)$report['id']);
        foreach ($this->types->fields((int)$type['id']) as $f) {
            if ((int)$f['required'] && !isset($values[$f['field_key']])) {
                $missing[] = mb_strtolower($f['label']);
            }
        }
        return $missing;
    }

    // ------------------------------------------------------------------ Akses

    private function canCreate(): bool
    {
        return PoskoAccess::isOperational() && Auth::satkerId() !== null;
    }

    private function canViewReport(array $report): bool
    {
        if (!PoskoAccess::canViewUnit((int)$report['satker_id'])) {
            return false;
        }
        // Draft (termasuk yang dibatalkan sebelum pernah dikirim) hanya terlihat oleh unit pembuatnya
        return $report['report_number'] !== null || (int)$report['satker_id'] === Auth::satkerId();
    }

    private function canEditReport(array $report): bool
    {
        return in_array($report['status'], PoskoReport::EDITABLE_STATUSES, true)
            && PoskoAccess::canEditForUnit((int)$report['satker_id']);
    }

    private function viewableReport(int $id): array
    {
        $report = $this->reports->find($id);
        if (!$report || !$this->canViewReport($report)) {
            Session::setFlash('error', 'Laporan tidak ditemukan atau di luar wilayah kewenangan Anda.');
            $this->redirect('/posko-laporan');
        }
        return $report;
    }

    private function editableReport(int $id): array
    {
        $report = $this->viewableReport($id);
        if (!$this->canEditReport($report)) {
            Session::setFlash('error', 'Laporan ini tidak dapat diubah oleh Anda pada status saat ini.');
            $this->redirect("/posko-laporan/show/{$id}");
        }
        return $report;
    }

    private function requireCreator(): void
    {
        if (!$this->canCreate()) {
            Session::setFlash('error', 'Hanya Operator/Admin Posko yang dapat membuat laporan untuk unitnya.');
            $this->redirect('/posko-laporan');
        }
    }

    private function requirePost(string $back): void
    {
        if (!$this->isPost()) {
            $this->redirect($back);
        }
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect($back);
        }
    }

    private function notFound(): never
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'File tidak ditemukan.';
        exit;
    }

    // ------------------------------------------------------------------ Tampilan

    private function renderForm(array $type, ?array $report, array $input): void
    {
        $this->render('posko/report_form', [
            'pageTitle'   => ($report ? 'Ubah' : 'Buat') . ' ' . $type['name'],
            'type'        => $type,
            'report'      => $report,
            'fields'      => $this->types->fields((int)$type['id']),
            'input'       => $input,
            'attachments' => $report ? $this->reports->attachments((int)$report['id']) : [],
            'unitName'    => Auth::user()['satker_name'] ?? '',
            'revisionNote' => $report && $report['status'] === 'NEEDS_REVISION' ? $this->reports->lastNote((int)$report['id'], ['return', 'revise']) : null,
        ], 'admin');
    }

    private function inputFromReport(array $report, array $type): array
    {
        $input = array_intersect_key($report, array_flip([
            'region', 'situation_summary', 'description', 'obstacles', 'follow_up', 'assistance_needed', 'priority', 'reference_ticket',
        ]));
        $input['period_date'] = $report['period_start'] ?? date('Y-m-d');
        $input['period_month'] = $report['period_start'] ? substr($report['period_start'], 0, 7) : date('Y-m');
        $input['event_time'] = $report['event_time'] ? date('Y-m-d\TH:i', strtotime($report['event_time'])) : '';
        $input['values'] = [];
        foreach ($this->reports->values((int)$report['id']) as $key => $v) {
            $input['values'][$key] = $v['value_number'] !== null ? rtrim(rtrim($v['value_number'], '0'), '.') : $v['value_text'];
        }
        return $input;
    }

    private function unitOptions(array $scope): array
    {
        return array_values(array_filter(
            (new Satker())->getAll(),
            fn($s) => in_array((int)$s['id'], $scope, true) && $s['type'] !== 'DIREKTORAT'
        ));
    }
}
