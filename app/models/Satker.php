<?php
/**
 * SIGAP-POLRI: Satker Model
 */

namespace App\Models;

use App\Core\Model;

class Satker extends Model
{
    public function getAll(): array
    {
        return $this->fetchAll("SELECT * FROM satkers ORDER BY type ASC, name ASC");
    }

    public function getDirectorates(): array
    {
        return $this->fetchAll("SELECT * FROM satkers WHERE type = 'DIREKTORAT' ORDER BY name ASC");
    }

    public function getPoldas(): array
    {
        return $this->fetchAll("SELECT * FROM satkers WHERE type = 'POLDA' ORDER BY name ASC");
    }

    public function findById(int $id): ?array
    {
        return $this->fetch("SELECT * FROM satkers WHERE id = ? LIMIT 1", [$id]);
    }
}
