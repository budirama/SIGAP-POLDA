<?php
/**
 * SIGAP-POLRI: Rekapitulasi Berjenjang Posko (BRD 2.2–2.3, 4.4)
 *
 * Rekap unit U untuk jenis & periode tertentu memuat:
 *  - laporan sumber milik U sendiri yang sudah dikirim, dan
 *  - untuk setiap unit anak C: rekap C bila sudah disetujui/diteruskan, selain itu laporan sumber C
 *    yang sudah disetujui/diteruskan (rekap anak sudah memuat seluruh unit di bawahnya).
 * Nilai agregat dihitung otomatis dari isian berjenis angka sesuai cara rekap (SUM/AVG/MAX);
 * narasi/analisis ditulis petugas dan disimpan terpisah pada kolom laporan.
 */

namespace App\Models;

use App\Core\Model;

class PoskoRecap extends Model
{
    private const SENT = "'SUBMITTED', 'IN_REVIEW', 'APPROVED', 'FORWARDED'";
    private const ACCEPTED = "'APPROVED', 'FORWARDED'";

    /**
     * Laporan sumber yang memenuhi syarat untuk direkap unit ini
     */
    public function eligibleSources(int $unitId, int $typeId, string $periodStart): array
    {
        $rows = $this->fetchAll(
            "SELECT r.id, r.report_number, r.satker_id, r.status, r.version, r.is_recap, r.is_nil, r.needs_review,
                    s.name AS satker_name, s.level AS satker_level
             FROM posko_reports r
             JOIN satkers s ON s.id = r.satker_id
             WHERE r.report_type_id = ? AND r.period_key = ?
               AND (
                    (r.satker_id = ? AND r.is_recap = 0 AND r.status IN (" . self::SENT . "))
                 OR (s.parent_id = ? AND r.status IN (" . self::ACCEPTED . "))
               )
             ORDER BY s.name ASC, r.is_recap DESC",
            [$typeId, $periodStart, $unitId, $unitId]
        );

        // Satu sumber per unit anak: rekap anak (bila ada) menggantikan laporan sumber anak tersebut
        $picked = [];
        foreach ($rows as $r) {
            $key = (int)$r['satker_id'] === $unitId ? 'self' : 'c' . $r['satker_id'];
            if (!isset($picked[$key]) || ((int)$r['is_recap'] === 1 && (int)$picked[$key]['is_recap'] === 0)) {
                $picked[$key] = $r;
            }
        }
        return array_values($picked);
    }

    /**
     * Nilai agregat isian angka atas laporan sumber: [field_key => '123.45']
     *
     * @param int[] $sourceIds
     */
    public function aggregate(int $typeId, array $sourceIds): array
    {
        $sourceIds = array_values(array_filter(array_map('intval', $sourceIds)));
        if (!$sourceIds) {
            return [];
        }
        $fields = $this->fetchAll(
            "SELECT field_key, aggregate FROM posko_report_fields
             WHERE report_type_id = ? AND type = 'number' AND aggregate <> 'NONE'",
            [$typeId]
        );
        $in = implode(',', $sourceIds);
        $out = [];
        foreach ($fields as $f) {
            $fn = ['SUM' => 'SUM', 'AVG' => 'AVG', 'MAX' => 'MAX'][$f['aggregate']];
            $val = $this->fetch(
                "SELECT ROUND({$fn}(value_number), 2) AS v FROM posko_report_values
                 WHERE report_id IN ({$in}) AND field_key = ? AND value_number IS NOT NULL",
                [$f['field_key']]
            )['v'] ?? null;
            if ($val !== null) {
                $out[$f['field_key']] = $val;
            }
        }
        return $out;
    }

    /**
     * Hitung ulang nilai agregat & jejak sumber rekap (hanya selama rekap masih dapat diubah).
     * Mengembalikan jumlah sumber, atau null bila rekap tidak dapat dihitung ulang.
     */
    public function compute(int $recapId): ?int
    {
        $recap = $this->fetch("SELECT * FROM posko_reports WHERE id = ? AND is_recap = 1", [$recapId]);
        if (!$recap || !in_array($recap['status'], PoskoReport::EDITABLE_STATUSES, true) || !$recap['period_key']) {
            return null;
        }

        $sources = $this->eligibleSources((int)$recap['satker_id'], (int)$recap['report_type_id'], $recap['period_key']);
        $ids = array_map(fn($s) => (int)$s['id'], $sources);
        $values = $this->aggregate((int)$recap['report_type_id'], $ids);

        $this->beginTransaction();
        try {
            $this->execute("DELETE FROM posko_recap_sources WHERE recap_report_id = ?", [$recapId]);
            foreach ($sources as $s) {
                $this->execute(
                    "INSERT INTO posko_recap_sources (recap_report_id, source_report_id, source_version) VALUES (?, ?, ?)",
                    [$recapId, $s['id'], $s['version']]
                );
            }
            $this->execute("DELETE FROM posko_report_values WHERE report_id = ?", [$recapId]);
            foreach ($values as $key => $v) {
                $this->execute(
                    "INSERT INTO posko_report_values (report_id, field_key, value_number) VALUES (?, ?, ?)",
                    [$recapId, $key, $v]
                );
            }
            $this->execute(
                "UPDATE posko_reports SET recap_computed_at = NOW(), needs_review = 0 WHERE id = ?",
                [$recapId]
            );
            $this->commit();
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
        return count($sources);
    }

    /**
     * Sumber rekap tersimpan beserta status terkini & penanda perubahan versi
     */
    public function sources(int $recapId): array
    {
        return $this->fetchAll(
            "SELECT rs.source_version, r.id, r.report_number, r.status, r.version, r.is_recap, r.is_nil,
                    s.name AS satker_name, s.level AS satker_level
             FROM posko_recap_sources rs
             JOIN posko_reports r ON r.id = rs.source_report_id
             JOIN satkers s ON s.id = r.satker_id
             WHERE rs.recap_report_id = ?
             ORDER BY r.is_recap DESC, s.name ASC",
            [$recapId]
        );
    }

    /**
     * Tandai seluruh rekap yang memuat laporan ini (dan rekap di atasnya) agar ditinjau ulang — BRD 5.3
     *
     * @return int[] id rekap yang ditandai
     */
    public function flagContaining(int $reportId, array $seen = []): array
    {
        $recaps = $this->fetchAll(
            "SELECT rs.recap_report_id AS id FROM posko_recap_sources rs
             JOIN posko_reports r ON r.id = rs.recap_report_id
             WHERE rs.source_report_id = ? AND r.status <> 'CANCELLED'",
            [$reportId]
        );
        $flagged = [];
        foreach ($recaps as $row) {
            $id = (int)$row['id'];
            if (in_array($id, $seen, true)) {
                continue;
            }
            $this->execute("UPDATE posko_reports SET needs_review = 1 WHERE id = ?", [$id]);
            $flagged[] = $id;
            $seen[] = $id;
            $flagged = array_merge($flagged, $this->flagContaining($id, $seen));
        }
        return array_values(array_unique($flagged));
    }
}
