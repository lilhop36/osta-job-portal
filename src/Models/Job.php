<?php
declare(strict_types=1);

namespace App\Models;

class Job extends BaseModel
{
    protected string $table = 'jobs';

    public function findApproved(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT j.*, d.name as department_name 
             FROM {$this->table} j 
             LEFT JOIN departments d ON j.department_id = d.id 
             WHERE j.id = ? AND j.status = 'approved'",
            [$id]
        );
    }

    public function getActive(int $limit = 20, int $offset = 0, array $filters = []): array
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

        if (!empty($filters['location'])) {
            $where[] = "j.location LIKE ?";
            $params[] = '%' . $filters['location'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return $this->fetchAll(
            "SELECT j.*, d.name as department_name 
             FROM {$this->table} j 
             LEFT JOIN departments d ON j.department_id = d.id 
             WHERE {$whereClause}
             ORDER BY j.created_at DESC 
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public function countActive(array $filters = []): int
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
        return (int) $this->fetchOne(
            "SELECT COUNT(*) FROM {$this->table} j WHERE {$whereClause}",
            $params
        )['COUNT(*)'] ?? 0;
    }

    public function getJobsByEmployer(int $userId, int $limit = 50): array
    {
        return $this->fetchAll(
            "SELECT j.*, d.name as department_name 
             FROM {$this->table} j 
             LEFT JOIN departments d ON j.department_id = d.id 
             WHERE j.created_by = ?
             ORDER BY j.created_at DESC 
             LIMIT ?",
            [$userId, $limit]
        );
    }

    public function countPending(): int
    {
        return $this->count(['status' => 'pending']);
    }

    public function approve(int $jobId): bool
    {
        return $this->update($jobId, ['status' => 'approved']);
    }

    public function reject(int $jobId): bool
    {
        return $this->update($jobId, ['status' => 'expired']);
    }

    public function incrementApplicationCount(int $jobId): bool
    {
        return $this->increment('application_count', 1, $jobId);
    }

    public function getWithDepartment(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT j.*, d.name as department_name, d.contact_email as dept_email
             FROM {$this->table} j 
             LEFT JOIN departments d ON j.department_id = d.id 
             WHERE j.id = ?",
            [$id]
        );
    }
}
