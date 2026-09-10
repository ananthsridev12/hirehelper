<?php

namespace App\Controllers\Provider;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Models\Booking;
use App\Models\ProviderProfile;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);

        $bookings = (new Booking())->forProvider(Auth::id());
        $profile = (new ProviderProfile())->findByUserId(Auth::id());
        $rating = (new ProviderProfile())->ratingSummary(Auth::id());

        view('provider/dashboard', [
            'bookings' => $bookings,
            'profile' => $profile,
            'rating' => $rating,
        ], 'main');
    }

    public function updateStatus(string $id): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);
        $this->verifyCsrf();

        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['provider_id'] !== Auth::id()) {
            Response::notFound();
            return;
        }

        $newStatus = (string) Request::input('status', '');
        $transitions = [
            Booking::STATUS_ASSIGNED => Booking::STATUS_IN_PROGRESS,
            Booking::STATUS_IN_PROGRESS => Booking::STATUS_COMPLETED,
        ];

        if (!isset($transitions[$booking['status']]) || $transitions[$booking['status']] !== $newStatus) {
            Flash::error('Invalid status change.');
            redirect('/bookings/' . $id);
            return;
        }

        $bookingModel->updateStatus((int) $id, $newStatus);
        Flash::success('Booking updated.');
        redirect('/bookings/' . $id);
    }
}
