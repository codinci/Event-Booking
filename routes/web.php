<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrganizerRequestController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\BookingCancellationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingPaymentController;
use App\Http\Controllers\Organizer\DashboardController as OrganizerDashboardController;
use App\Http\Controllers\Organizer\EventController;
use App\Http\Controllers\RefundCallbackController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')
    ->name('home');

Route::inertia('/test', 'Test')
    ->name('test');

/*
|--------------------------------------------------------------------------
| Public event routes
|--------------------------------------------------------------------------
*/

Route::get('/events', [EventController::class, 'index'])
    ->name('events.index');

Route::get('/events/{event}', [EventController::class, 'show'])
    ->name('events.show');

Route::get('/events/{event}/book', [BookingController::class, 'create'])
    ->name('bookings.create');

Route::post('/events/{event}/bookings', [BookingController::class, 'store'])
    ->name('bookings.store');

Route::post('/payments/daraja/callback/{reference}', [BookingPaymentController::class, 'callback'])
    ->whereUuid('reference')
    ->withoutMiddleware(PreventRequestForgery::class)
    ->name('payments.daraja.callback');

Route::post('/payments/daraja/b2c/{reference}/result', [RefundCallbackController::class, 'result'])
    ->whereUuid('reference')
    ->withoutMiddleware(PreventRequestForgery::class)
    ->name('payments.daraja.b2c.result');

Route::post('/payments/daraja/b2c/{reference}/timeout', [RefundCallbackController::class, 'timeout'])
    ->whereUuid('reference')
    ->withoutMiddleware(PreventRequestForgery::class)
    ->name('payments.daraja.b2c.timeout');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/bookings', [BookingController::class, 'index'])
        ->name('bookings.index');

    Route::get('/bookings/{booking}', [BookingController::class, 'show'])
        ->name('bookings.show');

    Route::post('/bookings/{booking}/payment', [BookingPaymentController::class, 'store'])
        ->name('bookings.pay');
    Route::get('/bookings/{booking}/receipt', [BookingPaymentController::class, 'receipt'])
        ->name('bookings.receipt');
    Route::post('/bookings/{booking}/cancel', [BookingCancellationController::class, 'store'])
        ->name('bookings.cancel');
});

/*
|--------------------------------------------------------------------------
| Organizer routes
|--------------------------------------------------------------------------
*/

// Organizer dashboard - uses plain `dashboard` route name the starter kit expects
Route::middleware(['auth', 'verified', 'organizer'])->group(function () {
    Route::get('dashboard', [OrganizerDashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'verified', 'organizer'])
    ->prefix('organizer')
    ->name('organizer.')
    ->group(function () {
        Route::get('events', [EventController::class, 'manage'])->name('events.index');

        Route::post('events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
        Route::post('events/{event}/cancel', [EventController::class, 'cancel'])->name('events.cancel');

        Route::resource('events', EventController::class)
            ->except(['index', 'show']);
    });

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('organizer-requests', [OrganizerRequestController::class, 'index'])->name('organizer-requests.index');
        Route::post('organizer-requests/{user}/approve', [OrganizerRequestController::class, 'approve'])->name('organizer-requests.approve');
        Route::post('organizer-requests/{user}/reject', [OrganizerRequestController::class, 'reject'])->name('organizer-requests.reject');

        Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
        Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
        Route::post('refunds/{refund}/resolve', [RefundController::class, 'resolve'])->name('refunds.resolve');
    });

require __DIR__.'/settings.php';
