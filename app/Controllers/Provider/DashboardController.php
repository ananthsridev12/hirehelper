<?php

namespace App\Controllers\Provider;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Models\Booking;
use App\Models\ProviderLocation;
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

    private function ownedOfferedBooking(string $id): ?array
    {
        $booking = (new Booking())->find((int) $id);
        if (!$booking || (int) $booking['provider_id'] !== Auth::id() || $booking['status'] !== Booking::STATUS_OFFERED) {
            return null;
        }
        return $booking;
    }

    public function accept(string $id): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);
        $this->verifyCsrf();

        $booking = $this->ownedOfferedBooking($id);
        if (!$booking) {
            Response::notFound();
            return;
        }

        $otp = (new Booking())->acceptOffer((int) $id);
        Notifier::notify(
            (int) $booking['customer_id'],
            'Professional assigned',
            "Share this code with your professional when they arrive: {$otp}",
            (int) $id
        );
        Flash::success('Job accepted. The start code has been sent to the customer.');
        redirect('/bookings/' . $id);
    }

    public function reject(string $id): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);
        $this->verifyCsrf();

        $booking = $this->ownedOfferedBooking($id);
        if (!$booking) {
            Response::notFound();
            return;
        }

        (new Booking())->rejectOffer((int) $id);
        Flash::success('Job declined.');
        redirect('/provider/dashboard');
    }

    public function startJob(string $id): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);
        $this->verifyCsrf();

        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['provider_id'] !== Auth::id() || $booking['status'] !== Booking::STATUS_ASSIGNED) {
            Response::notFound();
            return;
        }

        $otp = (string) Request::input('start_otp', '');
        if (!$bookingModel->startWithOtp((int) $id, $otp, (string) $booking['start_otp'])) {
            Flash::error('That code doesn\'t match. Ask the customer for the code again.');
            redirect('/bookings/' . $id);
            return;
        }

        Notifier::notify((int) $booking['customer_id'], 'Job started', 'Your professional has started the job.', (int) $id);
        Flash::success('Job started.');
        redirect('/bookings/' . $id);
    }

    public function completeJob(string $id): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);
        $this->verifyCsrf();

        $bookingModel = new Booking();
        $booking = $bookingModel->find((int) $id);
        if (!$booking || (int) $booking['provider_id'] !== Auth::id() || $booking['status'] !== Booking::STATUS_IN_PROGRESS) {
            Response::notFound();
            return;
        }

        $bookingModel->updateStatus((int) $id, Booking::STATUS_COMPLETED);
        Notifier::notify((int) $booking['customer_id'], 'Job completed', 'Your service is marked complete. Please rate your experience.', (int) $id);
        Flash::success('Job marked complete.');
        redirect('/bookings/' . $id);
    }

    /**
     * Periodic GPS ping from the provider's browser (and, once built, the
     * Flutter app) while a job is in progress -- lets the customer's
     * booking page show a live "professional is X km away."
     */
    public function pingLocation(): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);
        $this->verifyCsrf();

        $lat = (float) Request::input('lat', 0);
        $lng = (float) Request::input('lng', 0);
        if ($lat === 0.0 && $lng === 0.0) {
            Response::json(['ok' => false], 422);
            return;
        }

        (new ProviderLocation())->ping(Auth::id(), $lat, $lng);
        Response::json(['ok' => true]);
    }
}
