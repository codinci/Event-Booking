<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\RefundReason;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\PaymentAttempt;
use App\Payments\BookingCancellations;
use App\Payments\BookingPayments;
use App\Payments\RefundRequests;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function create(Request $request, Event $event): Response|RedirectResponse
    {
        abort_unless(
            $event->status === EventStatus::Published,
            404
        );

        $existing = $request->user()?->bookings()
            ->where('event_id', $event->id)
            ->active()
            ->first();

        if ($existing) {
            return redirect()
                ->route('bookings.show', $existing)
                ->with('success', 'You already have a booking for this event.');
        }

        $pendingBooking = $request->session()->get('pending_booking');

        $pendingItems = (
            ($pendingBooking['event_id'] ?? null) == $event->id
        )
            ? ($pendingBooking['items'] ?? [])
            : [];

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
                'ticket_types' => $event->ticketTypes->map(
                    fn ($ticketType) => [
                        'id' => $ticketType->id,
                        'name' => $ticketType->name,
                        'price' => $ticketType->price,
                        'available_quantity' => $ticketType->available_quantity,
                    ]
                )->values(),
            ],
            'pendingItems' => $pendingItems,
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

        if (! $request->user()) {
            $request->session()->put('pending_booking', [
                'event_id' => $event->id,
                'items' => $validated['items'],
            ]);

            $request->session()->put(
                'url.intended',
                route('bookings.create', $event)
            );

            return redirect()
                ->route('login')
                ->with('success', 'Sign in to continue your booking.');
        }

        $booking = $this->bookingService->create(
            $request->user(),
            $event,
            $validated['items'],
        );

        $request->session()->forget('pending_booking');

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Your booking has been created.');
    }

    public function index(Request $request): Response
    {
        // Ordered by event date in SQL so the most recent event comes first.
        $bookings = $request->user()->bookings()
            ->with([
                'event:id,title,venue,starts_at,ends_at',
                'items.ticketType:id,name',
            ])
            ->join('events', 'events.id', '=', 'bookings.event_id')
            ->orderByDesc('events.starts_at')
            ->select('bookings.*')
            ->get();

        return Inertia::render('Bookings/Index', [
            'bookings' => $bookings->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'status' => $booking->status->value,
                'total_amount' => $booking->total_amount,
                'created_at' => $booking->created_at,
                'is_past' => $booking->event->ends_at->isPast(),
                'event' => [
                    'id' => $booking->event->id,
                    'title' => $booking->event->title,
                    'venue' => $booking->event->venue,
                    'starts_at' => $booking->event->starts_at,
                    'ends_at' => $booking->event->ends_at,
                ],
                'items' => $booking->items->map(fn (BookingItem $item) => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'ticket_type' => [
                        'name' => $item->ticketType->name,
                    ],
                ])->values(),
            ])->values(),
        ]);
    }

    public function show(
        Request $request,
        Booking $booking,
        BookingPayments $payments,
        BookingCancellations $cancellations,
        RefundRequests $refunds,
    ): Response {
        abort_unless(
            $booking->user_id === $request->user()->id,
            403
        );

        $booking->load([
            'event',
            'items.ticketType',
        ]);

        $refund = $booking->refunds()->latest('id')->first();
        $canCancel = $cancellations->customerCanCancel($booking);
        $paidAttempt = $canCancel
            ? PaymentAttempt::where('booking_id', $booking->id)->where('status', 'succeeded')->latest('id')->first()
            : null;

        return Inertia::render('Bookings/Show', [
            'paymentGateways' => (object) $payments->gateways(),
            'paymentUrl' => route('bookings.pay', $booking),
            'receiptUrl' => route('bookings.receipt', $booking),
            'paymentAttempt' => PaymentAttempt::query()->where('booking_id', $booking->id)->latest('id')
                ->first(['status', 'refund_status', 'next_check_at', 'receipt_number']),
            'refund' => $refund ? [
                'status' => $refund->status->value,
                'reason' => $refund->reason->value,
                'amount' => $refund->amount / 100,
                'rejection_reason' => $refund->rejection_reason,
            ] : null,
            'cancellation' => $canCancel ? [
                'url' => route('bookings.cancel', $booking),
                'refund_amount' => $refunds->amount($paidAttempt, RefundReason::CustomerCancelled) / 100,
                'cutoff_hours' => config('payments.cancellation_cutoff_hours'),
            ] : null,
            'booking' => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'status' => $booking->status->value,
                'payment_hold_expires_at' => $booking->payment_hold_expires_at,
                'total_amount' => $booking->total_amount,
                'created_at' => $booking->created_at,
                'event' => [
                    'id' => $booking->event->id,
                    'title' => $booking->event->title,
                    'venue' => $booking->event->venue,
                    'starts_at' => $booking->event->starts_at,
                    'ends_at' => $booking->event->ends_at,
                ],
                'items' => $booking->items->map(fn (BookingItem $item) => [
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
