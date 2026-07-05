<?php
declare(strict_types=1);

namespace App\Models;

class SavedJob extends BaseModel
{
    protected string $table = 'saved_jobs';

    public function getByUser(int $userId): array
    {
        return $this->fetchAll(
            "SELECT sj.*, j.title, j.location, j.employment_type, j.deadline,
                    d.name as department_name
             FROM {$this->table} sj
             INNER JOIN jobs j ON sj.job_id = j.id
             LEFT JOIN departments d ON j.department_id = d.id
             WHERE sj.user_id = ?
             ORDER BY sj.created_at DESC",
            [$userId]
        );
    }

    public function isSaved(int $userId, int $jobId): bool
    {
        return $this->exists(['user_id' => $userId, 'job_id' => $jobId]);
    }

    public function toggle(int $userId, int $jobId): bool
    {
        if ($this->isSaved($userId, $jobId)) {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->table} WHERE user_id = ? AND job_id = ?"
            );
            $stmt->execute([$userId, $jobId]);
            return false; // removed
        } else {
            $this->create(['user_id' => $userId, 'job_id' => $jobId]);
            return true; // added
        }
    }

    public function countByUser(int $userId): int
    {
        return $this->count(['user_id' => $userId]);
    }
}
