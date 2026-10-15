<?php
/**
 * SIGAP-POLRI: Monitoring Kepatuhan Pelaporan Posko (Tahap 4 — P4-04)
 * Route: /posko-kepatuhan
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\PoskoAccess;
use App\Core\Session;
use App\Config\Constants;
use App\Models\PoskoCompliance;
use App\Models\PoskoReportType;
use App\Models\Satker;

class PoskoComplianceController extends Controller
{
    private const PERIOD_COUNT = ['DAY' => 7, 'WEEK' => 4, 'MONTH' => 3];

    public function __construct()
    {
        Auth::requireRole(array_merge(Constants::POSKO_ROLES, [Constants::ROLE_SUPERADMIN]));
        if (PoskoAccess::mustChangePassword()) {
            $this->redirect('/posko/password');
        }
    }

    public function index(): void
    {
        $types = array_values(array_filter((new PoskoReportType())->all(true), fn($t) => $t['period_unit'] !== 'NONE'));
        $type = null;
        foreach ($types as $t) {
            if ((int)$t['id'] === (int)$this->get('type', 0)) {
                $type = $t;
            }
        }
        $type ??= $types[0] ?? null;

        $satkers = new Satker();
        $home = PoskoAccess::isPoskoUser() ? (int)Auth::satkerId() : 1;
        $unitId = (int)$this->get('unit', $home);
        if (!PoskoAccess::canViewUnit($unitId)) {
            Session::setFlash('error', 'Unit di luar wilayah kewenangan Anda.');
            $this->redirect('/posko-kepatuhan');
        }
        $unit = $satkers->findById($unitId);

        $rows = [];
        $periods = [];
        $matrix = [];
        $summary = array_fill_keys(array_keys(PoskoCompliance::STATUS), 0);
        $lateCount = 0;
        if ($type) {
            $levels = array_map('intval', explode(',', (string)$type['applies_to_levels']));
            // Baris: unit terpilih (bila wajib lapor) + unit anak langsung yang wajib lapor (aktif, bukan direktorat)
            $candidates = array_merge([$unit], $satkers->childrenOf($unitId));
            foreach ($candidates as $c) {
                if ((int)$c['is_active'] && $c['type'] !== 'DIREKTORAT' && in_array((int)$c['level'], $levels, true)) {
                    $rows[] = $c + ['has_children' => (bool)$satkers->childrenOf((int)$c['id'])];
                }
            }
            $periods = PoskoCompliance::recentPeriods($type, self::PERIOD_COUNT[$type['period_unit']]);
            $matrix = (new PoskoCompliance())->matrix($type, array_column($rows, 'id'), $periods);

            // Ringkasan periode terbaru yang tenggatnya sudah lewat (bila ada), selain itu periode terbaru
            $focus = PoskoCompliance::focusPeriod($type) ?? end($periods);
            foreach ($rows as $r) {
                $cell = $matrix[(int)$r['id']][$focus['start']];
                $summary[$cell['code']]++;
                $lateCount += $cell['late'] ? 1 : 0;
            }
        }

        $this->render('posko/compliance', [
            'pageTitle' => 'Kepatuhan Pelaporan Posko',
            'types'     => $types,
            'type'      => $type,
            'unit'      => $unit,
            'ancestors' => array_map(fn($a) => $a + ['linkable' => PoskoAccess::canViewUnit((int)$a['id'])], $satkers->ancestors($unitId)),
            'rows'      => $rows,
            'periods'   => $periods,
            'matrix'    => $matrix,
            'summary'   => $summary,
            'lateCount' => $lateCount,
            'focus'     => $focus ?? null,
        ], 'admin');
    }
}
