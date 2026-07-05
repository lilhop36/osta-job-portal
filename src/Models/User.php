<?php
declare(strict_types=1);

namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->findWhere(['email' => $email]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->findWhere(['username' => $username]);
    }

    public function getActiveUsers(int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['status' => 'active'], 'created_at DESC', $limit, $offset);
    }

    public function getUsersByRole(string $role, int $limit = 100): array
    {
        return $this->findAll(['role' => $role], 'created_at DESC', $limit);
    }

    public function countByRole(string $role): int
    {
        return $this->count(['role' => $role]);
    }

    public function countActive(): int
    {
        return $this->count(['status' => 'active']);
    }

    public function countPending(): int
    {
        return $this->count(['status' => 'pending']);
    }

    public function setStatus(int $userId, string $status): bool
    {
        return $this->update($userId, ['status' => $status]);
    }

    public function setRole(int $userId, string $role): bool
    {
        return $this->update($userId, ['role' => $role]);
    }

    public function getDepartmentId(int $userId): ?int
    {
        $user = $this->find($userId);
        return $user && $user['department_id'] ? (int) $user['department_id'] : null;
    }
}
