<?php

use App\Controllers\PitchController;
use App\Controllers\Admin\AuthController;
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

// ====================== ADMIN AUTH =====================
$router->get('/admin/login',           AuthController::class . '@loginForm');
$router->post('/admin/login',          AuthController::class . '@login');
$router->get('/admin/logout',          AuthController::class . '@logout');

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

// Admin -> Quản lý đặt sân (version mới - đầy đủ nghiệp vụ)
$router->get('/admin/bookings',                       AdminBookingController::class . '@index');
$router->get('/admin/bookings/calendar',              AdminBookingController::class . '@calendar');
$router->get('/admin/bookings/create',                AdminBookingController::class . '@create');
$router->post('/admin/bookings',                      AdminBookingController::class . '@store');
$router->get('/admin/bookings/check-overlap',         AdminBookingController::class . '@checkOverlap');
$router->get('/admin/bookings/(\d+)',                 AdminBookingController::class . '@show');
$router->get('/admin/bookings/(\d+)/edit',            AdminBookingController::class . '@edit');
$router->post('/admin/bookings/(\d+)',                AdminBookingController::class . '@update');
$router->get('/admin/bookings/(\d+)/cancel',          AdminBookingController::class . '@cancel');
$router->post('/admin/bookings/(\d+)/cancel',         AdminBookingController::class . '@cancel');
$router->get('/admin/bookings/(\d+)/delete',          AdminBookingController::class . '@destroy');
$router->post('/admin/bookings/(\d+)/delete',         AdminBookingController::class . '@destroy');
$router->post('/admin/bookings/(\d+)/confirm',        AdminBookingController::class . '@confirm');
$router->post('/admin/bookings/(\d+)/start',          AdminBookingController::class . '@start');
$router->post('/admin/bookings/(\d+)/complete',       AdminBookingController::class . '@complete');
$router->post('/admin/bookings/(\d+)/payment',        AdminBookingController::class . '@addPayment');

// Khóa / Mở khóa khung giờ
$router->get('/admin/pitch-lock/create',               AdminBookingController::class . '@showLockForm');
$router->post('/admin/pitch-lock',                     AdminBookingController::class . '@storeLock');
$router->get('/admin/pitch-lock/(\d+)/unlock',         AdminBookingController::class . '@unlockSlot');
$router->post('/admin/pitch-lock/(\d+)/unlock',        AdminBookingController::class . '@unlockSlot');

// (Compat) Route cũ để không bị 404
$router->post('/admin/bookings/(\d+)/delete-old',     AdminBookingController::class . '@destroy');

// Admin -> Quản lý khách hàng (từ bảng customers)
$router->get('/admin/customers',                    AdminCustomerController::class . '@index');
$router->get('/admin/customers/detail',             AdminCustomerController::class . '@show');

// ------------------------

$router->run();
