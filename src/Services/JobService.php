<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

class JobService
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

    public function findApprovedJob(int $jobId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'approved'");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        return $job ?: null;
    }

    public function getJobById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT j.*, d.name as department_name 
            FROM jobs j 
            LEFT JOIN departments d ON j.department_id = d.id 
            WHERE j.id = ?
        ");
        $stmt->execute([$id]);
        $job = $stmt->fetch();
        return $job ?: null;
    }

    public function getActiveJobs(int $limit = 20, int $offset = 0, array $filters = []): array
    {
        $where = ["j.status = 'approved'", "j.deadline >= CURDATE()"];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[] = "(j.title LIKE ? OR j.description LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if (!empty($filters['type'])) {
            $where[] = "j.employment_type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['department'])) {
            $where[] = "j.department_id = ?";
            $params[] = $filters['department'];
        }

        $whereClause = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare("
            SELECT j.*, d.name as department_name 
            FROM jobs j 
            LEFT JOIN departments d ON j.department_id = d.id 
            WHERE {$whereClause}
            ORDER BY j.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countActiveJobs(array $filters = []): int
    {
        $where = ["j.status = 'approved'", "j.deadline >= CURDATE()"];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[] = "(j.title LIKE ? OR j.description LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $whereClause = implode(' AND ', $where);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM jobs j WHERE {$whereClause}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
