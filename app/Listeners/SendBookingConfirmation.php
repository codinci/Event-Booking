<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use Illuminate\Support\Facades\Log;

class SendBookingConfirmation
{
    public function handle(PaymentSucceeded $event): void
    {
        Log::info('Booking confirmation email simulated.', [
            'booking_id' => $event->bookingId,
        ]);
    }
}