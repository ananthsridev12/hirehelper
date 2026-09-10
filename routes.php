<?php

use App\Controllers\Admin\BookingController as AdminBookingController;
use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\ProviderController as AdminProviderController;
use App\Controllers\Admin\ServiceController as AdminServiceController;
use App\Controllers\AddressController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\CategoryController;
use App\Controllers\HomeController;
use App\Controllers\Provider\DashboardController as ProviderDashboardController;
use App\Controllers\ServiceController;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);

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
$router->post('/bookings/{id}/review', [BookingController::class, 'review']);

$router->get('/account/addresses', [AddressController::class, 'index']);
$router->post('/account/addresses', [AddressController::class, 'store']);
$router->post('/account/addresses/{id}/delete', [AddressController::class, 'destroy']);

$router->get('/provider/dashboard', [ProviderDashboardController::class, 'index']);
$router->post('/provider/bookings/{id}/status', [ProviderDashboardController::class, 'updateStatus']);

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
