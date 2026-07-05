<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

class ApplicationService
{
    private static ?self $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $this->pdo = Connection::getInstance()->getPdo();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function userHasApplication(int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM centralized_applications WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetch();
    }

    public function getApplicationsByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ca.*, GROUP_CONCAT(d.name SEPARATOR ', ') as department_names
            FROM centralized_applications ca
            LEFT JOIN departments d ON ca.department_id = d.id
            WHERE ca.user_id = ?
            ORDER BY ca.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getApplicationById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT ca.*, u.username, d.name as department_name
            FROM centralized_applications ca
            LEFT JOIN users u ON ca.user_id = u.id
            LEFT JOIN departments d ON ca.department_id = d.id
            WHERE ca.id = ?
        ");
        $stmt->execute([$id]);
        $app = $stmt->fetch();
        return $app ?: null;
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM centralized_applications WHERE status = ?");
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }

    public function countTotal(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM centralized_applications");
        return (int) $stmt->fetchColumn();
    }
}
