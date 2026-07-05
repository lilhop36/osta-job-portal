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
                 OR (target = 'user' AND target_id = ?))
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    public function countUnread(int $userId): int
    {
        $result = $this->fetchOne(
            "SELECT COUNT(*) as cnt FROM {$this->table}
             WHERE (target = 'all' 
                 OR (target = 'user' AND target_id = ?))
             AND status = 'unread'",
            [$userId]
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
             WHERE (target = 'all' OR (target = 'user' AND target_id = ?))
             AND status = 'unread'"
        );
        return $stmt->execute([$userId]);
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
