<?php

namespace App\Models;

class ServiceablePincode extends BaseModel
{
    protected string $table = 'serviceable_pincodes';

    /**
     * An empty table means no restriction is configured yet -- every
     * pincode is serviceable, so a fresh install never blocks bookings.
     */
    public function isServiceable(string $pincode): bool
    {
        $total = (int) $this->db()->query("SELECT COUNT(*) FROM serviceable_pincodes WHERE is_active = 1")->fetchColumn();
        if ($total === 0) {
            return true;
        }
        $stmt = $this->db()->prepare("SELECT COUNT(*) FROM serviceable_pincodes WHERE pincode = ? AND is_active = 1");
        $stmt->execute([$pincode]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
