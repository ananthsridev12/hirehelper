<?php

namespace App\Models;

class ProviderProfile extends BaseModel
{
    protected string $table = 'provider_profiles';
    protected string $primaryKey = 'user_id';

    public function findByUserId(int $userId): ?array
    {
        return $this->findBy('user_id', $userId);
    }

    public function createForUser(int $userId, array $data): void
    {
        $data['user_id'] = $userId;
        $this->create($data);
    }

    public function updateForUser(int $userId, array $data): bool
    {
        return $this->update($userId, $data);
    }

    public function categories(int $providerId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT c.* FROM categories c
             INNER JOIN provider_categories pc ON pc.category_id = c.id
             WHERE pc.provider_id = ? ORDER BY c.name ASC"
        );
        $stmt->execute([$providerId]);
        return $stmt->fetchAll();
    }

    public function setCategories(int $providerId, array $categoryIds): void
    {
        $stmt = $this->db()->prepare("DELETE FROM provider_categories WHERE provider_id = ?");
        $stmt->execute([$providerId]);

        if (empty($categoryIds)) {
            return;
        }
        $insert = $this->db()->prepare("INSERT INTO provider_categories (provider_id, category_id) VALUES (?, ?)");
        foreach ($categoryIds as $categoryId) {
            $insert->execute([$providerId, (int) $categoryId]);
        }
    }

    public function allWithUser(): array
    {
        $stmt = $this->db()->query(
            "SELECT u.id, u.name, u.email, u.phone, u.status, pp.bio, pp.photo_path, pp.experience_years, pp.city, pp.is_verified, pp.is_available
             FROM users u INNER JOIN provider_profiles pp ON pp.user_id = u.id
             WHERE u.role = 'provider' ORDER BY u.name ASC"
        );
        return $stmt->fetchAll();
    }

    public function ratingSummary(int $providerId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT AVG(rating) AS avg_rating, COUNT(id) AS review_count FROM reviews WHERE provider_id = ?"
        );
        $stmt->execute([$providerId]);
        return $stmt->fetch() ?: ['avg_rating' => null, 'review_count' => 0];
    }
}
