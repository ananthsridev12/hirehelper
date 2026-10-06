<?php

namespace App\Controllers\Api;

use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Models\Booking;
use App\Models\ProviderLocation;
use App\Models\ProviderProfile;

class ProviderController extends BaseApiController
{
    public function jobs(): void
    {
        $user = $this->authenticate();
        $this->requireRole($user, 'provider');
        Response::json(['bookings' => (new Booking())->forProvider((int) $user['id'])]);
    }

    private function ownedOfferedBooking(array $user, string $id): array
    {
        $booking = (new Booking())->find((int) $id);
        if (!$booking || (int) $booking['provider_id'] !== (int) $user['id'] || $booking['status'] !== Booking::STATUS_OFFERED) {
            $this->fail('Job offer not found.', 404);
        }
        return $booking;
    }

    public function accept(string $id): void
    {
        $user = $this->authenticate();
        $this->requireRole($user, 'provider');
        $booking = $this->ownedOfferedBooking($user, $id);

        $otp = (new Booking())->acceptOffer((int) $id);
        Notifier::notify(
            (int) $booking['customer_id'],
            'Professional assigned',
            "Share this code with your professional when they arrive: {$otp}",
            (int) $id
        );
        Response::json(['booking' => (new Booking())->detail((int) $id)]);
    }

    public function reject(string $id): void
    {
        $user = $this->authenticate();
        $this->requireRole($user, 'provider');
        $this->ownedOfferedBooking($user, $id);

        (new Booking())->rejectOffer((int) $id);
        Response::json(['ok' => true]);
    }

    public function start(string $id): void
    {
        $user = $this->authenticate();
        $this->requireRole($user, 'provider');

        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['provider_id'] !== (int) $user['id'] || $booking['status'] !== Booking::STATUS_ASSIGNED) {
            $this->fail('Job not found.', 404);
            return;
        }

        $otp = (string) Request::input('start_otp', '');
        if (!$bookingModel->startWithOtp((int) $id, $otp, (string) $booking['start_otp'])) {
            $this->fail('That code doesn\'t match.');
            return;
        }

        Notifier::notify((int) $booking['customer_id'], 'Job started', 'Your professional has started the job.', (int) $id);
        Response::json(['booking' => $bookingModel->detail((int) $id)]);
    }

    public function complete(string $id): void
    {
        $user = $this->authenticate();
        $this->requireRole($user, 'provider');

        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['provider_id'] !== (int) $user['id'] || $booking['status'] !== Booking::STATUS_IN_PROGRESS) {
            $this->fail('Job not found.', 404);
            return;
        }

        $bookingModel->updateStatus((int) $id, Booking::STATUS_COMPLETED);
        Notifier::notify((int) $booking['customer_id'], 'Job completed', 'Your service is marked complete. Please rate your experience.', (int) $id);
        Response::json(['booking' => $bookingModel->detail((int) $id)]);
    }

    /**
     * The Flutter app calls this every ~30-60s while a job is in
     * progress (via geolocator's background position stream).
     */
    public function pingLocation(): void
    {
        $user = $this->authenticate();
        $this->requireRole($user, 'provider');

        $lat = Request::input('lat', '');
        $lng = Request::input('lng', '');
        if (!is_numeric($lat) || !is_numeric($lng)) {
            $this->fail('lat and lng are required.');
            return;
        }

        (new ProviderLocation())->ping((int) $user['id'], (float) $lat, (float) $lng);
        Response::json(['ok' => true]);
    }

    public function toggleAvailability(): void
    {
        $user = $this->authenticate();
        $this->requireRole($user, 'provider');

        $profileModel = new ProviderProfile();
        $profile = $profileModel->findByUserId((int) $user['id']);
        $newValue = $profile && $profile['is_available'] ? 0 : 1;
        $profileModel->updateForUser((int) $user['id'], ['is_available' => $newValue]);
        Response::json(['is_available' => (bool) $newValue]);
    }
}
