<?php

namespace App\Controllers\Provider;

use App\Controllers\BaseController;
use App\Core\Auth;
use App\Core\Dispatcher;
use App\Core\Flash;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Core\Upload;
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

    public function editProfile(): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);
        $profile = (new ProviderProfile())->findByUserId(Auth::id());
        view('provider/profile', ['profile' => $profile, 'errors' => []], 'main');
    }

    public function updateProfile(): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);
        $this->verifyCsrf();

        $bio = (string) Request::input('bio', '');
        $experience = Request::input('experience_years', '');

        try {
            $photoPath = Upload::image(Request::file('photo'), 'providers');
        } catch (\RuntimeException $e) {
            $profile = (new ProviderProfile())->findByUserId(Auth::id());
            view('provider/profile', ['profile' => $profile, 'errors' => [$e->getMessage()]], 'main');
            return;
        }

        $data = [
            'bio' => $bio,
            'experience_years' => $experience !== '' ? (int) $experience : null,
        ];
        if ($photoPath) {
            $data['photo_path'] = $photoPath;
        }

        (new ProviderProfile())->updateForUser(Auth::id(), $data);
        Flash::success('Profile updated.');
        redirect('/provider/profile');
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

        Dispatcher::markResponded((int) $id, Auth::id(), 'accepted');
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

        Dispatcher::markResponded((int) $id, Auth::id(), 'rejected');
        (new Booking())->rejectOffer((int) $id);

        $reassigned = Dispatcher::autoAssign((int) $id);
        Flash::success($reassigned ? 'Job declined. It has been offered to another professional.' : 'Job declined.');
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

        try {
            $beforePhoto = Upload::image(Request::file('before_photo'), 'jobs');
            if ($beforePhoto) {
                $bookingModel->setBeforePhoto((int) $id, $beforePhoto);
            }
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
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

        try {
            $afterPhoto = Upload::image(Request::file('after_photo'), 'jobs');
            if ($afterPhoto) {
                $bookingModel->setAfterPhoto((int) $id, $afterPhoto);
            }
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }

        $bookingModel->updateStatus((int) $id, Booking::STATUS_COMPLETED);
        Notifier::notify((int) $booking['customer_id'], 'Job completed', 'Your service is marked complete. Please rate your experience.', (int) $id);
        Flash::success('Job marked complete.');
        redirect('/bookings/' . $id);
    }

    public function notifyDelay(string $id): void
    {
        $this->requireRole(Auth::ROLE_PROVIDER);
        $this->verifyCsrf();

        $booking = (new Booking())->find((int) $id);
        if (!$booking || (int) $booking['provider_id'] !== Auth::id()) {
            Response::notFound();
            return;
        }

        Notifier::notify((int) $booking['customer_id'], 'Running a little late', 'Your professional is running a bit late and is still on the way.', (int) $id);
        Flash::success('Customer notified.');
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
