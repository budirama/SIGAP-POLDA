<?php
/**
 * SIGAP-POLRI: Agregasi Dashboard Posko per tingkat (Tahap 5 — BRD 4.1, 7.2, 7.5)
 *
 * Semua angka dihitung langsung dari database untuk kumpulan unit ($scopeIds = unit + turunannya),
 * sehingga dashboard tingkat mana pun hanya memuat data wilayahnya. Status kewajiban lapor memakai
 * klasifikasi yang sama dengan halaman Kepatuhan (PoskoCompliance::classify).
 */

namespace App\Models;

use App\Core\Model;
use App\Core\PoskoSchedule;

class PoskoDashboard extends Model
{
    public const TREND_DAYS = 14;

    private const SENT_CODES = ['SUBMITTED', 'IN_REVIEW', 'APPROVED', 'NIL'];

    /** @var array<int, array>|null cache daftar satker per request */
    private ?array $units = null;

    // ------------------------------------------------------------------ Kewajiban lapor

    /**
     * Ringkasan kewajiban lapor periode fokus tiap jenis berperiode.
     *
     * @param int[] $scopeIds
     * @return array{required:int,sent:int,missing:int,late:int,nil:int,late_items:array}
     */
    public function obligations(array $scopeIds, ?string $now = null, int $lateLimit = 10): array
    {
        $now ??= date('Y-m-d H:i:s');
        $out = ['required' => 0, 'sent' => 0, 'missing' => 0, 'late' => 0, 'nil' => 0, 'late_items' => []];
        $scope = array_flip(array_map('intval', $scopeIds));
        if (!$scope) {
            return $out;
        }

        $units = array_filter($this->allUnits(), fn($s) => isset($scope[(int)$s['id']]) && (int)$s['is_active'] && $s['type'] !== 'DIREKTORAT');
        $compliance = new PoskoCompliance();

        foreach ((new PoskoReportType())->all(true) as $type) {
            if ($type['period_unit'] === 'NONE') {
                continue;
            }
            $levels = array_map('intval', explode(',', (string)$type['applies_to_levels']));
            $typeUnits = array_filter($units, fn($s) => in_array((int)$s['level'], $levels, true));
            if (!$typeUnits) {
                continue;
            }
            $focus = PoskoCompliance::focusPeriod($type, $now);
            if (!$focus) {
                continue;
            }
            $matrix = $compliance->matrix($type, array_column($typeUnits, 'id'), [$focus], $now);
            foreach ($typeUnits as $u) {
                $cell = $matrix[(int)$u['id']][$focus['start']];
                $out['required']++;
                $out['sent'] += in_array($cell['code'], self::SENT_CODES, true) ? 1 : 0;
                $out['nil'] += $cell['code'] === 'NIL' ? 1 : 0;
                $out['missing'] += $cell['code'] === 'MISSING_LATE' ? 1 : 0;
                if ($cell['late']) {
                    $out['late']++;
                    if (count($out['late_items']) < $lateLimit) {
                        $out['late_items'][] = [
                            'unit_id' => (int)$u['id'], 'unit' => $u['name'], 'type_id' => (int)$type['id'], 'type' => $type['name'],
                            'period' => PoskoSchedule::label($type['period_unit'], $focus['start'], $focus['end']),
                            'code' => $cell['code'], 'report_id' => $cell['report_id'],
                        ];
                    }
                }
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ Kartu & daftar

    /**
     * Kartu utama dashboard: harus dibuat, sudah dikirim, menunggu verifikasi, perlu perbaikan, terlambat
     *
     * @param int[] $scopeIds
     */
    public function cards(array $scopeIds, ?string $now = null): array
    {
        $ob = $this->obligations($scopeIds, $now, 10);
        return [
            'required'  => $ob['required'],
            'sent'      => $ob['sent'],
            'pending'   => $this->countStatus($scopeIds, ['SUBMITTED', 'IN_REVIEW']),
            'revision'  => $this->countStatus($scopeIds, ['NEEDS_REVISION']),
            'late'      => $ob['late'],
            'missing'   => $ob['missing'],
            'nil'       => $ob['nil'],
            'late_items' => $ob['late_items'],
        ];
    }

    /**
     * Jumlah laporan yang sudah pernah dikirim (bernomor) dengan status tertentu pada unit dalam cakupan
     *
     * @param int[] $scopeIds
     * @param string[] $statuses
     */
    public function countStatus(array $scopeIds, array $statuses): int
    {
        $in = $this->idList($scopeIds);
        if ($in === '' || !$statuses) {
            return 0;
        }
        return (int)($this->fetch(
            "SELECT COUNT(*) AS n FROM posko_reports
             WHERE satker_id IN ({$in}) AND report_number IS NOT NULL AND status IN (" . implode(',', array_fill(0, count($statuses), '?')) . ")",
            array_values($statuses)
        )['n'] ?? 0);
    }

    /**
     * Laporan terbaru yang dikirim
     *
     * @param int[] $scopeIds
     */
    public function recent(array $scopeIds, int $limit = 8): array
    {
        $in = $this->idList($scopeIds);
        if ($in === '') {
            return [];
        }
        $limit = max(1, min(50, $limit));
        return $this->fetchAll(
            "SELECT r.id, r.report_number, r.status, r.priority, r.is_nil, r.is_recap, r.submitted_at,
                    t.name AS type_name, s.name AS satker_name
             FROM posko_reports r
             JOIN posko_report_types t ON t.id = r.report_type_id
             JOIN satkers s ON s.id = r.satker_id
             WHERE r.satker_id IN ({$in}) AND r.report_number IS NOT NULL AND r.status <> 'CANCELLED'
             ORDER BY r.submitted_at DESC, r.id DESC LIMIT {$limit}"
        );
    }

    /**
     * Kejadian penting: laporan prioritas tinggi/kritis yang masih berjalan dalam jenjang pelaporan
     *
     * @param int[] $scopeIds
     */
    public function importantEvents(array $scopeIds, int $limit = 8): array
    {
        $in = $this->idList($scopeIds);
        if ($in === '') {
            return [];
        }
        $limit = max(1, min(50, $limit));
        return $this->fetchAll(
            "SELECT r.id, r.report_number, r.status, r.priority, r.situation_summary, r.submitted_at, r.forwarded_at,
                    t.name AS type_name, s.name AS satker_name, h.name AS holder_name
             FROM posko_reports r
             JOIN posko_report_types t ON t.id = r.report_type_id
             JOIN satkers s ON s.id = r.satker_id
             LEFT JOIN satkers h ON h.id = r.holder_satker_id
             WHERE r.satker_id IN ({$in}) AND r.priority IN ('HIGH', 'CRITICAL') AND r.is_nil = 0
               AND r.status IN ('SUBMITTED', 'IN_REVIEW', 'APPROVED', 'FORWARDED')
             ORDER BY (r.priority = 'CRITICAL') DESC, r.submitted_at DESC LIMIT {$limit}"
        );
    }

    /**
     * Tren jumlah laporan terkirim per hari (kini mundur $days hari), semua hari terisi
     *
     * @param int[] $scopeIds
     * @return array<int, array{date:string,count:int}>
     */
    public function trend(array $scopeIds, int $days = self::TREND_DAYS, ?string $today = null): array
    {
        $today ??= date('Y-m-d');
        $days = max(1, min(60, $days));
        $from = date('Y-m-d', strtotime("-" . ($days - 1) . " days", strtotime($today)));
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $series[date('Y-m-d', strtotime("+{$i} days", strtotime($from)))] = 0;
        }
        $in = $this->idList($scopeIds);
        if ($in !== '') {
            $rows = $this->fetchAll(
                "SELECT DATE(submitted_at) AS d, COUNT(*) AS n FROM posko_reports
                 WHERE satker_id IN ({$in}) AND report_number IS NOT NULL AND is_recap = 0 AND submitted_at >= ? AND submitted_at < ?
                 GROUP BY DATE(submitted_at)",
                [$from . ' 00:00:00', date('Y-m-d', strtotime('+1 day', strtotime($today))) . ' 00:00:00']
            );
            foreach ($rows as $r) {
                if (isset($series[$r['d']])) {
                    $series[$r['d']] = (int)$r['n'];
                }
            }
        }
        $out = [];
        foreach ($series as $d => $n) {
            $out[] = ['date' => $d, 'count' => $n];
        }
        return $out;
    }

    /**
     * Status pelaporan tiap unit anak langsung (drill-down satu tingkat ke bawah)
     *
     * @return array<int, array>
     */
    public function childrenStatus(int $unitId, ?string $now = null): array
    {
        $satkers = new Satker();
        $rows = [];
        foreach ($satkers->childrenOf($unitId) as $child) {
            if ($child['type'] === 'DIREKTORAT') {
                continue;
            }
            $scope = $satkers->selfAndDescendantIds((int)$child['id']);
            $ob = $this->obligations($scope, $now, 0);
            $rows[] = [
                'id' => (int)$child['id'], 'name' => $child['name'], 'code' => $child['code'], 'level' => (int)$child['level'],
                'required' => $ob['required'], 'sent' => $ob['sent'], 'late' => $ob['late'], 'missing' => $ob['missing'],
                'pending' => $this->countStatus($scope, ['SUBMITTED', 'IN_REVIEW']),
                'revision' => $this->countStatus($scope, ['NEEDS_REVISION']),
            ];
        }
        return $rows;
    }

    /**
     * Tindakan untuk pengguna: antrean verifikasi unit + laporan unit sendiri yang perlu diperbaiki
     */
    public function actionsFor(int $unitId, int $limit = 5): array
    {
        $inbox = (new PoskoReport())->inbox($unitId);
        $fix = $this->fetchAll(
            "SELECT r.id, r.report_number, r.updated_at, t.name AS type_name
             FROM posko_reports r JOIN posko_report_types t ON t.id = r.report_type_id
             WHERE r.satker_id = ? AND r.status = 'NEEDS_REVISION' AND r.report_number IS NOT NULL
             ORDER BY r.updated_at DESC LIMIT " . max(1, min(20, $limit)),
            [$unitId]
        );
        return ['inbox_total' => count($inbox), 'inbox' => array_slice($inbox, 0, $limit), 'revision' => $fix];
    }

    /**
     * Periode berjalan per jenis berperiode (untuk judul dashboard)
     */
    public function activePeriods(?string $now = null): array
    {
        $now ??= date('Y-m-d H:i:s');
        $out = [];
        foreach ((new PoskoReportType())->all(true) as $type) {
            if ($type['period_unit'] === 'NONE') {
                continue;
            }
            $p = PoskoCompliance::focusPeriod($type, $now);
            if ($p) {
                $out[] = ['type' => $type['name'], 'label' => PoskoSchedule::label($type['period_unit'], $p['start'], $p['end'])];
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ Util

    private function allUnits(): array
    {
        return $this->units ??= (new Satker())->getAll();
    }

    /** Daftar id bilangan bulat siap-IN; kosong bila tidak ada */
    private function idList(array $ids): string
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        return implode(',', $ids);
    }
}
