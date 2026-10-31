<?php

use App\Controllers\PitchController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\BookingController as AdminBookingController;
use App\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Controllers\Admin\PitchController as AdminPitchController;
use App\Controllers\Admin\ServiceController as AdminServiceController;
use App\Controllers\Admin\UserController as AdminUserController;
use Bramus\Router\Router;

$router = new Router();

$router->get('/site-theme', static function () {
	header('Content-Type: text/css; charset=utf-8');
	readfile(__DIR__ . '/../assets/site-theme.css');
});

// ====================== FRONTEND ======================
$router->get('/',  PitchController::class . '@index');
$router->get('/schedule', PitchController::class . '@schedule');
$router->get('/my-bookings', PitchController::class . '@myBookings');
$router->post('/my-bookings/(\d+)/cancel-request', PitchController::class . '@requestCancellation');
$router->post('/my-bookings/(\d+)/rating', PitchController::class . '@rateBooking');
$router->get('/notifications', PitchController::class . '@notifications');
$router->post('/notifications/(\d+)/read', PitchController::class . '@markNotificationRead');
$router->post('/notifications/read-all', PitchController::class . '@markAllNotificationsRead');

$router->get('/pitches/(\d+)',  PitchController::class . '@show');

// Trang xem dịch vụ dành cho người dùng (đồng bộ với Quản lý dịch vụ bên admin)
$router->get('/services',  PitchController::class . '@services');

$router->post('/bookings',           PitchController::class . '@bookingStore');
$router->get('/bookings/details/(\w+)', PitchController::class . '@bookingDetails');
$router->post('/bookings/details/(\w+)', PitchController::class . '@bookingStore');
$router->get('/bookings/payment/(\w+)', PitchController::class . '@bookingPayment');
$router->post('/bookings/payment/(\w+)', PitchController::class . '@bookingPaymentContinue');
$router->get('/bookings/success/(\d+)', PitchController::class . '@bookingSuccess');
$router->get('/bookings/vnpay-return', PitchController::class . '@vnpayReturn');
$router->get('/bookings/vnpay-ipn', PitchController::class . '@vnpayIpn');

// ====================== ADMIN AUTH =====================
$router->get('/admin/login',           AuthController::class . '@loginForm');
$router->post('/admin/login',          AuthController::class . '@login');
$router->get('/admin/register',        AuthController::class . '@registerForm');
$router->post('/admin/register',       AuthController::class . '@register');
$router->get('/login',                 AuthController::class . '@loginForm');
$router->post('/login',                AuthController::class . '@login');
$router->get('/register',              AuthController::class . '@registerForm');
$router->post('/register',             AuthController::class . '@register');
$router->get('/admin/logout',          AuthController::class . '@logout');
$router->post('/logout',               AuthController::class . '@logout');

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

// Admin -> Quản lý dịch vụ (đồ uống, thiết bị, phụ trội)
$router->get('/admin/services',                        AdminServiceController::class . '@index');
$router->get('/admin/services/create',                 AdminServiceController::class . '@create');
$router->post('/admin/services',                       AdminServiceController::class . '@store');
$router->get('/admin/services/(\d+)',                 AdminServiceController::class . '@show');
$router->get('/admin/services/(\d+)/edit',             AdminServiceController::class . '@edit');
$router->post('/admin/services/(\d+)',                AdminServiceController::class . '@update');
$router->post('/admin/services/(\d+)/toggle-status',   AdminServiceController::class . '@toggleStatus');
$router->post('/admin/services/(\d+)/adjust-stock',    AdminServiceController::class . '@adjustStock');
$router->post('/admin/services/(\d+)/delete',          AdminServiceController::class . '@destroy');

// Admin -> Quản lý đặt sân (version mới - đầy đủ nghiệp vụ)
$router->get('/admin/bookings',                       AdminBookingController::class . '@index');
$router->get('/admin/bookings/calendar',              AdminBookingController::class . '@calendar');
$router->get('/admin/cancellation-requests',           AdminBookingController::class . '@cancellationRequests');
$router->post('/admin/cancellation-requests/(\d+)/approve', AdminBookingController::class . '@approveCancellationRequest');
$router->post('/admin/cancellation-requests/(\d+)/reject', AdminBookingController::class . '@rejectCancellationRequest');
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

// Admin -> Quản lý tài khoản và phân quyền
$router->get('/admin/users',                        AdminUserController::class . '@index');
$router->post('/admin/users/(\d+)/access',           AdminUserController::class . '@updateAccess');

// ------------------------

$router->run();
