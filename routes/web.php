<?php

use App\Controllers\PitchController;
use App\Controllers\Admin\BookingController as AdminBookingController;
use App\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Controllers\Admin\PitchController as AdminPitchController;
use Bramus\Router\Router;

$router = new Router();

// ====================== FRONTEND ======================
$router->get('/',  PitchController::class . '@index');

$router->get('/pitches/(\d+)',  PitchController::class . '@show');

$router->post('/bookings',           PitchController::class . '@bookingStore');
$router->get('/bookings/success/(\d+)', PitchController::class . '@bookingSuccess');

// ====================== ADMIN =========================
$router->get('/admin',                       AdminBookingController::class . '@index');
$router->get('/admin/dashboard',             AdminBookingController::class . '@dashboard');

// Admin -> Quản lý sân
$router->get('/admin/pitches',                    AdminPitchController::class . '@index');
$router->get('/admin/pitches/create',             AdminPitchController::class . '@create');
$router->post('/admin/pitches',                   AdminPitchController::class . '@store');
$router->get('/admin/pitches/(\d+)',             AdminPitchController::class . '@show');
$router->get('/admin/pitches/(\d+)/edit',         AdminPitchController::class . '@edit');
$router->post('/admin/pitches/(\d+)',            AdminPitchController::class . '@update');
$router->post('/admin/pitches/(\d+)/toggle-status', AdminPitchController::class . '@toggleStatus');
$router->post('/admin/pitches/(\d+)/delete',      AdminPitchController::class . '@destroy');

// Admin -> Quản lý đặt sân
$router->get('/admin/bookings',                    AdminBookingController::class . '@index');
$router->get('/admin/bookings/(\d+)/edit',         AdminBookingController::class . '@edit');
$router->post('/admin/bookings/(\d+)',             AdminBookingController::class . '@update');
$router->post('/admin/bookings/(\d+)/delete',      AdminBookingController::class . '@destroy');

// Admin -> Quản lý khách hàng (read-only từ lịch sử đặt sân)
$router->get('/admin/customers',                    AdminCustomerController::class . '@index');
$router->get('/admin/customers/detail',             AdminCustomerController::class . '@show');

// ------------------------

$router->run();
