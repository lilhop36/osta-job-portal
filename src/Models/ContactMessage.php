<?php
declare(strict_types=1);

namespace App\Models;

class ContactMessage extends BaseModel
{
    protected string $table = 'contact_messages';

    public function getUnread(int $limit = 50): array
    {
        return $this->findAll(['is_read' => 0], 'created_at DESC', $limit);
    }

    public function markRead(int $id): bool
    {
        return $this->update($id, ['is_read' => 1]);
    }

    public function countUnread(): int
    {
        return $this->count(['is_read' => 0]);
    }
}
