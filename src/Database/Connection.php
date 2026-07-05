<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?self $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $name = $_ENV['DB_NAME'] ?? 'osta_job_portal';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
        $appEnv = $_ENV['APP_ENV'] ?? 'development';

        if ($appEnv === 'production') {
            ini_set('display_errors', '0');
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        } else {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        }
        ini_set('log_errors', '1');

        try {
            $this->pdo = new PDO(
                "mysql:host={$host};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            if ($appEnv === 'production') {
                die("Database connection failed. Please contact the administrator.");
            }
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)->fetch() ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $table, array $data): string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->query(
            "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $stmt = $this->query(
            "UPDATE {$table} SET {$set} WHERE {$where}",
            array_merge(array_values($data), $whereParams)
        );
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $stmt = $this->query("DELETE FROM {$table} WHERE {$where}", $params);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    private function __clone() {}
}
