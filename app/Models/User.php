<?php

namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';

    public const REFERRAL_BONUS = 100.00;

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

    public function findByReferralCode(string $code): ?array
    {
        return $this->findBy('referral_code', strtoupper($code));
    }

    public function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while ($this->findByReferralCode($code));
        return $code;
    }

    /**
     * Positive $amount credits the wallet, negative debits it. Every
     * change is logged to wallet_transactions for a visible history.
     */
    public function adjustWallet(int $userId, float $amount, string $reason, ?int $bookingId = null): void
    {
        $stmt = $this->db()->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);

        $log = $this->db()->prepare(
            "INSERT INTO wallet_transactions (user_id, amount, reason, booking_id, created_at) VALUES (?, ?, ?, ?, ?)"
        );
        $log->execute([$userId, $amount, $reason, $bookingId, date('Y-m-d H:i:s')]);
    }

    public function walletHistory(int $userId): array
    {
        $stmt = $this->db()->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
