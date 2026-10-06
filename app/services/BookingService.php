<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Jobs\ProcessBooking;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * @param array<int, array{ticket_type_id:int, quantity:int}> $items
     */
    public function create(
        User $user,
        Event $event,
        array $items,
    ): Booking {
        $ticketTypeIds = collect($items)
            ->pluck('ticket_type_id')
            ->map(fn ($id) => (int) $id);

        if ($ticketTypeIds->count() !== $ticketTypeIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'A ticket type can only be selected once.',
            ]);
        }

        $booking = DB::transaction(function () use (
            $user,
            $event,
            $items,
            $ticketTypeIds
        ) {
            $ticketTypes = $event->ticketTypes()
                ->whereIn('id', $ticketTypeIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($ticketTypes->count() !== $ticketTypeIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected ticket types are invalid for this event.',
                ]);
            }

            $bookingItems = [];
            $totalAmountCents = 0;

            foreach ($items as $item) {
                $ticketType = $ticketTypes->get(
                    (int) $item['ticket_type_id']
                );

                $quantity = (int) $item['quantity'];

                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        'items' => 'Ticket quantity must be at least 1.',
                    ]);
                }

                if ($quantity > $ticketType->available_quantity) {
                    throw ValidationException::withMessages([
                        'items' => sprintf(
                            'Only %d %s ticket(s) are available.',
                            $ticketType->available_quantity,
                            $ticketType->name,
                        ),
                    ]);
                }

                $unitPriceCents = $this->toCents(
                    $ticketType->price
                );

                $lineTotalCents = $quantity * $unitPriceCents;

                $bookingItems[] = [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => $quantity,
                    'unit_price' => $this->fromCents($unitPriceCents),
                    'total' => $this->fromCents($lineTotalCents),
                ];

                $totalAmountCents += $lineTotalCents;

                $ticketType->decrement(
                    'available_quantity',
                    $quantity
                );
            }

            $booking = $user->bookings()->create([
                'event_id' => $event->id,
                'reference' => $this->generateReference(),
                'status' => BookingStatus::Pending,
                'total_amount' => $this->fromCents($totalAmountCents),
            ]);

            $booking->items()->createMany($bookingItems);

            return $booking->load([
                'event',
                'items.ticketType',
            ]);
        });

        ProcessBooking::dispatch($booking->id)->afterCommit();

        return $booking;
    }

    private function toCents(string $amount): int
    {
        [$whole, $decimal] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        $decimal = str_pad(
            substr($decimal, 0, 2),
            2,
            '0'
        );

        return ((int) $whole * 100) + (int) $decimal;
    }
    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100)
            . '.'
            . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function generateReference(): string
    {
        do {
            $reference = 'BK-' . strtoupper(
                fake()->bothify('####??####')
            );
        } while (Booking::where('reference', $reference)->exists());

        return $reference;
    }
}