<?php
/**
 * Notification service placeholder for centralizing notification reads/writes.
 */
class NotificationService {
    public static function countUnread(PDO $pdo, ?int $userId = null): int {
        if ($userId !== null) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications 
                WHERE (target = 'all' 
                    OR (target = 'user' AND target_id = ?)
                    OR (target = 'department' AND target_id IN (SELECT department_id FROM users WHERE id = ?))
                    OR (target = 'job' AND target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))
                AND status = 'unread'");
            $stmt->execute([$userId, $userId, $userId]);
            return (int) $stmt->fetchColumn();
        }

        $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE status = 'unread'");
        return (int) $stmt->fetchColumn();
    }
}
