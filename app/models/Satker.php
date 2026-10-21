<?php
/**
 * SIGAP-POLRI: Satker Model (Satuan Kerja & hierarki Pusat → Polda → Polres → Polsek)
 */

namespace App\Models;

use App\Core\Model;
use App\Config\Constants;

class Satker extends Model
{
    // Tingkat induk yang sah untuk setiap tipe satuan
    private const PARENT_TYPES = [
        'POLSEK'     => ['POLRES'],
        'POLRES'     => ['POLDA'],
        'POLDA'      => ['MABES'],
        'DIREKTORAT' => ['MABES'],
        'MABES'      => [],
    ];

    public function getAll(): array
    {
        return $this->fetchAll("SELECT * FROM satkers ORDER BY type ASC, name ASC");
    }

    public function getAllActive(): array
    {
        return $this->fetchAll("SELECT * FROM satkers WHERE is_active = 1 ORDER BY type ASC, name ASC");
    }

    public function getDirectorates(): array
    {
        return $this->fetchAll("SELECT * FROM satkers WHERE type = 'DIREKTORAT' ORDER BY name ASC");
    }

    public function getPoldas(): array
    {
        return $this->fetchAll("SELECT * FROM satkers WHERE type = 'POLDA' ORDER BY name ASC");
    }

    /**
     * Jumlah satuan aktif per tipe (untuk statistik publik landing page)
     *
     * @return array{POLDA:int, POLRES:int, POLSEK:int}
     */
    public function countActiveByType(): array
    {
        $counts = ['POLDA' => 0, 'POLRES' => 0, 'POLSEK' => 0];
        $rows = $this->fetchAll(
            "SELECT type, COUNT(*) AS c FROM satkers
             WHERE is_active = 1 AND type IN ('POLDA', 'POLRES', 'POLSEK') GROUP BY type"
        );
        foreach ($rows as $row) {
            $counts[$row['type']] = (int)$row['c'];
        }
        return $counts;
    }

    public function findById(int $id): ?array
    {
        return $this->fetch("SELECT * FROM satkers WHERE id = ? LIMIT 1", [$id]);
    }

    public static function levelForType(string $type): int
    {
        return match ($type) {
            'POLSEK' => Constants::LEVEL_POLSEK,
            'POLRES' => Constants::LEVEL_POLRES,
            'POLDA' => Constants::LEVEL_POLDA,
            default => Constants::LEVEL_PUSAT,
        };
    }

    /**
     * Unit ini beserta seluruh unit di bawahnya (memakai materialized path)
     *
     * @return int[]
     */
    public function selfAndDescendantIds(int $id): array
    {
        $unit = $this->findById($id);
        if (!$unit || empty($unit['path'])) {
            return [];
        }
        $rows = $this->fetchAll("SELECT id FROM satkers WHERE path LIKE ?", [$unit['path'] . '%']);
        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * Seluruh unit di bawah unit ini (tidak termasuk dirinya)
     *
     * @return int[]
     */
    public function descendantIds(int $id): array
    {
        return array_values(array_diff($this->selfAndDescendantIds($id), [$id]));
    }

    /**
     * Rantai leluhur dari akar hingga induk langsung (urut Pusat → ... → induk)
     */
    public function ancestors(int $id): array
    {
        $unit = $this->findById($id);
        if (!$unit || empty($unit['path'])) {
            return [];
        }
        $ids = array_map('intval', array_filter(explode('/', trim($unit['path'], '/'))));
        array_pop($ids); // dirinya sendiri
        if (!$ids) {
            return [];
        }
        $rows = $this->fetchAll(
            "SELECT * FROM satkers WHERE id IN (" . implode(',', $ids) . ") ORDER BY level DESC"
        );
        return $rows;
    }

    /**
     * Unit anak langsung (untuk drill-down & pemilihan induk)
     */
    public function childrenOf(int $id, bool $activeOnly = true): array
    {
        return $this->fetchAll(
            "SELECT * FROM satkers WHERE parent_id = ?" . ($activeOnly ? " AND is_active = 1" : '') . " ORDER BY name ASC",
            [$id]
        );
    }

    /**
     * Apakah unit $candidateId berada di dalam wilayah unit $rootId (termasuk dirinya)
     */
    public function isWithin(int $candidateId, int $rootId): bool
    {
        $root = $this->findById($rootId);
        $candidate = $this->findById($candidateId);
        return $root && $candidate && !empty($root['path']) && !empty($candidate['path'])
            && str_starts_with($candidate['path'], $root['path']);
    }

    /**
     * Validasi pasangan tipe unit & induknya; null bila sah, atau pesan galat
     */
    public function validateParent(string $type, ?int $parentId): ?string
    {
        if (!isset(self::PARENT_TYPES[$type])) {
            return 'Tipe satuan tidak dikenal.';
        }
        $allowed = self::PARENT_TYPES[$type];
        if (!$allowed) {
            return $parentId === null ? null : 'Mabes Polri tidak memiliki satuan induk.';
        }

        $parent = $parentId ? $this->findById($parentId) : null;
        if (!$parent || !in_array($parent['type'], $allowed, true)) {
            $names = implode(' / ', array_map(fn($t) => $t === 'MABES' ? 'Mabes Polri' : ucfirst(strtolower($t)), $allowed));
            return "Induk " . ucfirst(strtolower($type)) . " harus berupa {$names}.";
        }
        return null;
    }

    /**
     * Anak langsung beserta jumlah sub-unit & akun posko (halaman manajemen organisasi)
     */
    public function childrenWithStats(int $id): array
    {
        return $this->fetchAll(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM satkers c WHERE c.parent_id = s.id) AS child_count,
                    (SELECT COUNT(*) FROM users u WHERE u.satker_id = s.id
                        AND u.role IN ('ROLE_POSKO_ADMIN', 'ROLE_POSKO_OPERATOR', 'ROLE_POSKO_EXECUTIVE')) AS user_count
             FROM satkers s WHERE s.parent_id = ?
             ORDER BY s.is_active DESC, s.type ASC, s.name ASC",
            [$id]
        );
    }

