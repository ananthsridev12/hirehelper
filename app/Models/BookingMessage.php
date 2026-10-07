<?php

namespace App\Models;

class BookingMessage extends BaseModel
{
    protected string $table = 'booking_messages';

    public function forBooking(int $bookingId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT m.*, u.name AS sender_name, u.role AS sender_role
             FROM booking_messages m INNER JOIN users u ON u.id = m.sender_id
             WHERE m.booking_id = ? ORDER BY m.created_at ASC"
        );
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }
}
