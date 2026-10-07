<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Dispatcher;
use App\Core\Flash;
use App\Core\Geo;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Core\Upload;
use App\Models\Address;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\Coupon;
use App\Models\ProviderLocation;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceablePincode;
use App\Models\SupportTicket;

class BookingController extends BaseController
{
    private const ALLOWED_SLOTS = [
        '09:00 AM - 11:00 AM', '11:00 AM - 01:00 PM', '02:00 PM - 04:00 PM',
        '04:00 PM - 06:00 PM', '06:00 PM - 08:00 PM',
    ];

    public function create(string $serviceSlug): void
    {
        $this->requireRole(Auth::ROLE_CUSTOMER);

        $service = (new Service())->withCategoryBySlug($serviceSlug);
        if (!$service) {
            Response::notFound();
            return;
        }

        $addresses = (new Address())->forUser(Auth::id());
        view('bookings/create', ['service' => $service, 'addresses' => $addresses, 'errors' => [], 'old' => []]);
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
        $couponCode = trim((string) Request::input('coupon_code', ''));

        $errors = [];
        $address = $addressModel->belongsToUser($addressId, Auth::id());
        if (!$address) {
            $errors[] = 'Please select a valid delivery address.';
        } elseif (!(new ServiceablePincode())->isServiceable($address['pincode'])) {
            $errors[] = 'Sorry, we don\'t serve pincode ' . $address['pincode'] . ' yet.';
        }
        $today = date('Y-m-d');
        if ($date === '' || $date < $today) {
            $errors[] = 'Please choose a valid date (today or later).';
        }
        if (!in_array($slot, self::ALLOWED_SLOTS, true)) {
            $errors[] = 'Please choose a valid time slot.';
        }

        $coupon = null;
        $discount = 0.0;
        if ($couponCode !== '') {
            $coupon = (new Coupon())->findValidByCode($couponCode);
            if (!$coupon) {
                $errors[] = 'That coupon code is invalid or expired.';
            } else {
                $discount = (new Coupon())->calculateDiscount($coupon, (float) $service['price']);
                if ($discount <= 0) {
                    $errors[] = 'This coupon doesn\'t apply to this booking (minimum ' . money($coupon['min_booking_amount']) . ').';
                }
            }
        }

        if (!empty($errors)) {
            $addresses = $addressModel->forUser(Auth::id());
            view('bookings/create', ['service' => $service, 'addresses' => $addresses, 'errors' => $errors, 'old' => Request::all()]);
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
            'coupon_id' => $coupon['id'] ?? null,
            'discount_amount' => $discount,
            'payment_status' => 'unpaid',
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($coupon) {
            (new Coupon())->incrementUsage((int) $coupon['id']);
        }

        $assigned = Dispatcher::autoAssign($bookingId);
        Flash::success($assigned
            ? 'Booking placed! A professional has been matched and notified.'
            : 'Booking placed! We will assign a professional shortly.');
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

        $distanceKm = null;
        if ($booking['status'] === Booking::STATUS_IN_PROGRESS && $booking['provider_id'] && $booking['address_lat'] !== null) {
            $location = (new ProviderLocation())->forProvider((int) $booking['provider_id']);
            if ($location) {
                $distanceKm = Geo::distanceKm(
                    (float) $booking['address_lat'],
                    (float) $booking['address_lng'],
                    (float) $location['lat'],
                    (float) $location['lng']
                );
            }
        }

        $messages = in_array($booking['status'], ['assigned', 'in_progress', 'completed'], true)
            ? (new BookingMessage())->forBooking((int) $id)
            : [];

        view('bookings/show', [
            'booking' => $booking,
            'review' => $review,
            'distanceKm' => $distanceKm,
            'messages' => $messages,
            'slots' => self::ALLOWED_SLOTS,
        ]);
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
        if (!in_array($booking['status'], [Booking::STATUS_PENDING, Booking::STATUS_OFFERED, Booking::STATUS_ASSIGNED], true)) {
            Flash::error('This booking can no longer be cancelled.');
            redirect('/bookings/' . $id);
            return;
        }

        $bookingModel->updateStatus((int) $id, Booking::STATUS_CANCELLED);
        if ($booking['provider_id']) {
            Notifier::notify((int) $booking['provider_id'], 'Booking cancelled', 'The customer cancelled a job that was offered to you.', (int) $id);
        }
        Flash::success('Booking cancelled.');
        redirect('/bookings/' . $id);
    }

    public function reschedule(string $id): void
    {
        $this->requireRole(Auth::ROLE_CUSTOMER);
        $this->verifyCsrf();

        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['customer_id'] !== Auth::id()) {
            Response::notFound();
            return;
        }
        if (!in_array($booking['status'], [Booking::STATUS_PENDING, Booking::STATUS_OFFERED, Booking::STATUS_ASSIGNED], true)) {
            Flash::error('This booking can no longer be rescheduled.');
            redirect('/bookings/' . $id);
            return;
        }

        $date = (string) Request::input('scheduled_date', '');
        $slot = (string) Request::input('scheduled_time_slot', '');
        if ($date < date('Y-m-d') || !in_array($slot, self::ALLOWED_SLOTS, true)) {
            Flash::error('Please choose a valid date and time slot.');
            redirect('/bookings/' . $id);
            return;
        }

        $bookingModel->reschedule((int) $id, $date, $slot);
        if ($booking['provider_id']) {
            Notifier::notify((int) $booking['provider_id'], 'Booking rescheduled', "New time: {$date}, {$slot}.", (int) $id);
        }
        Flash::success('Booking rescheduled.');
        redirect('/bookings/' . $id);
    }

