<?php

namespace App\Models;

class Service extends BaseModel
{
    protected string $table = 'services';

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    public function activeByCategory(int $categoryId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM services WHERE category_id = ? AND is_active = 1 ORDER BY name ASC"
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    public function withCategory(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT s.*, c.name AS category_name, c.slug AS category_slug
             FROM services s INNER JOIN categories c ON c.id = s.category_id
             WHERE s.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function withCategoryBySlug(string $slug): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT s.*, c.name AS category_name, c.slug AS category_slug
             FROM services s INNER JOIN categories c ON c.id = s.category_id
             WHERE s.slug = ? AND s.is_active = 1 LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function ratingSummary(int $serviceId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
             FROM reviews r INNER JOIN bookings b ON b.id = r.booking_id
             WHERE b.service_id = ?"
        );
        $stmt->execute([$serviceId]);
        return $stmt->fetch() ?: ['avg_rating' => null, 'review_count' => 0];
    }
}
