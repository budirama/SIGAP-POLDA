<?php
/**
 * SIGAP-POLDA: Satker Model
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

    public function getPolres(): array
    {
        return $this->fetchAll("SELECT * FROM satkers WHERE type = 'POLRES' ORDER BY name ASC");
    }

    public function findById(int $id): ?array
    {
        return $this->fetch("SELECT * FROM satkers WHERE id = ? LIMIT 1", [$id]);
    }
}
