<?php
declare(strict_types=1);

namespace App\Models;

class Interview extends BaseModel
{
    protected string $table = 'interviews';

    public function getWithDetails(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT i.*, 
                    ca.first_name, ca.last_name, ca.email as applicant_email,
                    u.username as interviewer_name,
                    it.name as type_name
             FROM {$this->table} i
             LEFT JOIN centralized_applications ca ON i.application_id = ca.id
             LEFT JOIN users u ON i.primary_interviewer_id = u.id
             LEFT JOIN interview_types it ON i.interview_type_id = it.id
             WHERE i.id = ?",
            [$id]
        );
    }

    public function getUpcoming(int $limit = 20): array
    {
        return $this->fetchAll(
            "SELECT i.*, 
                    ca.first_name, ca.last_name,
                    u.username as interviewer_name
             FROM {$this->table} i
             LEFT JOIN centralized_applications ca ON i.application_id = ca.id
             LEFT JOIN users u ON i.primary_interviewer_id = u.id
             WHERE i.scheduled_date >= CURDATE() AND i.status = 'scheduled'
             ORDER BY i.scheduled_date ASC, i.start_time ASC
             LIMIT ?",
            [$limit]
        );
    }

    public function getByApplication(int $applicationId): array
    {
        return $this->findAll(
            ['application_id' => $applicationId],
            'scheduled_date DESC'
        );
    }

    public function getForUser(int $userId): array
    {
        return $this->fetchAll(
            "SELECT i.*, ca.first_name, ca.last_name
             FROM {$this->table} i
             LEFT JOIN centralized_applications ca ON i.application_id = ca.id
             WHERE i.primary_interviewer_id = ?
             ORDER BY i.scheduled_date DESC
             LIMIT 20",
            [$userId]
        );
    }

    public function countScheduled(): int
    {
        return $this->count(['status' => 'scheduled']);
    }

    public function countToday(): int
    {
        return (int) $this->fetchOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE scheduled_date = CURDATE() AND status = 'scheduled'"
        )['cnt'] ?? 0;
    }

    public function generateCode(): string
    {
        $prefix = 'INT';
        $date = date('ymd');
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) + 1 as next_num FROM {$this->table} WHERE DATE(created_at) = CURDATE()"
        );
        $stmt->execute();
        $nextNum = (int) $stmt->fetch()['next_num'];
        return sprintf('%s%s%03d', $prefix, $date, $nextNum);
    }

    public function complete(int $id, ?string $feedback = null, ?float $rating = null): bool
    {
        $data = ['status' => 'completed'];
        if ($feedback !== null) $data['feedback'] = $feedback;
        if ($rating !== null) $data['overall_rating'] = $rating;
        return $this->update($id, $data);
    }

    public function cancel(int $id): bool
    {
        return $this->update($id, ['status' => 'cancelled']);
    }
}