    public function codeTaken(string $code, ?int $exceptId = null): bool
    {
        return $this->fetch(
            "SELECT id FROM satkers WHERE code = ? AND id <> ? LIMIT 1",
            [$code, $exceptId ?? 0]
        ) !== null;
    }

    /**
     * Calon induk yang sah untuk tipe unit (aktif), untuk pilihan di formulir
     */
    public function parentCandidates(string $type): array
    {
        $allowed = self::PARENT_TYPES[$type] ?? [];
        if (!$allowed) {
            return [];
        }
        $in = implode(',', array_map(fn($t) => "'" . $t . "'", $allowed));
        return $this->fetchAll("SELECT id, name, type FROM satkers WHERE type IN ({$in}) AND is_active = 1 ORDER BY name ASC");
    }

    public function create(string $type, string $code, string $name, ?int $parentId, ?string $address): int
    {
        $this->execute(
            "INSERT INTO satkers (parent_id, type, level, code, name, address) VALUES (?, ?, ?, ?, ?, ?)",
            [$parentId, $type, self::levelForType($type), $code, $name, $address]
        );
        $id = $this->lastInsertId();
        $this->rebuildHierarchy();
        return $id;
    }

    public function updateIdentity(int $id, string $code, string $name, ?string $address): void
    {
        $this->execute("UPDATE satkers SET code = ?, name = ?, address = ? WHERE id = ?", [$code, $name, $address, $id]);
    }

    /**
     * Pindah induk; path seluruh turunan ikut diperbarui
     */
    public function move(int $id, int $newParentId): void
    {
        $this->execute("UPDATE satkers SET parent_id = ? WHERE id = ?", [$newParentId, $id]);
        $this->rebuildHierarchy();
    }

    public function setActive(int $id, bool $active): void
    {
        $this->execute("UPDATE satkers SET is_active = ? WHERE id = ?", [$active ? 1 : 0, $id]);
    }

    /**
     * Hitung ulang level & materialized path seluruh hierarki (setelah tambah/pindah unit).
     * Dipanggil di dalam transaksi pemanggil.
     */
    public function rebuildHierarchy(): void
    {
        $this->execute(
            "UPDATE satkers SET level = CASE type WHEN 'POLSEK' THEN 1 WHEN 'POLRES' THEN 2 WHEN 'POLDA' THEN 3 ELSE 4 END"
        );
        $this->execute("UPDATE satkers SET path = CONCAT('/', id, '/') WHERE parent_id IS NULL");
        for ($i = 0; $i < 3; $i++) {
            $this->execute(
                "UPDATE satkers c JOIN satkers p ON p.id = c.parent_id
                 SET c.path = CONCAT(p.path, c.id, '/') WHERE p.path IS NOT NULL"
            );
        }
    }
}
