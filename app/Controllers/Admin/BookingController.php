<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Models\Booking;
use App\Models\User;

class BookingController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $status = Request::query('status');
        $bookings = (new Booking())->allDetailed($status ?: null);

        $userModel = new User();
        $eligibleProviders = [];
        foreach ($bookings as $booking) {
            if ($booking['status'] === Booking::STATUS_PENDING && !isset($eligibleProviders[$booking['category_id']])) {
                $eligibleProviders[$booking['category_id']] = $userModel->providersByCategory((int) $booking['category_id']);
            }
        }

        view('admin/bookings', ['bookings' => $bookings, 'status' => $status, 'eligibleProviders' => $eligibleProviders], 'admin');
    }

    public function assign(string $id): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);
        $this->verifyCsrf();

        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking) {
            redirect('/admin/bookings');
            return;
        }

        $providerId = (int) Request::input('provider_id', 0);
        $userModel = new User();
        $provider = $userModel->find($providerId);

        if (!$provider || $provider['role'] !== 'provider') {
            Flash::error('Please choose a valid provider.');
            redirect('/admin/bookings');
            return;
        }

        $bookingModel->assignProvider((int) $id, $providerId);
        Flash::success('Provider assigned.');
        redirect('/admin/bookings');
    }
}
