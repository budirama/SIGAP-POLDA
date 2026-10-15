<?php
/**
 * SIGAP-POLRI: Base Model PDO Wrapper
 */

namespace App\Core;

use App\Config\Database;
use PDO;
use PDOStatement;

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        $res = $this->query($sql, $params)->fetch();
        return $res ?: null;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Eksekusi perintah dan kembalikan jumlah baris yang terdampak
     */
    protected function affected(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    protected function lastInsertId(): int
    {
        return (int)$this->db->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollBack(): bool
    {
        return $this->db->rollBack();
    }
}
