<?php

namespace App\Models;

class Review extends BaseModel
{
    protected string $table = 'reviews';

    public function findByBooking(int $bookingId): ?array
    {
        return $this->findBy('booking_id', $bookingId);
    }

    public function forProvider(int $providerId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT r.*, cust.name AS customer_name, s.name AS service_name
             FROM reviews r
             INNER JOIN users cust ON cust.id = r.customer_id
             INNER JOIN bookings b ON b.id = r.booking_id
             INNER JOIN services s ON s.id = b.service_id
             WHERE r.provider_id = ? ORDER BY r.created_at DESC"
        );
        $stmt->execute([$providerId]);
        return $stmt->fetchAll();
    }
}
