<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Dispatcher;
use App\Core\Flash;
use App\Core\Geo;
use App\Core\Notifier;
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
                $providers = $userModel->providersByCategory((int) $booking['category_id']);
                $eligibleProviders[$booking['category_id']] = $this->sortByDistance($providers, $booking);
            }
        }

        view('admin/bookings', ['bookings' => $bookings, 'status' => $status, 'eligibleProviders' => $eligibleProviders], 'admin');
    }

    /**
     * Nearest-first when both the job address and the provider's last GPS
     * ping are known; providers with no location yet sort to the end but
     * stay selectable (most won't have pinged until the mobile app does).
     */
    private function sortByDistance(array $providers, array $booking): array
    {
        if ($booking['address_lat'] === null || $booking['address_lng'] === null) {
            return $providers;
        }

        foreach ($providers as &$provider) {
            $provider['distance_km'] = ($provider['last_lat'] !== null && $provider['last_lng'] !== null)
                ? Geo::distanceKm((float) $booking['address_lat'], (float) $booking['address_lng'], (float) $provider['last_lat'], (float) $provider['last_lng'])
                : null;
        }
        unset($provider);

        usort($providers, function ($a, $b) {
            if ($a['distance_km'] === null) return $b['distance_km'] === null ? 0 : 1;
            if ($b['distance_km'] === null) return -1;
            return $a['distance_km'] <=> $b['distance_km'];
        });

        return $providers;
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

        $bookingModel->offerToProvider((int) $id, $providerId);
        Dispatcher::logOffer((int) $id, $providerId);
        Notifier::notify($providerId, 'New job offer', 'You have a new job to review.', (int) $id);
        Flash::success('Job offered to the provider — they still need to accept it.');
        redirect('/admin/bookings');
    }

    public function export(): void
    {
        $this->requireRole(Auth::ROLE_ADMIN);

        $bookings = (new Booking())->allDetailed(Request::query('status') ?: null);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bookings-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Service', 'Customer', 'Provider', 'Date', 'Slot', 'Status', 'Price', 'Discount', 'Payment Status', 'Created At'], ',', '"', '\\');
        foreach ($bookings as $b) {
            fputcsv($out, [
                $b['id'], $b['service_name'], $b['customer_name'], $b['provider_name'] ?? '-',
                $b['scheduled_date'], $b['scheduled_time_slot'], $b['status'], $b['price'],
                $b['discount_amount'], $b['payment_status'], $b['created_at'],
            ], ',', '"', '\\');
        }
        fclose($out);
        exit;
    }
}
