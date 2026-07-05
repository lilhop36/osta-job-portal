<?php
declare(strict_types=1);

namespace App\Models;

class Application extends BaseModel
{
    protected string $table = 'centralized_applications';

    public function getByUser(int $userId): array
    {
        return $this->fetchAll(
            "SELECT ca.*, GROUP_CONCAT(d.name SEPARATOR ', ') as department_names
             FROM {$this->table} ca
             LEFT JOIN departments d ON ca.department_id = d.id
             WHERE ca.user_id = ?
             ORDER BY ca.created_at DESC",
            [$userId]
        );
    }

    public function getWithDetails(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT ca.*, u.username, u.email as user_email, d.name as department_name
             FROM {$this->table} ca
             LEFT JOIN users u ON ca.user_id = u.id
             LEFT JOIN departments d ON ca.department_id = d.id
             WHERE ca.id = ?",
            [$id]
        );
    }

    public function countByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function countTotal(): int
    {
        return $this->count();
    }

    public function getByStatus(string $status, int $limit = 50): array
    {
        return $this->findAll(['status' => $status], 'created_at DESC', $limit);
    }

    public function updateStatus(int $id, string $status, ?string $notes = null): bool
    {
        $data = ['status' => $status];
        if ($notes !== null) {
            $data['eligibility_notes'] = $notes;
        }
        return $this->update($id, $data);
    }

    public function generateApplicationNumber(): string
    {
        $year = date('Y');
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) + 1 as next_num FROM {$this->table} WHERE YEAR(created_at) = ?"
        );
        $stmt->execute([$year]);
        $nextNum = (int) $stmt->fetch()['next_num'];
        return sprintf('APP-%s-%04d', $year, $nextNum);
    }

    public function markSubmitted(int $id): bool
    {
        return $this->update($id, [
            'status' => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getRecent(int $limit = 10): array
    {
        return $this->findAll([], 'created_at DESC', $limit);
    }

    public function getStats(): array
    {
        $total = $this->countTotal();
        return [
            'total'           => $total,
            'draft'           => $this->countByStatus('draft'),
            'submitted'       => $this->countByStatus('submitted'),
            'under_review'    => $this->countByStatus('under_review'),
            'shortlisted'     => $this->countByStatus('shortlisted'),
            'rejected'        => $this->countByStatus('rejected'),
            'accepted'        => $this->countByStatus('accepted'),
            'onboarding'      => $this->countByStatus('onboarding'),
        ];
    }
}
