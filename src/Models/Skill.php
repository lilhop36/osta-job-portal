<?php
declare(strict_types=1);

namespace App\Models;

class Skill extends BaseModel
{
    protected string $table = 'skills';

    public function getAll(): array
    {
        return $this->findAll(['is_active' => 1], 'name ASC');
    }

    public function getByName(string $name): ?array
    {
        return $this->findWhere(['name' => $name]);
    }

    public function getByCategory(string $category): array
    {
        return $this->findAll(['category' => $category, 'is_active' => 1], 'name ASC');
    }

    public function getForUser(int $userId): array
    {
        return $this->fetchAll(
            "SELECT s.*, us.proficiency, us.years_experience
             FROM {$this->table} s
             INNER JOIN user_skills us ON s.id = us.skill_id
             WHERE us.user_id = ?
             ORDER BY s.name ASC",
            [$userId]
        );
    }

    public function getForJob(int $jobId): array
    {
        return $this->fetchAll(
            "SELECT s.*, js.is_required, js.min_proficiency
             FROM {$this->table} s
             INNER JOIN job_skills js ON s.id = js.skill_id
             WHERE js.job_id = ?
             ORDER BY js.is_required DESC, s.name ASC",
            [$jobId]
        );
    }

    public function search(string $query, int $limit = 20): array
    {
        return $this->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE name LIKE ? AND is_active = 1
             ORDER BY name ASC
             LIMIT ?",
            ['%' . $query . '%', $limit]
        );
    }
}
