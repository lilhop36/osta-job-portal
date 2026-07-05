<?php
declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;

abstract class BaseModel
{
    protected \PDO $pdo;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->pdo = Connection::getInstance()->getPdo();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(array $conditions = [], string $orderBy = 'created_at DESC', int $limit = 100, int $offset = 0): array
    {
        $where = '';
        $params = [];

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $column => $value) {
                if (is_array($value)) {
                    // ['operator', 'value'] e.g. ['>=', '2024-01-01']
                    $clauses[] = "{$column} {$value[0]} ?";
                    $params[] = $value[1];
                } else {
                    $clauses[] = "{$column} = ?";
                    $params[] = $value;
                }
            }
            $where = 'WHERE ' . implode(' AND ', $clauses);
        }

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} {$where} ORDER BY {$orderBy} LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(array $conditions = []): int
    {
        $where = '';
        $params = [];

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $column => $value) {
                if (is_array($value)) {
                    $clauses[] = "{$column} {$value[0]} ?";
                    $params[] = $value[1];
                } else {
                    $clauses[] = "{$column} = ?";
                    $params[] = $value;
                }
            }
            $where = 'WHERE ' . implode(' AND ', $clauses);
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    public function exists(array $conditions): bool
    {
        $clauses = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $clauses[] = "{$column} = ?";
            $params[] = $value;
        }

        $where = implode(' AND ', $clauses);
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$this->table} WHERE {$where} LIMIT 1");
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function findWhere(array $conditions, string $orderBy = 'id DESC'): ?array
    {
        $clauses = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $clauses[] = "{$column} = ?";
            $params[] = $value;
        }

        $where = implode(' AND ', $clauses);
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$where} ORDER BY {$orderBy} LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function increment(string $column, int $amount = 1, int $id = 0): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET {$column} = {$column} + ? WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([$amount, $id]);
    }

    public function transaction(callable $callback): bool
    {
        try {
            $this->pdo->beginTransaction();
            $callback($this->pdo);
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
}
