<?php

namespace App\Models;

class Address extends BaseModel
{
    protected string $table = 'addresses';

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId, 'is_default DESC, id DESC');
    }

    public function belongsToUser(int $addressId, int $userId): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$addressId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function clearDefault(int $userId): void
    {
        $stmt = $this->db()->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
}
