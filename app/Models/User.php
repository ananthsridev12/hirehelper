<?php

namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    public function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    /**
     * Eligible providers for a category, each carrying their last known
     * lat/lng (if they've ever pinged one) so callers can rank by
     * distance from the job address -- see Geo::distanceKm().
     */
    public function providersByCategory(int $categoryId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT u.*, pl.lat AS last_lat, pl.lng AS last_lng
             FROM users u
             INNER JOIN provider_profiles pp ON pp.user_id = u.id
             INNER JOIN provider_categories pc ON pc.provider_id = u.id
             LEFT JOIN provider_locations pl ON pl.provider_id = u.id
             WHERE pc.category_id = ? AND u.role = 'provider' AND u.status = 'active' AND pp.is_available = 1
             ORDER BY pp.is_verified DESC, u.name ASC"
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
}
