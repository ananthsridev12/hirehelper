<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Models\Address;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Service;

class BookingController extends BaseController
{
    public function create(string $serviceSlug): void
    {
        $this->requireRole(Auth::ROLE_CUSTOMER);

        $service = (new Service())->withCategoryBySlug($serviceSlug);
        if (!$service) {
            Response::notFound();
            return;
        }

        $addresses = (new Address())->forUser(Auth::id());
        view('bookings/create', ['service' => $service, 'addresses' => $addresses, 'errors' => []]);
    }

    public function store(string $serviceSlug): void
    {
        $this->requireRole(Auth::ROLE_CUSTOMER);
        $this->verifyCsrf();

        $service = (new Service())->withCategoryBySlug($serviceSlug);
        if (!$service) {
            Response::notFound();
            return;
        }

        $addressModel = new Address();
        $addressId = (int) Request::input('address_id', 0);
        $date = (string) Request::input('scheduled_date', '');
        $slot = (string) Request::input('scheduled_time_slot', '');
        $notes = (string) Request::input('notes', '');

        $errors = [];
        $address = $addressModel->belongsToUser($addressId, Auth::id());
        if (!$address) {
            $errors[] = 'Please select a valid delivery address.';
        }
        $today = date('Y-m-d');
        if ($date === '' || $date < $today) {
            $errors[] = 'Please choose a valid date (today or later).';
        }
        $allowedSlots = ['09:00 AM - 11:00 AM', '11:00 AM - 01:00 PM', '02:00 PM - 04:00 PM', '04:00 PM - 06:00 PM', '06:00 PM - 08:00 PM'];
        if (!in_array($slot, $allowedSlots, true)) {
            $errors[] = 'Please choose a valid time slot.';
        }

        if (!empty($errors)) {
            $addresses = $addressModel->forUser(Auth::id());
            view('bookings/create', ['service' => $service, 'addresses' => $addresses, 'errors' => $errors]);
            return;
        }

        $bookingModel = new Booking();
        $bookingId = $bookingModel->create([
            'customer_id' => Auth::id(),
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

        Flash::success('Booking placed! We will assign a professional shortly.');
        redirect('/bookings/' . $bookingId);
    }

    public function index(): void
    {
        $this->requireRole(Auth::ROLE_CUSTOMER);
        $bookings = (new Booking())->forCustomer(Auth::id());
        view('bookings/index', ['bookings' => $bookings]);
    }

    public function show(string $id): void
    {
        $this->requireLogin();
        $booking = (new Booking())->detail((int) $id);
        if (!$booking) {
            Response::notFound();
            return;
        }
        $isOwner = (int) $booking['customer_id'] === Auth::id();
        $isProvider = Auth::id() !== null && (int) $booking['provider_id'] === Auth::id();
        if (!$isOwner && !$isProvider && Auth::role() !== Auth::ROLE_ADMIN) {
            Response::notFound();
            return;
        }
        $review = (new Review())->findByBooking((int) $booking['id']);
        view('bookings/show', ['booking' => $booking, 'review' => $review]);
    }

    public function cancel(string $id): void
    {
        $this->requireRole(Auth::ROLE_CUSTOMER);
        $this->verifyCsrf();

        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['customer_id'] !== Auth::id()) {
            Response::notFound();
            return;
        }
        if (!in_array($booking['status'], [Booking::STATUS_PENDING, Booking::STATUS_ASSIGNED], true)) {
            Flash::error('This booking can no longer be cancelled.');
            redirect('/bookings/' . $id);
            return;
        }

        $bookingModel->updateStatus((int) $id, Booking::STATUS_CANCELLED);
        Flash::success('Booking cancelled.');
        redirect('/bookings/' . $id);
    }

    public function review(string $id): void
    {
        $this->requireRole(Auth::ROLE_CUSTOMER);
        $this->verifyCsrf();

        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['customer_id'] !== Auth::id()) {
            Response::notFound();
            return;
        }
        if ($booking['status'] !== Booking::STATUS_COMPLETED) {
            Flash::error('You can only review completed bookings.');
            redirect('/bookings/' . $id);
            return;
        }

        $reviewModel = new Review();
        if ($reviewModel->findByBooking((int) $id)) {
            Flash::error('You have already reviewed this booking.');
            redirect('/bookings/' . $id);
            return;
        }

        $rating = max(1, min(5, (int) Request::input('rating', 0)));
        $comment = (string) Request::input('comment', '');

        $reviewModel->create([
            'booking_id' => $id,
            'customer_id' => Auth::id(),
            'provider_id' => $booking['provider_id'],
            'rating' => $rating,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Flash::success('Thanks for your feedback!');
        redirect('/bookings/' . $id);
    }
}
