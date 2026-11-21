<?php

namespace App\Payments;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Models\Booking;
use App\Models\Event;
use App\Models\PaymentAttempt;
use App\services\ReleaseBookingTicketsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingCancellations
{
    public function __construct(
        private readonly ReleaseBookingTicketsService $release,
        private readonly RefundRequests $refunds,
    ) {}

    /** Whether the customer may still cancel this booking themselves. */
    public function customerCanCancel(Booking $booking): bool
    {
        return $booking->status === BookingStatus::Confirmed
            && $booking->payment_status === PaymentStatus::Paid
            && $booking->event->starts_at->isAfter(now()->addHours(config('payments.cancellation_cutoff_hours')))
            && $this->successfulPayment($booking) !== null;
    }

    public function cancelByCustomer(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (! $this->customerCanCancel($booking)) {
                $hours = config('payments.cancellation_cutoff_hours');

                throw ValidationException::withMessages([
                    'booking' => "Only paid bookings can be cancelled, up to {$hours} hours before the event starts.",
                ]);
            }

            $this->cancelAndRefund($booking, RefundReason::CustomerCancelled);
        }, 3);
    }

    /**
     * Cancel the event: paid bookings are cancelled with a refund, unpaid
     * reservations are released. A reservation whose payment still succeeds
     * later becomes a late payment and is refunded in full.
     *
     * @return int The number of refunds opened.
     */
    public function cancelEvent(Event $event): int
    {
        return DB::transaction(function () use ($event) {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);

            if ($event->status === EventStatus::Cancelled) {
                return 0;
            }

            $event->update(['status' => EventStatus::Cancelled]);
            $refunds = 0;

            $event->bookings()->where('status', '!=', BookingStatus::Cancelled)->eachById(function (Booking $booking) use (&$refunds) {
                if ($booking->status === BookingStatus::Pending) {
                    $this->release->failAndRelease($booking);

                    return;
                }

                if ($booking->payment_status === PaymentStatus::Paid && $this->cancelAndRefund($booking, RefundReason::EventCancelled)) {
                    $refunds++;
                }
            });

            return $refunds;
        }, 3);
    }

    private function cancelAndRefund(Booking $booking, RefundReason $reason): bool
    {
        $attempt = $this->successfulPayment($booking);

        if (! $this->release->cancelPaidAndRelease($booking)) {
            return false;
        }

        // Bookings marked paid without a recorded payment (e.g. seeded data) have nothing to refund.
        if ($attempt === null) {
            $booking->newQuery()->whereKey($booking->id)->update(['payment_status' => PaymentStatus::Paid]);

            return false;
        }

        $this->refunds->request($attempt, $reason);

        return true;
    }

    private function successfulPayment(Booking $booking): ?PaymentAttempt
    {
        return PaymentAttempt::where('booking_id', $booking->id)->where('status', 'succeeded')->latest('id')->first();
    }
}
