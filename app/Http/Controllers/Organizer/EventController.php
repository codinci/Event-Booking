<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\TicketType;
use App\Payments\BookingCancellations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'ticket_types' => $event->ticketTypes->map(fn (TicketType $ticketType) => [
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

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $validated = $request->validated();
        $ticketTypes = $validated['ticket_types'];
        unset($validated['ticket_types']);

        DB::transaction(function () use ($validated, $ticketTypes, $request) {
            $event = Event::create([
                ...$validated,
                'organizer_id' => $request->user()->id,
            ]);

            foreach ($ticketTypes as $type) {
                TicketType::create([
                    'event_id' => $event->id,
                    'name' => $type['name'],
                    'price' => $type['price'],
                    'quantity' => $type['quantity'],
                    'available_quantity' => $type['quantity'],
                ]);
            }
        });

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
                'ticket_types' => $event->ticketTypes->map(fn (TicketType $ticketType) => [
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

        $event->load('ticketTypes:id,event_id,name,price,quantity,available_quantity');

        return Inertia::render('Organizer/Events/Edit', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'venue' => $event->venue,
                'location' => $event->location,
                'image_url' => $event->image_url,
                'starts_at' => $event->starts_at->format('Y-m-d\TH:i:s'),
                'ends_at' => $event->ends_at->format('Y-m-d\TH:i:s'),
                'status' => $event->status->value,
                'paid_bookings_count' => $event->bookings()->where('payment_status', PaymentStatus::Paid)->count(),
                'ticket_types' => $event->ticketTypes->map(fn (TicketType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'price' => $type->price,
                    'quantity' => $type->quantity,
                    'available_quantity' => $type->available_quantity,
                ])->values(),
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

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validated();
        $ticketTypes = $validated['ticket_types'] ?? [];
        unset($validated['ticket_types']);

        DB::transaction(function () use ($validated, $ticketTypes, $event) {
            $event->update($validated);

            if (! empty($ticketTypes)) {
                $existingIds = [];
                foreach ($ticketTypes as $type) {
                    if (isset($type['id'])) {
                        $existingIds[] = $type['id'];
                    }
                }
                $toDelete = $event->ticketTypes()->whereNotIn('id', $existingIds)->get();

                foreach ($toDelete as $type) {
                    if ($type->bookingItems()->exists()) {
                        abort(422, "Ticket type \"{$type->name}\" has bookings and cannot be deleted.");
                    }
                    $type->delete();
                }

                foreach ($ticketTypes as $data) {
                    if (isset($data['id'])) {
                        /** @var TicketType $type */
                        $type = TicketType::findOrFail($data['id']);
                        $sold = $type->quantity - $type->available_quantity;
                        $quantity = (int) ($data['quantity'] ?? 0);
                        if ($quantity < $sold) {
                            abort(422, "Ticket type \"{$data['name']}\" quantity cannot be less than tickets sold ($sold).");
                        }
                        $type->update([
                            'name' => $data['name'],
                            'price' => $data['price'],
                            'quantity' => $quantity,
                            'available_quantity' => $data['available_quantity'] ?? $type->available_quantity,
                        ]);
                    } else {
                        TicketType::create([
                            'event_id' => $event->id,
                            'name' => $data['name'],
                            'price' => $data['price'],
                            'quantity' => $data['quantity'],
                            'available_quantity' => $data['quantity'],
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('organizer.events.index')
            ->with('success', 'Event updated successfully.');
    }

    public function publish(Event $event): RedirectResponse
    {
        $this->authorize('publish', $event);

        if ($event->ticketTypes()->doesntExist()) {
            abort(422, 'An event must have at least one ticket type to publish.');
        }

        if ($event->starts_at->isPast()) {
            abort(422, 'An event cannot be published if it has already started.');
        }

        $event->update(['status' => EventStatus::Published]);

        return redirect()
            ->route('organizer.events.index')
            ->with('success', 'Event published successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()
            ->route('organizer.events.index')
            ->with('success', 'Event deleted successfully.');
    }
}
