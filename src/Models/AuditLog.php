<?php
declare(strict_types=1);

namespace App\Models;

class AuditLog extends BaseModel
{
    protected string $table = 'audit_log';

    public function log(int $userId, string $action, ?string $details = null, ?array $data = null): int
    {
        return $this->create([
            'user_id'     => $userId,
            'action'      => $action,
            'details'     => $details,
            'data'        => $data ? json_encode($data) : null,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ]);
    }

    public function getRecent(int $limit = 50): array
    {
        return $this->fetchAll(
            "SELECT al.*, u.username
             FROM {$this->table} al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function getByUser(int $userId, int $limit = 50): array
    {
        return $this->findAll(['user_id' => $userId], 'created_at DESC', $limit);
    }

    public function getByAction(string $action, int $limit = 50): array
    {
        return $this->findAll(['action' => $action], 'created_at DESC', $limit);
    }
}
