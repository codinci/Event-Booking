<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Organizer\EventController;
use App\Http\Controllers\BookingController;


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

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')
        ->name('dashboard');

    Route::get('/events/{event}/book', [BookingController::class, 'create'])
        ->name('bookings.create');

    Route::post('/events/{event}/bookings', [BookingController::class, 'store'])
        ->name('bookings.store');

    Route::get('/bookings/{booking}', [BookingController::class, 'show'])
        ->name('bookings.show');
});

/*
|--------------------------------------------------------------------------
| Organizer routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'organizer'])
    ->prefix('organizer')
    ->name('organizer.')
    ->group(function () {
        Route::resource('events', EventController::class)
            ->except(['show']);
    });

require __DIR__.'/settings.php';