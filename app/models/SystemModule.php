<?php
/**
 * SIGAP-POLRI: Model Manajemen Modul & Fitur Sistem
 */

namespace App\Models;

use App\Core\Model;
use PDO;

class SystemModule extends Model
{
    /**
     * Ambil seluruh modul dalam struktur hierarki (Induk & Sub-modul)
     */
    public function getHierarchical(): array
    {
        $sql = "SELECT * FROM system_modules ORDER BY sort_order ASC, id ASC";
        $rows = $this->fetchAll($sql);

        $parents = [];
        $children = [];

        foreach ($rows as $row) {
            $row['is_active'] = (bool)$row['is_active'];
            $row['is_core'] = (bool)$row['is_core'];
            if (empty($row['parent_key'])) {
                $row['children'] = [];
                $parents[$row['module_key']] = $row;
            } else {
                $children[$row['parent_key']][] = $row;
            }
        }

        foreach ($children as $parentKey => $items) {
            if (isset($parents[$parentKey])) {
                $parents[$parentKey]['children'] = $items;
            } else {
                // Orphan child fallback
                foreach ($items as $item) {
                    $item['children'] = [];
                    $parents[$item['module_key']] = $item;
                }
            }
        }

        return array_values($parents);
    }

    /**
     * Ambil data modul berdasarkan module_key
     */
    public function findByKey(string $key): ?array
    {
        $sql = "SELECT * FROM system_modules WHERE module_key = ? LIMIT 1";
        $row = $this->fetch($sql, [$key]);
        if ($row) {
            $row['is_active'] = (bool)$row['is_active'];
            $row['is_core'] = (bool)$row['is_core'];
        }
        return $row;
    }

    /**
     * Ambil peta status seluruh modul untuk caching cepat
     */
    public function getStatusMap(): array
    {
        $sql = "SELECT module_key, parent_key, is_active, is_core FROM system_modules";
        $rows = $this->fetchAll($sql);

        $map = [];
        foreach ($rows as $r) {
            $map[$r['module_key']] = [
                'parent_key' => $r['parent_key'],
                'is_active' => (bool)$r['is_active'],
                'is_core' => (bool)$r['is_core']
            ];
        }
        return $map;
    }

    /**
     * Toggle status aktif/nonaktif modul
     */
    public function setStatus(string $moduleKey, bool $isActive, ?int $userId = null): bool
    {
        $module = $this->findByKey($moduleKey);
        if (!$module) {
            return false;
        }

        // Modul inti tidak boleh dinonaktifkan
        if (!$isActive && !empty($module['is_core'])) {
            return false;
        }

        $activeVal = $isActive ? 1 : 0;
        
        $sql = "UPDATE system_modules SET is_active = ?, updated_by = ?, updated_at = NOW() WHERE module_key = ?";
        $res = $this->execute($sql, [$activeVal, $userId, $moduleKey]);

        // Jika modul induk dinonaktifkan, nonaktifkan juga seluruh sub-modulnya
        if (!$isActive && empty($module['parent_key'])) {
            $sqlChildren = "UPDATE system_modules SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE parent_key = ?";
            $this->execute($sqlChildren, [$userId, $moduleKey]);
        }

        // Jika sub-modul diaktifkan, pastikan modul induknya juga berstatus aktif
        if ($isActive && !empty($module['parent_key'])) {
            $sqlParent = "UPDATE system_modules SET is_active = 1, updated_by = ?, updated_at = NOW() WHERE module_key = ?";
            $this->execute($sqlParent, [$userId, $module['parent_key']]);
        }

        return $res;
    }

    /**
     * Perbarui informasi label, deskripsi, ikon, atau urutan modul
     */
    public function updateModule(string $moduleKey, array $data, ?int $userId = null): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = trim($data['name']);
        }
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $params[] = trim($data['description']);
        }
        if (isset($data['icon'])) {
            $fields[] = "icon = ?";
            $params[] = trim($data['icon']);
        }
        if (isset($data['sort_order'])) {
            $fields[] = "sort_order = ?";
            $params[] = (int)$data['sort_order'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_by = ?";
        $params[] = $userId;
        $fields[] = "updated_at = NOW()";

        $params[] = $moduleKey;
        $sql = "UPDATE system_modules SET " . implode(", ", $fields) . " WHERE module_key = ?";

        return $this->execute($sql, $params);
    }
}
