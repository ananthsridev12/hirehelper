<?php

namespace App\Models;

class ProviderLocation extends BaseModel
{
    protected string $table = 'provider_locations';
    protected string $primaryKey = 'provider_id';

    public function ping(int $providerId, float $lat, float $lng): void
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO provider_locations (provider_id, lat, lng) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE lat = VALUES(lat), lng = VALUES(lng)"
        );
        $stmt->execute([$providerId, $lat, $lng]);
    }

    public function forProvider(int $providerId): ?array
    {
        return $this->find($providerId);
    }
}
