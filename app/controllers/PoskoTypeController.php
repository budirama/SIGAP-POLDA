<?php
/**
 * SIGAP-POLRI: Konfigurasi Jenis Laporan Posko & isiannya (System Administrator & Admin Posko Pusat)
 * Route: /posko-jenis/*
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\PoskoAccess;
use App\Core\Session;
use App\Config\Constants;
use App\Models\PoskoReportType;

class PoskoTypeController extends Controller
{
    private PoskoReportType $types;

    public function __construct()
    {
        Auth::requireRole(array_merge(Constants::POSKO_ROLES, [Constants::ROLE_SUPERADMIN]));
        if (PoskoAccess::mustChangePassword()) {
            $this->redirect('/posko/password');
        }
        if (!PoskoAccess::canManageOrganization()) {
            Session::setFlash('error', 'Hanya System Administrator & Admin Posko Pusat yang dapat mengatur jenis laporan.');
            $this->redirect('/posko');
        }
        $this->types = new PoskoReportType();
    }

    public function index(int $id = 0): void
    {
        $all = $this->types->all();
        $selected = $id ? $this->types->find($id) : null;

        $this->render('posko/types', [
            'pageTitle' => 'Jenis Laporan Posko',
            'types'     => $all,
            'selected'  => $selected,
            'fields'    => $selected ? $this->types->fields($id, false) : [],
        ], 'admin');
    }

    public function saveType(): void
    {
        $this->requirePost();
        $id = (int)$this->post('id', 0);
        if ($id && !$this->types->find($id)) {
            $this->redirect('/posko-jenis');
        }

        $levels = array_values(array_intersect(['1', '2', '3', '4'], (array)$this->post('levels', [])));
        $periodUnit = (string)$this->post('period_unit', '');
        $time = trim((string)$this->post('deadline_time', ''));
        $d = [
            'code'                 => strtoupper(trim((string)$this->post('code', ''))),
            'name'                 => mb_substr(trim((string)$this->post('name', '')), 0, 120),
            'kind'                 => (string)$this->post('kind', ''),
            'period_unit'          => $periodUnit,
            'deadline_offset_days' => (int)$this->post('deadline_offset_days', 0),
            'deadline_time'        => $time !== '' ? $time . (strlen($time) === 5 ? ':00' : '') : null,
            'deadline_hours'       => $this->post('deadline_hours', '') !== '' ? (int)$this->post('deadline_hours') : null,
            'requires_approval'    => $this->post('requires_approval') ? 1 : 0,
            'allows_nil'           => $this->post('allows_nil') ? 1 : 0,
            'applies_to_levels'    => implode(',', $levels),
            'description'          => ($desc = mb_substr(trim((string)$this->post('description', '')), 0, 255)) !== '' ? $desc : null,
            'sort_order'           => (int)$this->post('sort_order', 0),
            'is_active'            => $this->post('is_active') ? 1 : 0,
        ];

        $error = match (true) {
            !preg_match('/^[A-Z0-9-]{3,40}$/', $d['code']) => 'Kode jenis hanya huruf kapital, angka, dan strip (3–40 karakter).',
            $this->types->codeTaken($d['code'], $id) => 'Kode jenis sudah dipakai.',
            $d['name'] === '' => 'Nama jenis wajib diisi.',
            !isset(PoskoReportType::KINDS[$d['kind']]) => 'Kategori jenis tidak valid.',
            !isset(PoskoReportType::PERIOD_UNITS[$periodUnit]) => 'Satuan periode tidak valid.',
            !$levels => 'Pilih minimal satu tingkat posko.',
            $periodUnit !== 'NONE' && ($d['deadline_offset_days'] < 0 || $d['deadline_offset_days'] > 31) => 'Selisih hari tenggat harus 0–31.',
            $periodUnit !== 'NONE' && $d['deadline_time'] !== null && !preg_match('/^([01]\d|2[0-3]):[0-5]\d:00$/', $d['deadline_time']) => 'Jam tenggat tidak valid.',
            $periodUnit === 'NONE' && ($d['deadline_hours'] === null || $d['deadline_hours'] < 1 || $d['deadline_hours'] > 720) => 'Jenis tanpa periode wajib memiliki tenggat 1–720 jam setelah kejadian.',
            default => null,
        };
        if ($error) {
            Session::setFlash('error', $error);
            $this->redirect('/posko-jenis' . ($id ? "/index/{$id}" : ''));
        }

        $savedId = $this->types->saveType($id ?: null, $d);
        Audit::log($id ? 'posko.type.update' : 'posko.type.create', null, "Jenis laporan #{$savedId} {$d['code']}");
        Session::setFlash('success', "Jenis laporan {$d['name']} disimpan.");
        $this->redirect("/posko-jenis/index/{$savedId}");
    }

    public function saveField(): void
    {
        $this->requirePost();
        $typeId = (int)$this->post('report_type_id', 0);
        if (!$this->types->find($typeId)) {
            $this->redirect('/posko-jenis');
        }
        $id = (int)$this->post('id', 0);
        $existing = $id ? $this->types->findField($id) : null;
        if ($id && (!$existing || (int)$existing['report_type_id'] !== $typeId)) {
            $this->redirect("/posko-jenis/index/{$typeId}");
        }

        $d = [
            'field_key'  => $existing['field_key'] ?? strtolower(trim((string)$this->post('field_key', ''))),
            'label'      => mb_substr(trim((string)$this->post('label', '')), 0, 120),
            'type'       => (string)$this->post('type', ''),
            'required'   => $this->post('required') ? 1 : 0,
            'options'    => trim((string)$this->post('options', '')) ?: null,
            'aggregate'  => (string)$this->post('aggregate', 'NONE'),
            'unit_label' => ($u = mb_substr(trim((string)$this->post('unit_label', '')), 0, 30)) !== '' ? $u : null,
            'sort_order' => (int)$this->post('sort_order', 0),
            'is_active'  => $this->post('is_active') ? 1 : 0,
        ];

        $error = match (true) {
            !$existing && !preg_match('/^[a-z][a-z0-9_]{1,49}$/', $d['field_key']) => 'Kunci isian huruf kecil/angka/garis bawah, diawali huruf (2–50 karakter).',
            !$existing && $this->types->fieldKeyTaken($typeId, $d['field_key']) => 'Kunci isian sudah dipakai pada jenis ini.',
            $d['label'] === '' => 'Label isian wajib diisi.',
            !isset(PoskoReportType::FIELD_TYPES[$d['type']]) => 'Tipe isian tidak valid.',
            !isset(PoskoReportType::AGGREGATES[$d['aggregate']]) => 'Cara rekap tidak valid.',
            $d['type'] !== 'number' && $d['aggregate'] !== 'NONE' => 'Hanya isian angka yang dapat direkap (jumlah/rata-rata/maksimum).',
            $d['type'] === 'select' && count(PoskoReportType::optionsOf($d)) < 2 => 'Isian pilihan membutuhkan minimal dua opsi (satu per baris).',
            default => null,
        };
        if ($error) {
            Session::setFlash('error', $error);
            $this->redirect("/posko-jenis/index/{$typeId}");
        }

        $fieldId = $this->types->saveField($id ?: null, $typeId, $d);
        Audit::log($id ? 'posko.type.field_update' : 'posko.type.field_create', null, "Isian #{$fieldId} {$d['field_key']} jenis #{$typeId}");
        Session::setFlash('success', "Isian {$d['label']} disimpan.");
        $this->redirect("/posko-jenis/index/{$typeId}");
    }

    private function requirePost(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/posko-jenis');
        }
        if (!$this->validateCsrf()) {
            Session::setFlash('error', 'Token CSRF tidak valid.');
            $this->redirect('/posko-jenis');
        }
    }
}
