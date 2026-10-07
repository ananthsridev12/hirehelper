<?php

namespace App\Models;

class Coupon extends BaseModel
{
    protected string $table = 'coupons';

    public function findValidByCode(string $code): ?array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM coupons
             WHERE code = ? AND is_active = 1
               AND (expires_at IS NULL OR expires_at >= CURDATE())
               AND (usage_limit IS NULL OR used_count < usage_limit)
             LIMIT 1"
        );
        $stmt->execute([strtoupper($code)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function calculateDiscount(array $coupon, float $amount): float
    {
        if ($amount < (float) $coupon['min_booking_amount']) {
            return 0.0;
        }
        if ($coupon['discount_type'] === 'percent') {
            $discount = $amount * ((float) $coupon['discount_value'] / 100);
            if ($coupon['max_discount'] !== null) {
                $discount = min($discount, (float) $coupon['max_discount']);
            }
        } else {
            $discount = (float) $coupon['discount_value'];
        }
        return round(min($discount, $amount), 2);
    }

    public function incrementUsage(int $id): void
    {
        $stmt = $this->db()->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
}
