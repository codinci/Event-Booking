<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\services\ReleaseBookingTicketsService;

class CancelBookingAfterPaymentFailure
{
    public function handle(
        PaymentFailed $event,
        ReleaseBookingTicketsService $releaseBookingTickets,
    ): void {
        $releaseBookingTickets->failAndRelease($event->bookingId);
    }
}
