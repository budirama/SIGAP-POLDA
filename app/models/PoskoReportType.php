<?php
/**
 * SIGAP-POLRI: Jenis Laporan Posko & definisi isiannya (dapat dikonfigurasi)
 */

namespace App\Models;

use App\Core\Model;

class PoskoReportType extends Model
{
    public const KINDS = [
        'ROUTINE'       => 'Laporan Rutin',
        'PERIODIC'      => 'Laporan Periodik',
        'SPECIAL_EVENT' => 'Laporan Kejadian Khusus',
        'INCIDENTAL'    => 'Laporan Insidentil',
    ];
    public const PERIOD_UNITS = ['DAY' => 'Harian', 'WEEK' => 'Mingguan', 'MONTH' => 'Bulanan', 'NONE' => 'Tanpa periode'];
    public const FIELD_TYPES = ['number' => 'Angka', 'text' => 'Teks singkat', 'textarea' => 'Teks panjang', 'select' => 'Pilihan', 'date' => 'Tanggal'];
    public const AGGREGATES = ['SUM' => 'Jumlah', 'AVG' => 'Rata-rata', 'MAX' => 'Maksimum', 'NONE' => 'Tidak direkap'];

    public function all(bool $activeOnly = false): array
    {
        return $this->fetchAll(
            "SELECT * FROM posko_report_types" . ($activeOnly ? " WHERE is_active = 1" : '') . " ORDER BY sort_order ASC, name ASC"
        );
    }

    /**
     * Jenis aktif yang berlaku untuk tingkat tertentu
     */
    public function forLevel(int $level): array
    {
        return $this->fetchAll(
            "SELECT * FROM posko_report_types WHERE is_active = 1 AND FIND_IN_SET(?, applies_to_levels) ORDER BY sort_order ASC, name ASC",
            [(string)$level]
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch("SELECT * FROM posko_report_types WHERE id = ? LIMIT 1", [$id]);
    }

    public function fields(int $typeId, bool $activeOnly = true): array
    {
        return $this->fetchAll(
            "SELECT * FROM posko_report_fields WHERE report_type_id = ?" . ($activeOnly ? " AND is_active = 1" : '') . "
             ORDER BY sort_order ASC, id ASC",
            [$typeId]
        );
    }

    public function findField(int $id): ?array
    {
        return $this->fetch("SELECT * FROM posko_report_fields WHERE id = ? LIMIT 1", [$id]);
    }

    public function codeTaken(string $code, int $exceptId = 0): bool
    {
        return $this->fetch("SELECT id FROM posko_report_types WHERE code = ? AND id <> ? LIMIT 1", [$code, $exceptId]) !== null;
    }

    public function saveType(?int $id, array $d): int
    {
        $params = [
            $d['code'], $d['name'], $d['kind'], $d['period_unit'], $d['deadline_offset_days'], $d['deadline_time'],
            $d['deadline_hours'], $d['requires_approval'], $d['allows_nil'], $d['applies_to_levels'], $d['description'],
            $d['sort_order'], $d['is_active'],
        ];
        if ($id) {
            $this->execute(
                "UPDATE posko_report_types SET code = ?, name = ?, kind = ?, period_unit = ?, deadline_offset_days = ?,
                    deadline_time = ?, deadline_hours = ?, requires_approval = ?, allows_nil = ?, applies_to_levels = ?,
                    description = ?, sort_order = ?, is_active = ?
                 WHERE id = ?",
                array_merge($params, [$id])
            );
            return $id;
        }
        $this->execute(
            "INSERT INTO posko_report_types (code, name, kind, period_unit, deadline_offset_days, deadline_time, deadline_hours,
                requires_approval, allows_nil, applies_to_levels, description, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            $params
        );
        return $this->lastInsertId();
    }

    public function fieldKeyTaken(int $typeId, string $key, int $exceptId = 0): bool
    {
        return $this->fetch(
            "SELECT id FROM posko_report_fields WHERE report_type_id = ? AND field_key = ? AND id <> ? LIMIT 1",
            [$typeId, $key, $exceptId]
        ) !== null;
    }

    /**
     * Simpan definisi isian. field_key tidak diubah setelah dibuat agar nilai laporan lama tetap terbaca.
     */
    public function saveField(?int $id, int $typeId, array $d): int
    {
        if ($id) {
            $this->execute(
                "UPDATE posko_report_fields SET label = ?, type = ?, required = ?, options = ?, aggregate = ?, unit_label = ?,
                    sort_order = ?, is_active = ?
                 WHERE id = ? AND report_type_id = ?",
                [$d['label'], $d['type'], $d['required'], $d['options'], $d['aggregate'], $d['unit_label'], $d['sort_order'], $d['is_active'], $id, $typeId]
            );
            return $id;
        }
        $this->execute(
            "INSERT INTO posko_report_fields (report_type_id, field_key, label, type, required, options, aggregate, unit_label, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$typeId, $d['field_key'], $d['label'], $d['type'], $d['required'], $d['options'], $d['aggregate'], $d['unit_label'], $d['sort_order'], $d['is_active']]
        );
        return $this->lastInsertId();
    }

    /**
     * Pilihan isian select (satu per baris)
     *
     * @return string[]
     */
    public static function optionsOf(array $field): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$field['options']))));
    }
}
