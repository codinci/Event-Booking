<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use App\Enums\EventStatus;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;

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

    public function show(Event $event): Response
    {
        abort_unless(
            $event->status === EventStatus::Published,
            404
        );

        $event->load([
            'ticketTypes:id,event_id,name,price,quantity,available_quantity',
        ]);

        return Inertia::render('Events/Show', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
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

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('Organizer/Events/Edit', [
            'event' => $event,
        ]);
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