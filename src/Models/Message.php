<?php
declare(strict_types=1);

namespace App\Models;

class Message extends BaseModel
{
    protected string $table = 'messages';

    public function getConversation(int $user1Id, int $user2Id, int $limit = 50): array
    {
        return $this->fetchAll(
            "SELECT m.*, 
                    s.username as sender_name,
                    r.username as receiver_name
             FROM {$this->table} m
             LEFT JOIN users s ON m.sender_id = s.id
             LEFT JOIN users r ON m.receiver_id = r.id
             WHERE (m.sender_id = ? AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id = ?)
             ORDER BY m.created_at ASC
             LIMIT ?",
            [$user1Id, $user2Id, $user2Id, $user1Id, $limit]
        );
    }

    public function getInbox(int $userId, int $limit = 20): array
    {
        return $this->fetchAll(
            "SELECT m.*, 
                    u.username as other_name,
                    (SELECT COUNT(*) FROM {$this->table} 
                     WHERE sender_id = m.sender_id AND receiver_id = ? AND is_read = 0) as unread_count
             FROM {$this->table} m
             LEFT JOIN users u ON (
                 CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
             ) = u.id
             WHERE m.sender_id = ? OR m.receiver_id = ?
             GROUP BY LEAST(m.sender_id, m.receiver_id), GREATEST(m.sender_id, m.receiver_id)
             ORDER BY MAX(m.created_at) DESC
             LIMIT ?",
            [$userId, $userId, $userId, $userId, $limit]
        );
    }

    public function getUnreadCount(int $userId): int
    {
        $result = $this->fetchOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE receiver_id = ? AND is_read = 0",
            [$userId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public function markRead(int $senderId, int $receiverId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET is_read = 1, read_at = NOW() 
             WHERE sender_id = ? AND receiver_id = ? AND is_read = 0"
        );
        return $stmt->execute([$senderId, $receiverId]);
    }

    public function send(int $senderId, int $receiverId, string $message, ?string $subject = null): int
    {
        return $this->create([
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'subject'     => $subject,
            'message'     => $message,
            'is_read'     => 0,
        ]);
    }
}
