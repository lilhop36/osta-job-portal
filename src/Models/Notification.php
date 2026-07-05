<?php
declare(strict_types=1);

namespace App\Models;

class Notification extends BaseModel
{
    protected string $table = 'notifications';

    public function getForUser(int $userId, int $limit = 20): array
    {
        return $this->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE (target = 'all' 
                 OR (target = 'user' AND target_id = ?)
                 OR (target = 'department' AND target_id IN (SELECT department_id FROM users WHERE id = ?))
                 OR (target = 'job' AND target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $userId, $userId, $limit]
        );
    }

    public function countUnread(int $userId): int
    {
        $result = $this->fetchOne(
            "SELECT COUNT(*) as cnt FROM {$this->table}
             WHERE status = 'unread'
             AND (target = 'all' 
                 OR (target = 'user' AND target_id = ?)
                 OR (target = 'department' AND target_id IN (SELECT department_id FROM users WHERE id = ?))
                 OR (target = 'job' AND target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))",
            [$userId, $userId, $userId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public function markRead(int $id): bool
    {
        return $this->update($id, ['status' => 'read']);
    }

    public function markAllRead(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET status = 'read' 
             WHERE (target = 'all' 
                 OR (target = 'user' AND target_id = ?)
                 OR (target = 'department' AND target_id IN (SELECT department_id FROM users WHERE id = ?))
                 OR (target = 'job' AND target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))
             AND status = 'unread'"
        );
        return $stmt->execute([$userId, $userId, $userId]);
    }

    public function createNotification(array $data): int
    {
        return $this->create([
            'title'      => $data['title'],
            'message'    => $data['message'],
            'type'       => $data['type'] ?? 'info',
            'target'     => $data['target'] ?? 'all',
            'target_id'  => $data['target_id'] ?? null,
            'user_id'    => $data['user_id'] ?? null,
            'created_by' => $data['created_by'] ?? 1,
            'status'     => 'unread',
        ]);
    }
}
