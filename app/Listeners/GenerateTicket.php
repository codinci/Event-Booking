<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use Illuminate\Support\Facades\Log;

class GenerateTicket
{
    public function handle(PaymentSucceeded $event): void
    {
        Log::info('Ticket generation simulated.', [
            'booking_id' => $event->bookingId,
        ]);
    }
}