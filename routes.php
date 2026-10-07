<?php

use App\Controllers\Admin\BookingController as AdminBookingController;
use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\CouponController as AdminCouponController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\ProviderController as AdminProviderController;
use App\Controllers\Admin\ServiceController as AdminServiceController;
use App\Controllers\Admin\SupportController as AdminSupportController;
use App\Controllers\Admin\ZoneController as AdminZoneController;
use App\Controllers\AddressController;
use App\Controllers\Api\AddressController as ApiAddressController;
use App\Controllers\Api\AuthController as ApiAuthController;
use App\Controllers\Api\BookingController as ApiBookingController;
use App\Controllers\Api\CatalogController as ApiCatalogController;
use App\Controllers\Api\NotificationController as ApiNotificationController;
use App\Controllers\Api\ProviderController as ApiProviderController;
use App\Controllers\Api\SupportController as ApiSupportController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\CategoryController;
use App\Controllers\HomeController;
use App\Controllers\NotificationController;
use App\Controllers\PageController;
use App\Controllers\Provider\DashboardController as ProviderDashboardController;
use App\Controllers\ServiceController;
use App\Controllers\SupportController;
use App\Controllers\WalletController;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/search', [HomeController::class, 'search']);

$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/category/{slug}', [CategoryController::class, 'show']);
$router->get('/service/{slug}', [ServiceController::class, 'show']);

$router->get('/book/{serviceSlug}', [BookingController::class, 'create']);
$router->post('/book/{serviceSlug}', [BookingController::class, 'store']);
$router->get('/bookings', [BookingController::class, 'index']);
$router->get('/bookings/{id}', [BookingController::class, 'show']);
$router->post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
$router->post('/bookings/{id}/reschedule', [BookingController::class, 'reschedule']);
$router->post('/bookings/{id}/rebook', [BookingController::class, 'rebook']);
$router->get('/bookings/{id}/invoice', [BookingController::class, 'invoice']);
$router->post('/bookings/{id}/messages', [BookingController::class, 'sendMessage']);
$router->post('/bookings/{id}/report-issue', [BookingController::class, 'reportIssue']);
$router->post('/bookings/{id}/review', [BookingController::class, 'review']);

$router->get('/account/addresses', [AddressController::class, 'index']);
$router->post('/account/addresses', [AddressController::class, 'store']);
$router->post('/account/addresses/{id}/delete', [AddressController::class, 'destroy']);

$router->get('/wallet', [WalletController::class, 'index']);
$router->get('/support', [SupportController::class, 'create']);
$router->post('/support', [SupportController::class, 'store']);

$router->get('/provider/dashboard', [ProviderDashboardController::class, 'index']);
$router->get('/provider/profile', [ProviderDashboardController::class, 'editProfile']);
$router->post('/provider/profile', [ProviderDashboardController::class, 'updateProfile']);
$router->post('/provider/bookings/{id}/accept', [ProviderDashboardController::class, 'accept']);
$router->post('/provider/bookings/{id}/reject', [ProviderDashboardController::class, 'reject']);
$router->post('/provider/bookings/{id}/start', [ProviderDashboardController::class, 'startJob']);
$router->post('/provider/bookings/{id}/complete', [ProviderDashboardController::class, 'completeJob']);
$router->post('/provider/bookings/{id}/notify-delay', [ProviderDashboardController::class, 'notifyDelay']);
$router->post('/provider/location-ping', [ProviderDashboardController::class, 'pingLocation']);

$router->get('/notifications', [NotificationController::class, 'index']);
$router->post('/notifications/mark-read', [NotificationController::class, 'markRead']);

$router->get('/privacy', [PageController::class, 'privacy']);
$router->get('/terms', [PageController::class, 'terms']);

$router->get('/admin', [AdminDashboardController::class, 'index']);

$router->get('/admin/categories', [AdminCategoryController::class, 'index']);
$router->post('/admin/categories', [AdminCategoryController::class, 'store']);
$router->post('/admin/categories/{id}/toggle', [AdminCategoryController::class, 'toggle']);
$router->post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'destroy']);

$router->get('/admin/services', [AdminServiceController::class, 'index']);
$router->post('/admin/services', [AdminServiceController::class, 'store']);
$router->post('/admin/services/{id}/toggle', [AdminServiceController::class, 'toggle']);
$router->post('/admin/services/{id}/delete', [AdminServiceController::class, 'destroy']);

$router->get('/admin/providers', [AdminProviderController::class, 'index']);
$router->post('/admin/providers/{id}/verify', [AdminProviderController::class, 'verify']);
$router->post('/admin/providers/{id}/suspend', [AdminProviderController::class, 'suspend']);

