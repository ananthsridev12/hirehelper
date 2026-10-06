<?php

namespace App\Controllers\Api;

use App\Core\Geo;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Models\Address;
use App\Models\Booking;
use App\Models\ProviderLocation;
use App\Models\Review;
use App\Models\Service;

class BookingController extends BaseApiController
{
    private const ALLOWED_SLOTS = [
        '09:00 AM - 11:00 AM', '11:00 AM - 01:00 PM', '02:00 PM - 04:00 PM',
        '04:00 PM - 06:00 PM', '06:00 PM - 08:00 PM',
    ];

    public function index(): void
    {
        $user = $this->authenticate();
        Response::json(['bookings' => (new Booking())->forCustomer((int) $user['id'])]);
    }

    public function store(string $serviceSlug): void
    {
        $user = $this->authenticate();
        $this->requireRole($user, 'customer');

        $service = (new Service())->withCategoryBySlug($serviceSlug);
        if (!$service) {
            $this->fail('Service not found.', 404);
            return;
        }

        $addressId = (int) Request::input('address_id', 0);
        $date = (string) Request::input('scheduled_date', '');
        $slot = (string) Request::input('scheduled_time_slot', '');
        $notes = (string) Request::input('notes', '');

        $address = (new Address())->belongsToUser($addressId, (int) $user['id']);
        if (!$address) $this->fail('Please select a valid address.');
        if ($date === '' || $date < date('Y-m-d')) $this->fail('Please choose a valid date (today or later).');
        if (!in_array($slot, self::ALLOWED_SLOTS, true)) $this->fail('Please choose a valid time slot.');

        $bookingModel = new Booking();
        $bookingId = $bookingModel->create([
            'customer_id' => $user['id'],
            'service_id' => $service['id'],
            'provider_id' => null,
            'address_id' => $addressId,
            'scheduled_date' => $date,
            'scheduled_time_slot' => $slot,
            'status' => Booking::STATUS_PENDING,
            'price' => $service['price'],
            'payment_status' => 'unpaid',
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Response::json(['booking' => $bookingModel->detail($bookingId)], 201);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();
        $booking = (new Booking())->detail((int) $id);
        if (!$booking) {
            $this->fail('Booking not found.', 404);
            return;
        }
        $isOwner = (int) $booking['customer_id'] === (int) $user['id'];
        $isProvider = (int) $booking['provider_id'] === (int) $user['id'];
        if (!$isOwner && !$isProvider && $user['role'] !== 'admin') {
            $this->fail('Booking not found.', 404);
            return;
        }

        $distanceKm = null;
        if ($booking['status'] === Booking::STATUS_IN_PROGRESS && $booking['provider_id'] && $booking['address_lat'] !== null) {
            $location = (new ProviderLocation())->forProvider((int) $booking['provider_id']);
            if ($location) {
                $distanceKm = round(Geo::distanceKm(
                    (float) $booking['address_lat'], (float) $booking['address_lng'],
                    (float) $location['lat'], (float) $location['lng']
                ), 1);
            }
        }

        $review = (new Review())->findByBooking((int) $booking['id']);
        Response::json(['booking' => $booking, 'review' => $review, 'distance_km' => $distanceKm]);
    }

    public function cancel(string $id): void
    {
        $user = $this->authenticate();
        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['customer_id'] !== (int) $user['id']) {
            $this->fail('Booking not found.', 404);
            return;
        }
        if (!in_array($booking['status'], [Booking::STATUS_PENDING, Booking::STATUS_OFFERED, Booking::STATUS_ASSIGNED], true)) {
            $this->fail('This booking can no longer be cancelled.');
            return;
        }

        $bookingModel->updateStatus((int) $id, Booking::STATUS_CANCELLED);
        if ($booking['provider_id']) {
            Notifier::notify((int) $booking['provider_id'], 'Booking cancelled', 'The customer cancelled a job that was offered to you.', (int) $id);
        }
        Response::json(['ok' => true]);
    }

    public function review(string $id): void
    {
        $user = $this->authenticate();
        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['customer_id'] !== (int) $user['id']) {
            $this->fail('Booking not found.', 404);
            return;
        }
        if ($booking['status'] !== Booking::STATUS_COMPLETED) {
            $this->fail('You can only review completed bookings.');
            return;
        }

        $reviewModel = new Review();
        if ($reviewModel->findByBooking((int) $id)) {
            $this->fail('You have already reviewed this booking.');
            return;
        }

        $rating = max(1, min(5, (int) Request::input('rating', 0)));
        $comment = (string) Request::input('comment', '');

        $reviewModel->create([
            'booking_id' => $id,
            'customer_id' => $user['id'],
            'provider_id' => $booking['provider_id'],
            'rating' => $rating,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Response::json(['ok' => true], 201);
    }
}
