<?php

namespace App\Models;

class Booking extends BaseModel
{
    protected string $table = 'bookings';

    public const STATUS_PENDING = 'pending';
    public const STATUS_OFFERED = 'offered';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    private const DETAIL_SELECT = "
        SELECT b.*,
               s.name AS service_name, s.slug AS service_slug,
               c.id AS category_id, c.name AS category_name,
               cust.name AS customer_name, cust.phone AS customer_phone,
               prov.name AS provider_name, prov.phone AS provider_phone,
               a.line1, a.line2, a.city, a.state, a.pincode, a.phone AS address_phone,
               a.label AS address_label, a.lat AS address_lat, a.lng AS address_lng
        FROM bookings b
        INNER JOIN services s ON s.id = b.service_id
        INNER JOIN categories c ON c.id = s.category_id
        INNER JOIN users cust ON cust.id = b.customer_id
        LEFT JOIN users prov ON prov.id = b.provider_id
        INNER JOIN addresses a ON a.id = b.address_id
    ";

    public function forCustomer(int $customerId): array
    {
        $stmt = $this->db()->prepare(self::DETAIL_SELECT . " WHERE b.customer_id = ? ORDER BY b.created_at DESC");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function forProvider(int $providerId): array
    {
        $stmt = $this->db()->prepare(self::DETAIL_SELECT . " WHERE b.provider_id = ? ORDER BY b.scheduled_date ASC, b.created_at DESC");
        $stmt->execute([$providerId]);
        return $stmt->fetchAll();
    }

    public function detail(int $id): ?array
    {
        $stmt = $this->db()->prepare(self::DETAIL_SELECT . " WHERE b.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function allDetailed(?string $status = null): array
    {
        $sql = self::DETAIL_SELECT;
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE b.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY b.created_at DESC";
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Admin offers the job to a provider. The provider must still accept
     * it (acceptOffer) before it's really theirs -- mirrors how Urban
     * Company-style apps let a professional decline a lead.
     */
    public function offerToProvider(int $bookingId, int $providerId): bool
    {
        return $this->update($bookingId, [
            'provider_id' => $providerId,
            'status' => self::STATUS_OFFERED,
        ]);
    }

    public function acceptOffer(int $bookingId): string
    {
        $otp = (string) random_int(1000, 9999);
        $this->update($bookingId, [
            'status' => self::STATUS_ASSIGNED,
            'start_otp' => $otp,
        ]);
        return $otp;
    }

    public function rejectOffer(int $bookingId): bool
    {
        return $this->update($bookingId, [
            'provider_id' => null,
            'status' => self::STATUS_PENDING,
            'start_otp' => null,
        ]);
    }

    /**
     * Provider enters the OTP shown in the customer's app/booking page to
     * prove they're on site before the job can start.
     */
    public function startWithOtp(int $bookingId, string $otp, string $expectedOtp): bool
    {
        if (!hash_equals($expectedOtp, $otp)) {
            return false;
        }
        return $this->update($bookingId, ['status' => self::STATUS_IN_PROGRESS]);
    }

    public function updateStatus(int $bookingId, string $status): bool
    {
        return $this->update($bookingId, ['status' => $status]);
    }
}
