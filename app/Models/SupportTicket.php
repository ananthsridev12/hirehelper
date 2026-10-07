<?php

namespace App\Models;

class SupportTicket extends BaseModel
{
    protected string $table = 'support_tickets';

    public function allWithUser(): array
    {
        $stmt = $this->db()->query(
            "SELECT t.*, u.name AS user_name, u.email AS user_email
             FROM support_tickets t INNER JOIN users u ON u.id = t.user_id
             ORDER BY (t.status = 'open') DESC, t.created_at DESC"
        );
        return $stmt->fetchAll();
    }
}
