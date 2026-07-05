<?php
declare(strict_types=1);

namespace App\Models;

class Department extends BaseModel
{
    protected string $table = 'departments';

    public function getAll(): array
    {
        return $this->findAll([], 'name ASC');
    }

    public function getActive(): array
    {
        return $this->findAll([], 'name ASC', 100);
    }

    public function getByName(string $name): ?array
    {
        return $this->findWhere(['name' => $name]);
    }

    public function getWithCounts(): array
    {
        return $this->fetchAll(
            "SELECT d.*, 
                    (SELECT COUNT(*) FROM users WHERE department_id = d.id) as user_count,
                    (SELECT COUNT(*) FROM jobs WHERE department_id = d.id) as job_count
             FROM {$this->table} d
             ORDER BY d.name ASC"
        );
    }

    public function getJobCount(int $departmentId): int
    {
        $result = $this->fetchOne(
            "SELECT COUNT(*) as cnt FROM jobs WHERE department_id = ?",
            [$departmentId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public function getUserCount(int $departmentId): int
    {
        $result = $this->fetchOne(
            "SELECT COUNT(*) as cnt FROM users WHERE department_id = ?",
            [$departmentId]
        );
        return (int) ($result['cnt'] ?? 0);
    }
}
