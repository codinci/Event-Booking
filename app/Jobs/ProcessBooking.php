<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBooking implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $bookingId,
    ) {}

    public function handle(): void
    {
        $booking = Booking::findOrFail($this->bookingId);

        if ($booking->status !== BookingStatus::Pending) {
            return;
        }

        if ($booking->payment_status !== PaymentStatus::Pending) {
            return;
        }

        $booking->update([
            'payment_status' => PaymentStatus::Processing,
        ]);

        ProcessPayment::dispatch($booking->id);
    }
}
