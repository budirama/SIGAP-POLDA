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
        ], 'admin');
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

        $missing = $this->missingForSubmit($report, $type);
        if ($missing) {
            Session::setFlash('error', 'Laporan belum lengkap: ' . implode(', ', $missing) . '.');
            $this->redirect("/posko-laporan/edit/{$id}");
        }

        $number = $this->reports->submit($id, (int)Auth::id());
        if ($number === null) {
            Session::setFlash('error', 'Laporan tidak dapat dikirim pada status saat ini.');
            $this->redirect("/posko-laporan/show/{$id}");
        }

        Audit::log('posko.report.submit', null, "Laporan posko #{$id} → {$number}");
        Session::setFlash('success', "Laporan terkirim dengan nomor {$number}.");
        $this->redirect("/posko-laporan/show/{$id}");
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
        // Draft hanya terlihat oleh unit pembuatnya
        return $report['status'] !== 'DRAFT' || (int)$report['satker_id'] === Auth::satkerId();
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
