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

    /**
     * Cari akun warga berdasarkan NIK, atau buat baru bila belum ada.
     * Data akun yang sudah ada TIDAK diubah: NIK diketik bebas di formulir publik,
     * sehingga tidak boleh menjadi dasar untuk menimpa nama/telepon pemilik NIK.
     */
    public function findOrCreateCitizen(string $nik, string $name, string $phone): array
    {
        $existing = $this->findByNik($nik);
        if ($existing) {
            return $existing;
        }

        // Akun warga baru tidak memiliki password yang dapat ditebak;
        // pelacakan laporan menggunakan Nomor Tiket + PIN Keamanan.
        $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
        $this->execute(
            "INSERT INTO users (nik, name, phone, password_hash, role) VALUES (?, ?, ?, ?, 'ROLE_CITIZEN')",
            [$nik, $name, $phone, $passwordHash]
        );

        return $this->findById($this->lastInsertId());
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
}
