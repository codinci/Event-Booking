<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBooking implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
    ) {}

    public function handle(): void
    {
        $this->booking->loadMissing([
            'user',
            'event',
            'items.ticketType',
        ]);

        // Post-booking processing will go here.
        //
        // Examples:
        // - send confirmation email
        // - generate ticket/QR code
        // - notify organizer
        // - publish booking event
    }
}
