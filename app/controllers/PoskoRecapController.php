<?php
/**
 * SIGAP-POLRI: Rekapitulasi Berjenjang Posko (Tahap 4 — P4-01/P4-02)
 * Route: /posko-rekap/*
 *
 * Rekap = laporan posko ber-is_recap: nilai agregat otomatis dari laporan sumber,
 * narasi/analisis ditulis petugas; dikirim & diverifikasi lewat workflow yang sama.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\PoskoAccess;
use App\Core\PoskoSchedule;
use App\Core\Session;
use App\Config\Constants;
use App\Models\PoskoRecap;
use App\Models\PoskoReport;
use App\Models\PoskoReportType;

class PoskoRecapController extends Controller
{
    // Tenggat rekap = tenggat laporan sumber + jeda verifikasi unit
    private const RECAP_EXTRA_DAYS = 1;

    private PoskoReport $reports;
    private PoskoReportType $types;
    private PoskoRecap $recaps;

    public function __construct()
    {
        Auth::requireRole(array_merge(Constants::POSKO_ROLES, [Constants::ROLE_SUPERADMIN]));
        if (PoskoAccess::mustChangePassword()) {
            $this->redirect('/posko/password');
        }
        if (!PoskoAccess::isOperational() || (int)PoskoAccess::level() < Constants::LEVEL_POLRES) {
            Session::setFlash('error', 'Rekap disusun oleh Operator/Admin Posko Polres, Polda, atau Pusat untuk unit di bawahnya.');
            $this->redirect('/posko-laporan');
        }
        $this->reports = new PoskoReport();
        $this->types = new PoskoReportType();
        $this->recaps = new PoskoRecap();
    }

    /**
     * Pilih jenis & periode, pratinjau sumber dan nilai agregat sebelum menyimpan rekap
     */
    public function create(): void
    {
        $types = array_values(array_filter($this->types->all(true), fn($t) => $t['period_unit'] !== 'NONE'));
        $type = $this->types->find((int)$this->get('type', 0));
        $period = null;
        $sources = [];
        $values = [];
        $existing = null;

        if ($type && $type['period_unit'] !== 'NONE') {
            $raw = $type['period_unit'] === 'MONTH'
                ? (string)$this->get('period_month', date('Y-m')) . '-01'
                : (string)$this->get('period_date', date('Y-m-d'));
            $d = \DateTime::createFromFormat('!Y-m-d', $raw);
            if ($d && $d->format('Y-m-d') === $raw && $raw <= date('Y-m-d')) {
                $period = PoskoSchedule::periodFor($type['period_unit'], $raw);
                $unit = (int)Auth::satkerId();
                $existing = $this->reports->findRecap($unit, (int)$type['id'], $period['start']);
                $sources = $this->recaps->eligibleSources($unit, (int)$type['id'], $period['start']);
                $values = $this->recaps->aggregate((int)$type['id'], array_column($sources, 'id'));
            }
        }

        $this->render('posko/recap_form', [
            'pageTitle' => 'Susun Rekap Wilayah',
            'types'     => $types,
            'type'      => $type,
            'period'    => $period,
            'sources'   => $sources,
            'values'    => $values,
            'fields'    => $type ? $this->types->fields((int)$type['id']) : [],
            'existing'  => $existing,
            'recap'     => null,
            'input'     => ['period_date' => $this->get('period_date', date('Y-m-d')), 'period_month' => $this->get('period_month', date('Y-m'))],
        ], 'admin');
    }

    public function store(): void
    {
        $this->requirePost('/posko-rekap/create');
        $type = $this->types->find((int)$this->post('report_type_id', 0));
        if (!$type || $type['period_unit'] === 'NONE' || !(int)$type['is_active']) {
            Session::setFlash('error', 'Jenis laporan tidak dapat direkap.');
            $this->redirect('/posko-rekap/create');
        }

        $raw = (string)$this->post('period_start', '');
        $period = preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) ? PoskoSchedule::periodFor($type['period_unit'], $raw) : null;
        $unit = (int)Auth::satkerId();
        $back = '/posko-rekap/create?type=' . (int)$type['id'];

        $error = match (true) {
            !$period || $period['start'] !== $raw || $period['start'] > date('Y-m-d') => 'Periode rekap tidak valid.',
            $this->reports->existsForPeriod($unit, (int)$type['id'], $period['start'], 0, true) => 'Rekap unit Anda untuk jenis & periode ini sudah ada.',
            !$this->recaps->eligibleSources($unit, (int)$type['id'], $period['start']) => 'Belum ada laporan sumber yang disetujui untuk periode ini.',
            default => null,
        };
        if ($error) {
            Session::setFlash('error', $error);
            $this->redirect($back);
        }

        $deadline = PoskoSchedule::deadline($type, $period['end'], null);
        $general = array_merge($this->narrative(), [
            'period_start' => $period['start'],
            'period_end'   => $period['end'],
            'deadline_at'  => $deadline ? date('Y-m-d H:i:s', strtotime($deadline . ' +' . self::RECAP_EXTRA_DAYS . ' day')) : null,
        ]);
        $general['region'] = $general['region'] ?: (Auth::user()['satker_name'] ?? null);

        $id = $this->reports->createDraft((int)$type['id'], $unit, $general, [], (int)Auth::id(), true);
        $this->reports->logCreate($id, (int)Auth::id(), $unit);
        $count = $this->recaps->compute($id);
        Audit::log('posko.recap.create', null, "Rekap #{$id} ({$type['code']} {$period['start']}, {$count} sumber)");

        Session::setFlash('success', "Draft rekap tersimpan dari {$count} laporan sumber. Periksa lalu kirim ke unit induk.");
        $this->redirect("/posko-laporan/show/{$id}");
    }

    public function edit(int $id): void
    {
        $recap = $this->ownRecap($id);
        $type = $this->types->find((int)$recap['report_type_id']);
        $this->render('posko/recap_form', [
            'pageTitle' => 'Ubah Rekap ' . $type['name'],
            'types'     => [],
            'type'      => $type,
            'period'    => ['start' => $recap['period_start'], 'end' => $recap['period_end']],
            'sources'   => $this->recaps->sources($id),
            'values'    => array_map(fn($v) => $v['value_number'], $this->reports->values($id)),
            'fields'    => $this->types->fields((int)$type['id']),
            'existing'  => null,
            'recap'     => $recap,
            'input'     => $recap,
        ], 'admin');
    }

    public function save(): void
    {
        $this->requirePost('/posko-laporan');
        $recap = $this->ownRecap((int)$this->post('id', 0));
        $narrative = $this->narrative();
        if (!in_array($narrative['priority'], ['NORMAL', 'HIGH', 'CRITICAL'], true)) {
            $narrative['priority'] = 'NORMAL';
        }
        if (!$this->reports->updateNarrative((int)$recap['id'], $narrative, (int)Auth::id())) {
            Session::setFlash('error', 'Rekap tidak lagi dapat diubah.');
            $this->redirect("/posko-laporan/show/{$recap['id']}");
        }
        Audit::log('posko.recap.update', null, "Rekap #{$recap['id']}");
        Session::setFlash('success', 'Narasi rekap disimpan.');
        $this->redirect("/posko-laporan/show/{$recap['id']}");
    }

    /**
     * Hitung ulang nilai agregat & daftar sumber (mis. setelah laporan sumber direvisi)
     */
    public function refresh(): void
    {
        $this->requirePost('/posko-laporan');
        $recap = $this->ownRecap((int)$this->post('id', 0));
        $count = $this->recaps->compute((int)$recap['id']);
        Audit::log('posko.recap.refresh', null, "Rekap #{$recap['id']} ({$count} sumber)");
        Session::setFlash('success', "Nilai agregat dihitung ulang dari {$count} laporan sumber.");
        $this->redirect("/posko-laporan/show/{$recap['id']}");
    }

    private function narrative(): array
    {
        $txt = fn(string $k, int $max = 5000) => mb_substr(trim((string)$this->post($k, '')), 0, $max);
        return [
            'region'            => $txt('region', 255),
            'situation_summary' => $txt('situation_summary'),
            'description'       => $txt('description'),
            'obstacles'         => $txt('obstacles'),
            'follow_up'         => $txt('follow_up'),
            'assistance_needed' => $txt('assistance_needed'),
            'priority'          => (string)$this->post('priority', 'NORMAL'),
        ];
    }

    /**
     * Rekap milik unit pengguna yang masih dapat diubah
     */
    private function ownRecap(int $id): array
    {
        $recap = $id ? $this->reports->find($id) : null;
        if (!$recap || !(int)$recap['is_recap'] || (int)$recap['satker_id'] !== Auth::satkerId()
            || !in_array($recap['status'], PoskoReport::EDITABLE_STATUSES, true)) {
            Session::setFlash('error', 'Rekap tidak ditemukan atau tidak dapat diubah pada status saat ini.');
            $this->redirect($recap ? "/posko-laporan/show/{$id}" : '/posko-laporan');
        }
        return $recap;
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
}
