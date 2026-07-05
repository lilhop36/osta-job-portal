<?php
declare(strict_types=1);

namespace App\Models;

class Company extends BaseModel
{
    protected string $table = 'companies';

    public function getByUser(int $userId): ?array
    {
        return $this->findWhere(['user_id' => $userId]);
    }

    public function getVerified(int $limit = 50): array
    {
        return $this->findAll(['is_verified' => 1], 'name ASC', $limit);
    }

    public function getPending(): array
    {
        return $this->findAll(['is_verified' => 0], 'created_at DESC');
    }

    public function verify(int $companyId, int $adminId): bool
    {
        return $this->update($companyId, [
            'is_verified' => 1,
            'verified_by' => $adminId,
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getWithJobCount(): array
    {
        return $this->fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM jobs WHERE company_id = c.id AND status = 'approved') as job_count
             FROM {$this->table} c
             ORDER BY c.name ASC"
        );
    }
}
