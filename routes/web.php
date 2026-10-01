<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Organizer\EventController;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/test', 'Test')->name('test');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'organizer'])
    ->prefix('organizer')
    ->name('organizer.')
    ->group(function () {
        Route::resource('events', EventController::class)
            ->except(['show']);
    });

require __DIR__.'/settings.php';


