<?php
/**
 * SIGAP-POLDA: User Model
 */

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function findById(int $id): ?array
    {
        return $this->fetch(
            "SELECT u.*, s.name AS satker_name, s.code AS satker_code 
             FROM users u 
             LEFT JOIN satkers s ON u.satker_id = s.id 
             WHERE u.id = ? LIMIT 1",
            [$id]
        );
    }

    public function findByEmailOrNrp(string $identifier): ?array
    {
        return $this->fetch(
            "SELECT u.*, s.name AS satker_name, s.code AS satker_code 
             FROM users u 
             LEFT JOIN satkers s ON u.satker_id = s.id 
             WHERE (u.email = ? OR u.nrp = ? OR u.phone = ?) AND u.is_active = 1 LIMIT 1",
            [$identifier, $identifier, $identifier]
        );
    }

    public function findByNik(string $nik): ?array
    {
        return $this->fetch(
            "SELECT * FROM users WHERE nik = ? AND role = 'ROLE_CITIZEN' LIMIT 1",
            [$nik]
        );
    }

    public function findOrCreateCitizen(string $nik, string $name, string $phone): array
    {
        $existing = $this->findByNik($nik);
        if ($existing) {
            // Update nama & no telp jika ada perubahan
            $this->execute(
                "UPDATE users SET name = ?, phone = ? WHERE id = ?",
                [$name, $phone, $existing['id']]
            );
            $existing['name'] = $name;
            $existing['phone'] = $phone;
            return $existing;
        }

        // Buat akun warga baru (default password adalah hash nomor HP)
        $passwordHash = password_hash($phone, PASSWORD_BCRYPT);
        $this->execute(
            "INSERT INTO users (nik, name, phone, password_hash, role) VALUES (?, ?, ?, ?, 'ROLE_CITIZEN')",
            [$nik, $name, $phone, $passwordHash]
        );

        return $this->findById($this->lastInsertId());
    }

    public function getInvestigatorsBySatker(int $satkerId): array
    {
        return $this->fetchAll(
            "SELECT id, nrp, name, rank, position, phone, avatar_url 
             FROM users 
             WHERE satker_id = ? AND role IN ('ROLE_INVESTIGATOR', 'ROLE_SATKER_ADMIN') AND is_active = 1 
             ORDER BY rank DESC, name ASC",
            [$satkerId]
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
}
