<?php

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Events\PaymentSucceeded;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ConfirmBooking implements ShouldQueue
{
    use Queueable;

    public function handle(PaymentSucceeded $event): void
    {
        $booking = Booking::findOrFail($event->bookingId);

        if ($booking->status !== BookingStatus::Pending) {
            return;
        }

        $booking->update([
            'status' => BookingStatus::Confirmed,
        ]);
    }
}