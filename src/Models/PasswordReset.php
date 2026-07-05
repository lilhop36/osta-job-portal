<?php
declare(strict_types=1);

namespace App\Models;

class PasswordReset extends BaseModel
{
    protected string $table = 'password_resets';

    public function createToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->create([
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'used'       => 0,
        ]);
        return $token;
    }

    public function findValidToken(string $token): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM {$this->table} 
             WHERE token = ? AND used = 0 AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1",
            [$token]
        );
    }

    public function markUsed(string $token): bool
    {
        $record = $this->findValidToken($token);
        if (!$record) return false;
        return $this->update($record['id'], ['used' => 1]);
    }

    public function invalidateUserTokens(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET used = 1 WHERE user_id = ? AND used = 0"
        );
        return $stmt->execute([$userId]);
    }
}
