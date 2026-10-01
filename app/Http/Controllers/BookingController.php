<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Enums\EventStatus;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function create(Event $event): Response
    {
        abort_unless(
            $event->status === EventStatus::Published,
            404
        );

        $event->load([
            'ticketTypes:id,event_id,name,price,quantity,available_quantity',
        ]);

        return Inertia::render('Bookings/Create', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'venue' => $event->venue,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'ticket_types' => $event->ticketTypes->map(fn ($ticketType) => [
                    'id' => $ticketType->id,
                    'name' => $ticketType->name,
                    'price' => $ticketType->price,
                    'available_quantity' => $ticketType->available_quantity,
                ])->values(),
            ],
        ]);
    }
    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id' => [
                'required',
                'integer',
                'distinct',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $booking = $this->bookingService->create(
            $request->user(),
            $event,
            $validated['items'],
        );

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Your booking has been created.');
    }

    public function show(Request $request, Booking $booking): Response
    {
        abort_unless(
            $booking->user_id === $request->user()->id,
            403
        );

        $booking->load([
            'event',
            'items.ticketType',
        ]);

        return Inertia::render('Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'status' => $booking->status->value,
                'total_amount' => $booking->total_amount,
                'created_at' => $booking->created_at,
                'event' => [
                    'id' => $booking->event->id,
                    'title' => $booking->event->title,
                    'venue' => $booking->event->venue,
                    'starts_at' => $booking->event->starts_at,
                    'ends_at' => $booking->event->ends_at,
                ],
                'items' => $booking->items->map(fn ($item) => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                    'ticket_type' => [
                        'id' => $item->ticketType->id,
                        'name' => $item->ticketType->name,
                    ],
                ])->values(),
            ],
        ]);
    }
}
