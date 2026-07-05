<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

class NotificationService
{
    private static ?self $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $this->pdo = Connection::getInstance()->getPdo();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function countUnread(?int $userId = null): int
    {
        if ($userId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM notifications 
                WHERE (target = 'all' 
                    OR (target = 'user' AND target_id = ?)
                    OR (target = 'department' AND target_id IN (SELECT department_id FROM users WHERE id = ?))
                    OR (target = 'job' AND target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))
                AND status = 'unread'
            ");
            $stmt->execute([$userId, $userId, $userId]);
            return (int) $stmt->fetchColumn();
        }

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE status = 'unread'");
        return (int) $stmt->fetchColumn();
    }

    public function getNotifications(int $userId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notifications 
            WHERE (target = 'all' 
                OR (target = 'user' AND target_id = ?))
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function markAsRead(int $notificationId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notifications SET status = 'read' WHERE id = ?");
        return $stmt->execute([$notificationId]);
    }
}
