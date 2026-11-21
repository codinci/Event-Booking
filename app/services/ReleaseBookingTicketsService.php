<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReleaseBookingTicketsService
{
    /**
     * Release tickets for a booking whose payment definitively failed.
     *
     * Returns true when tickets were released.
     * Returns false when the booking is not eligible or was
     * already cancelled.
     */
    public function failAndRelease(Booking|int $booking): bool
    {
        $bookingId = $booking instanceof Booking
            ? $booking->getKey()
            : $booking;

        return DB::transaction(function () use ($bookingId): bool {
            $booking = Booking::query()
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->firstOrFail();

            // Never release tickets for a successfully paid booking.
            if ($booking->payment_status === PaymentStatus::Paid) {
                return false;
            }

            // Idempotency: tickets have already been released.
            if ($booking->tickets_released_at !== null) {
                return false;
            }

            // Only pending bookings can be cancelled by this flow.
            if ($booking->status !== BookingStatus::Pending) {
                return false;
            }

            $this->returnTickets($booking);

            $booking->payment_status = PaymentStatus::Failed;
            $booking->status = BookingStatus::Cancelled;
            $booking->tickets_released_at = now();
            $booking->save();

            return true;
        });
    }

    /**
     * Cancel a confirmed, paid booking whose money will be refunded,
     * returning its tickets to sale immediately.
     *
     * Returns false when the booking is not a confirmed paid booking
     * or its tickets were already released.
     */
    public function cancelPaidAndRelease(Booking|int $booking): bool
    {
        $bookingId = $booking instanceof Booking
            ? $booking->getKey()
            : $booking;

        return DB::transaction(function () use ($bookingId): bool {
            $booking = Booking::query()
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== BookingStatus::Confirmed
                || $booking->payment_status !== PaymentStatus::Paid
                || $booking->tickets_released_at !== null) {
                return false;
            }

            $this->returnTickets($booking);

            $booking->payment_status = PaymentStatus::RefundPending;
            $booking->status = BookingStatus::Cancelled;
            $booking->tickets_released_at = now();
            $booking->save();

            return true;
        });
    }

    /**
     * Add the booking's ticket quantities back to their ticket types.
     * Must run inside a transaction holding the booking's row lock.
     */
    private function returnTickets(Booking $booking): void
    {
        $items = $booking->items()
            ->get(['ticket_type_id', 'quantity']);

        if ($items->isEmpty()) {
            throw new RuntimeException(
                "Booking {$booking->id} has no items to release."
            );
        }

        $quantities = $items
            ->groupBy('ticket_type_id')
            ->map(fn ($group) => (int) $group->sum('quantity'))
            ->sortKeys();

        foreach ($quantities as $ticketTypeId => $quantity) {
            if ($quantity < 1) {
                throw new RuntimeException(
                    "Invalid ticket quantity for booking {$booking->id}."
                );
            }

            $ticketType = TicketType::query()
                ->whereKey($ticketTypeId)
                ->lockForUpdate()
                ->firstOrFail();

            $ticketType->increment(
                'available_quantity',
                $quantity,
            );
        }
    }
}
