<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Payments\BookingCancellations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->with([
                'ticketTypes:id,event_id,name,price,quantity,available_quantity',
            ])
            ->latest('starts_at')
            ->get();

        return Inertia::render('Events/Index', [
            'events' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'venue' => $event->venue,
                'image_url' => $event->image_url,
                'location' => $event->location,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'ticket_types' => $event->ticketTypes->map(fn ($ticketType) => [
                    'id' => $ticketType->id,
                    'name' => $ticketType->name,
                    'price' => $ticketType->price,
                    'available_quantity' => $ticketType->available_quantity,
                ])->values(),
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Event::class);

        return Inertia::render('Organizer/Events/Create');
    }

    public function manage(Request $request): Response
    {
        return Inertia::render('Organizer/Events/Index', [
            'events' => Event::query()
                ->whereBelongsTo($request->user(), 'organizer')
                ->select(['id', 'title', 'venue', 'status'])
                ->withCount(['bookings as paid_bookings_count' => fn ($query) => $query->where('payment_status', PaymentStatus::Paid)])
                ->latest('starts_at')
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function store(StoreEventRequest $request)
    {
        $this->authorize('create', Event::class);

        $event = Event::create([
            ...$request->validated(),
            'organizer_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('organizer.events.index')
            ->with('success', 'Event created successfully.');
    }

    public function show(Request $request, Event $event): Response
    {
        abort_unless(
            $event->status === EventStatus::Published,
            404
        );

        $event->load([
            'ticketTypes:id,event_id,name,price,quantity,available_quantity',
        ]);

        $existingBooking = $request->user()?->bookings()
            ->where('event_id', $event->id)
            ->active()
            ->latest('id')
            ->first();

        return Inertia::render('Events/Show', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'venue' => $event->venue,
                'image_url' => $event->image_url,
                'location' => $event->location,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'ticket_types' => $event->ticketTypes->map(fn ($ticketType) => [
                    'id' => $ticketType->id,
                    'name' => $ticketType->name,
                    'price' => $ticketType->price,
                    'available_quantity' => $ticketType->available_quantity,
                ])->values(),
            ],
            'existingBooking' => $existingBooking ? [
                'id' => $existingBooking->id,
                'reference' => $existingBooking->reference,
                'status' => $existingBooking->status->value,
            ] : null,
        ]);
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('Organizer/Events/Edit', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'venue' => $event->venue,
                'starts_at' => $event->starts_at->format('Y-m-d\TH:i:s'),
                'ends_at' => $event->ends_at->format('Y-m-d\TH:i:s'),
                'status' => $event->status->value,
                'paid_bookings_count' => $event->bookings()->where('payment_status', PaymentStatus::Paid)->count(),
            ],
        ]);
    }

    public function cancel(Event $event, BookingCancellations $cancellations): RedirectResponse
    {
        $this->authorize('update', $event);

        $refunds = $cancellations->cancelEvent($event);

        return redirect()
            ->route('organizer.events.index')
            ->with('success', $refunds
                ? "Event cancelled. {$refunds} paid ".str('booking')->plural($refunds).' will be refunded after admin approval.'
                : 'Event cancelled.');
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $this->authorize('update', $event);

        $event->update($request->validated());

        return redirect()
            ->route('organizer.events.index')
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()
            ->route('organizer.events.index')
            ->with('success', 'Event deleted successfully.');
    }
}
