<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\PitchController as AdminPitchController;
use App\Http\Controllers\PitchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PitchController::class, 'index']);
Route::get('/pitches/{id}', [PitchController::class, 'show'])->whereNumber('id');
Route::post('/bookings', [PitchController::class, 'bookingStore']);
Route::get('/bookings/success/{id}', [PitchController::class, 'bookingSuccess'])->whereNumber('id');

Route::get('/admin/login', [AuthController::class, 'loginForm']);
Route::post('/admin/login', [AuthController::class, 'login']);
Route::get('/admin/logout', [AuthController::class, 'logout']);

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', fn () => redirect('/admin/bookings'));
    Route::get('/dashboard', [AdminBookingController::class, 'dashboard']);

    Route::get('/pitches', [AdminPitchController::class, 'index']);
    Route::get('/pitches/create', [AdminPitchController::class, 'create']);
    Route::post('/pitches', [AdminPitchController::class, 'store']);
    Route::get('/pitches/{id}', [AdminPitchController::class, 'show'])->whereNumber('id');
    Route::get('/pitches/{id}/edit', [AdminPitchController::class, 'edit'])->whereNumber('id');
    Route::post('/pitches/{id}', [AdminPitchController::class, 'update'])->whereNumber('id');
    Route::post('/pitches/{id}/toggle-status', [AdminPitchController::class, 'toggleStatus'])->whereNumber('id');
    Route::post('/pitches/{id}/delete', [AdminPitchController::class, 'destroy'])->whereNumber('id');

    Route::get('/bookings', [AdminBookingController::class, 'index']);
    Route::get('/bookings/{id}/edit', [AdminBookingController::class, 'edit'])->whereNumber('id');
    Route::post('/bookings/{id}', [AdminBookingController::class, 'update'])->whereNumber('id');
    Route::post('/bookings/{id}/delete', [AdminBookingController::class, 'destroy'])->whereNumber('id');
});
