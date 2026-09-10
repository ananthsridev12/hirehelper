<?php

namespace App\Models;

class Category extends BaseModel
{
    protected string $table = 'categories';

    public function active(): array
    {
        $stmt = $this->db()->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }
}
