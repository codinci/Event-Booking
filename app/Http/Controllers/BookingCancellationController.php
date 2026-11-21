<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Payments\BookingCancellations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingCancellationController extends Controller
{
    public function store(Request $request, Booking $booking, BookingCancellations $cancellations): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $cancellations->cancelByCustomer($booking);

        return to_route('bookings.show', $booking)
            ->with('success', 'Your booking was cancelled. Your refund is waiting for approval.');
    }
}
