<?php
/**
 * SIGAP-POLRI: User Model
 */

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function findById(int $id): ?array
    {
        return $this->fetch(
            "SELECT u.*, s.name AS satker_name, s.code AS satker_code,
                    s.type AS satker_type, s.level AS satker_level, s.path AS satker_path
             FROM users u
             LEFT JOIN satkers s ON u.satker_id = s.id
             WHERE u.id = ? LIMIT 1",
            [$id]
        );
    }

    public function findByEmailOrNrp(string $identifier): ?array
    {
        return $this->fetch(
            "SELECT u.*, s.name AS satker_name, s.code AS satker_code,
                    s.type AS satker_type, s.level AS satker_level, s.path AS satker_path
             FROM users u
             LEFT JOIN satkers s ON u.satker_id = s.id
             WHERE (u.email = ? OR u.nrp = ? OR u.phone = ?) AND u.is_active = 1 LIMIT 1",
            [$identifier, $identifier, $identifier]
        );
    }

    public function getInvestigatorsBySatker(int $satkerId): array
    {
        return $this->fetchAll(
            "SELECT id, satker_id, nrp, name, rank, position, phone, avatar_url
             FROM users
             WHERE satker_id = ? AND role IN ('ROLE_INVESTIGATOR', 'ROLE_SATKER_ADMIN') AND is_active = 1
             ORDER BY rank DESC, name ASC",
            [$satkerId]
        );
    }

    public function getAllActiveInvestigators(): array
    {
        return $this->fetchAll(
            "SELECT id, satker_id, nrp, name, rank, position, phone, avatar_url
             FROM users
             WHERE role IN ('ROLE_INVESTIGATOR', 'ROLE_SATKER_ADMIN') AND is_active = 1 AND satker_id IS NOT NULL
             ORDER BY satker_id ASC, rank DESC, name ASC"
        );
    }

    public function getAllStaff(): array
    {
        return $this->fetchAll(
            "SELECT u.id, u.nrp, u.name, u.role, u.rank, u.position, s.name AS satker_name
             FROM users u
             LEFT JOIN satkers s ON u.satker_id = s.id
             WHERE u.role != 'ROLE_CITIZEN'
             ORDER BY u.satker_id ASC, u.id ASC"
        );
    }

    /**
     * Akun posko pada unit-unit tertentu (cakupan wilayah admin), dengan pencarian & paginasi
     *
     * @param int[] $satkerIds
     */
    public function paginatePoskoUsers(array $satkerIds, string $search, int $page, int $perPage = 25): array
    {
        $satkerIds = array_values(array_filter(array_map('intval', $satkerIds)));
        if (!$satkerIds) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'perPage' => $perPage];
        }

        $where = "u.role IN ('ROLE_POSKO_ADMIN', 'ROLE_POSKO_OPERATOR', 'ROLE_POSKO_EXECUTIVE')
                  AND u.satker_id IN (" . implode(',', $satkerIds) . ")";
        $params = [];
        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR u.nrp LIKE ? OR u.email LIKE ? OR s.name LIKE ?)";
            $kw = "%{$search}%";
            array_push($params, $kw, $kw, $kw, $kw);
        }

        $total = (int)($this->fetch("SELECT COUNT(*) AS n FROM users u JOIN satkers s ON s.id = u.satker_id WHERE {$where}", $params)['n'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT u.id, u.nrp, u.name, u.email, u.phone, u.role, u.rank, u.position, u.is_active,
                    u.must_change_password, u.satker_id, s.name AS satker_name, s.level AS satker_level
             FROM users u JOIN satkers s ON s.id = u.satker_id
             WHERE {$where}
             ORDER BY s.level DESC, s.name ASC, u.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    /**
     * Apakah email/NRP/HP sudah dipakai akun lain
     */
    public function identityTaken(string $email, string $nrp, ?string $phone): bool
    {
        $sql = "SELECT id FROM users WHERE email = ? OR nrp = ?";
        $params = [$email, $nrp];
        if ($phone !== null && $phone !== '') {
            $sql .= " OR phone = ?";
            $params[] = $phone;
        }
        return $this->fetch($sql . " LIMIT 1", $params) !== null;
    }

    /**
     * Buat akun posko; kata sandi awal wajib diganti pada login pertama
     */
    public function createPoskoUser(array $data, string $plainPassword): int
    {
        $this->execute(
            "INSERT INTO users (nrp, name, email, phone, password_hash, must_change_password, role, satker_id, rank, position, is_active)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 1)",
            [
                $data['nrp'], $data['name'], $data['email'], $data['phone'],
                password_hash($plainPassword, PASSWORD_DEFAULT),
                $data['role'], $data['satker_id'], $data['rank'] ?: null, $data['position'] ?: null,
            ]
        );
        return $this->lastInsertId();
    }

    public function setActive(int $userId, bool $active): bool
    {
        return $this->affected("UPDATE users SET is_active = ? WHERE id = ?", [$active ? 1 : 0, $userId]) === 1;
    }

    /**
     * Setel kata sandi baru; $mustChange = true untuk sandi sementara dari admin
     */
    public function setPassword(int $userId, string $plainPassword, bool $mustChange): void
    {
        $this->execute(
            "UPDATE users SET password_hash = ?, must_change_password = ? WHERE id = ?",
            [password_hash($plainPassword, PASSWORD_DEFAULT), $mustChange ? 1 : 0, $userId]
        );
    }

    public function passwordHash(int $userId): ?string
    {
        return $this->fetch("SELECT password_hash FROM users WHERE id = ?", [$userId])['password_hash'] ?? null;
    }
}