$router->get('/admin/bookings', [AdminBookingController::class, 'index']);
$router->post('/admin/bookings/{id}/assign', [AdminBookingController::class, 'assign']);
$router->get('/admin/bookings/export', [AdminBookingController::class, 'export']);

$router->get('/admin/coupons', [AdminCouponController::class, 'index']);
$router->post('/admin/coupons', [AdminCouponController::class, 'store']);
$router->post('/admin/coupons/{id}/toggle', [AdminCouponController::class, 'toggle']);

$router->get('/admin/zones', [AdminZoneController::class, 'index']);
$router->post('/admin/zones', [AdminZoneController::class, 'store']);
$router->post('/admin/zones/{id}/toggle', [AdminZoneController::class, 'toggle']);
$router->post('/admin/zones/{id}/delete', [AdminZoneController::class, 'destroy']);

$router->get('/admin/support', [AdminSupportController::class, 'index']);
$router->post('/admin/support/{id}/resolve', [AdminSupportController::class, 'resolve']);

// ---------------------------------------------------------------------
// JSON API for the Flutter app -- bearer-token auth, no CSRF, no
// sessions. Same backend, same database, just a different front door:
// see app/Controllers/Api/* and app/Core/ApiAuth.php.
// ---------------------------------------------------------------------
$router->post('/api/v1/register', [ApiAuthController::class, 'register']);
$router->post('/api/v1/login', [ApiAuthController::class, 'login']);
$router->post('/api/v1/logout', [ApiAuthController::class, 'logout']);
$router->get('/api/v1/me', [ApiAuthController::class, 'me']);

$router->get('/api/v1/categories', [ApiCatalogController::class, 'categories']);
$router->get('/api/v1/categories/{slug}/services', [ApiCatalogController::class, 'services']);
$router->get('/api/v1/services/{slug}', [ApiCatalogController::class, 'serviceDetail']);
$router->get('/api/v1/search', [ApiCatalogController::class, 'search']);

$router->get('/api/v1/addresses', [ApiAddressController::class, 'index']);
$router->post('/api/v1/addresses', [ApiAddressController::class, 'store']);
$router->post('/api/v1/addresses/{id}/delete', [ApiAddressController::class, 'destroy']);

$router->get('/api/v1/bookings', [ApiBookingController::class, 'index']);
$router->post('/api/v1/services/{serviceSlug}/book', [ApiBookingController::class, 'store']);
$router->get('/api/v1/bookings/{id}', [ApiBookingController::class, 'show']);
$router->post('/api/v1/bookings/{id}/cancel', [ApiBookingController::class, 'cancel']);
$router->post('/api/v1/bookings/{id}/reschedule', [ApiBookingController::class, 'reschedule']);
$router->get('/api/v1/bookings/{id}/messages', [ApiBookingController::class, 'messages']);
$router->post('/api/v1/bookings/{id}/messages', [ApiBookingController::class, 'sendMessage']);
$router->post('/api/v1/bookings/{id}/report-issue', [ApiBookingController::class, 'reportIssue']);
$router->post('/api/v1/bookings/{id}/review', [ApiBookingController::class, 'review']);

$router->get('/api/v1/provider/jobs', [ApiProviderController::class, 'jobs']);
$router->post('/api/v1/provider/jobs/{id}/accept', [ApiProviderController::class, 'accept']);
$router->post('/api/v1/provider/jobs/{id}/reject', [ApiProviderController::class, 'reject']);
$router->post('/api/v1/provider/jobs/{id}/start', [ApiProviderController::class, 'start']);
$router->post('/api/v1/provider/jobs/{id}/complete', [ApiProviderController::class, 'complete']);
$router->post('/api/v1/provider/location-ping', [ApiProviderController::class, 'pingLocation']);
$router->post('/api/v1/provider/availability', [ApiProviderController::class, 'toggleAvailability']);
$router->get('/api/v1/provider/profile', [ApiProviderController::class, 'profile']);
$router->post('/api/v1/provider/profile', [ApiProviderController::class, 'updateProfile']);
$router->post('/api/v1/provider/jobs/{id}/notify-delay', [ApiProviderController::class, 'notifyDelay']);

$router->get('/api/v1/notifications', [ApiNotificationController::class, 'index']);
$router->post('/api/v1/notifications/mark-read', [ApiNotificationController::class, 'markRead']);
$router->post('/api/v1/device-tokens', [ApiNotificationController::class, 'registerDeviceToken']);

$router->get('/api/v1/wallet', [ApiAuthController::class, 'wallet']);

$router->post('/api/v1/support', [ApiSupportController::class, 'store']);
