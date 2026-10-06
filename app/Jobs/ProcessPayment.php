<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Events\PaymentSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPayment implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $bookingId,
    ) {}

    public function handle(): void
    {
        $booking = Booking::findOrFail($this->bookingId);

        if ($booking->payment_status === PaymentStatus::Paid) {
            return;
        }

        if ($booking->payment_status !== PaymentStatus::Processing) {
            return;
        }

        /*
         * Development-only payment simulation.
         *
         * Later this is where the actual payment
         * gateway interaction will happen.
         */

        $booking->update([
            'payment_status' => PaymentStatus::Paid,
        ]);

        PaymentSucceeded::dispatch($booking->id);
    }
}