    public function rebook(string $id): void
    {
        $this->requireRole(Auth::ROLE_CUSTOMER);
        $this->verifyCsrf();

        $booking = (new Booking())->find((int) $id);
        if (!$booking || (int) $booking['customer_id'] !== Auth::id()) {
            Response::notFound();
            return;
        }

        $service = (new Service())->find((int) $booking['service_id']);
        if (!$service) {
            Flash::error('That service is no longer available.');
            redirect('/bookings');
            return;
        }

        redirect('/book/' . $service['slug']);
    }

    public function invoice(string $id): void
    {
        $this->requireLogin();
        $booking = (new Booking())->detail((int) $id);
        if (!$booking) {
            Response::notFound();
            return;
        }
        $isOwner = (int) $booking['customer_id'] === Auth::id();
        if (!$isOwner && Auth::role() !== Auth::ROLE_ADMIN) {
            Response::notFound();
            return;
        }
        view('bookings/invoice', ['booking' => $booking], null);
    }

    public function sendMessage(string $id): void
    {
        $this->requireLogin();
        $this->verifyCsrf();

        $booking = (new Booking())->find((int) $id);
        $isOwner = $booking && (int) $booking['customer_id'] === Auth::id();
        $isProvider = $booking && (int) $booking['provider_id'] === Auth::id();
        if (!$booking || (!$isOwner && !$isProvider)) {
            Response::notFound();
            return;
        }

        $message = trim((string) Request::input('message', ''));
        if ($message !== '') {
            (new BookingMessage())->create([
                'booking_id' => $id,
                'sender_id' => Auth::id(),
                'message' => mb_substr($message, 0, 1000),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $recipientId = $isOwner ? $booking['provider_id'] : $booking['customer_id'];
            if ($recipientId) {
                Notifier::notify((int) $recipientId, 'New message', mb_substr($message, 0, 100), (int) $id);
            }
        }
        redirect('/bookings/' . $id);
    }

    public function reportIssue(string $id): void
    {
        $this->requireLogin();
        $this->verifyCsrf();

        $booking = (new Booking())->find((int) $id);
        if (!$booking || (int) $booking['customer_id'] !== Auth::id()) {
            Response::notFound();
            return;
        }

        $message = trim((string) Request::input('message', ''));
        if ($message === '') {
            Flash::error('Please describe the issue.');
            redirect('/bookings/' . $id);
            return;
        }

        (new SupportTicket())->create([
            'user_id' => Auth::id(),
            'booking_id' => $id,
            'subject' => 'Issue with booking #' . $id,
            'message' => $message,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Flash::success('Thanks — we\'ll look into it and get back to you.');
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

        try {
            $photoPath = Upload::image(Request::file('photo'), 'reviews');
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
            redirect('/bookings/' . $id);
            return;
        }

        $reviewModel->create([
            'booking_id' => $id,
            'customer_id' => Auth::id(),
            'provider_id' => $booking['provider_id'],
            'rating' => $rating,
            'comment' => $comment,
            'photo_path' => $photoPath,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Flash::success('Thanks for your feedback!');
        redirect('/bookings/' . $id);
    }
}
