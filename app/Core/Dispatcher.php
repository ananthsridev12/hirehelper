<?php

namespace App\Core;

use App\Models\Booking;

/**
 * Urban-Company-style free auto-assignment: the moment a booking is
 * created, try to match it to the best available verified provider by
 * distance (when known) then rating -- no manual admin click, and no fee
 * charged to the provider for receiving the lead. If a provider declines,
 * the booking is re-dispatched to the next best candidate automatically.
 * Only when nobody is left does it fall back to "pending" for an admin to
 * assign by hand.
 */
class Dispatcher
{
    public static function autoAssign(int $bookingId): bool
    {
        $booking = (new Booking())->detail($bookingId);
        if (!$booking) {
            return false;
        }

        $candidate = self::pickCandidate((int) $booking['category_id'], $bookingId, $booking['address_lat'], $booking['address_lng']);
        if (!$candidate) {
            return false;
        }

        (new Booking())->offerToProvider($bookingId, (int) $candidate['id']);
        self::logOffer($bookingId, (int) $candidate['id']);
        Notifier::notify((int) $candidate['id'], 'New job offer', 'You have a new job to review.', $bookingId);
        return true;
    }

    private static function pickCandidate(int $categoryId, int $bookingId, $addressLat, $addressLng): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT u.id, pl.lat AS last_lat, pl.lng AS last_lng, COALESCE(AVG(r.rating), 0) AS avg_rating
             FROM users u
             INNER JOIN provider_profiles pp ON pp.user_id = u.id
             INNER JOIN provider_categories pc ON pc.provider_id = u.id
             LEFT JOIN provider_locations pl ON pl.provider_id = u.id
             LEFT JOIN reviews r ON r.provider_id = u.id
             WHERE pc.category_id = ?
               AND u.role = 'provider' AND u.status = 'active'
               AND pp.is_available = 1 AND pp.is_verified = 1
               AND u.id NOT IN (SELECT provider_id FROM booking_offers WHERE booking_id = ?)
             GROUP BY u.id, pl.lat, pl.lng"
        );
        $stmt->execute([$categoryId, $bookingId]);
        $candidates = $stmt->fetchAll();
        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as &$candidate) {
            $candidate['distance_km'] = null;
            if ($addressLat !== null && $addressLng !== null && $candidate['last_lat'] !== null && $candidate['last_lng'] !== null) {
                $candidate['distance_km'] = Geo::distanceKm(
                    (float) $addressLat, (float) $addressLng,
                    (float) $candidate['last_lat'], (float) $candidate['last_lng']
                );
            }
        }
        unset($candidate);

        usort($candidates, function ($a, $b) {
            if ($a['distance_km'] !== null || $b['distance_km'] !== null) {
                if ($a['distance_km'] === null) return 1;
                if ($b['distance_km'] === null) return -1;
                if ($a['distance_km'] !== $b['distance_km']) return $a['distance_km'] <=> $b['distance_km'];
            }
            return $b['avg_rating'] <=> $a['avg_rating'];
        });

        return $candidates[0];
    }

    public static function logOffer(int $bookingId, int $providerId): void
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO booking_offers (booking_id, provider_id, status, created_at) VALUES (?, ?, 'offered', ?)"
        );
        $stmt->execute([$bookingId, $providerId, date('Y-m-d H:i:s')]);
    }

    public static function markResponded(int $bookingId, int $providerId, string $status): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE booking_offers SET status = ?, responded_at = ? WHERE booking_id = ? AND provider_id = ? AND status = 'offered'"
        );
        $stmt->execute([$status, date('Y-m-d H:i:s'), $bookingId, $providerId]);
    }
}
