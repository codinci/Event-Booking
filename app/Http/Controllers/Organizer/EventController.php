<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::query()
            ->where('organizer_id', Auth::id())
            ->latest()
            ->paginate(10);

        return Inertia::render('Organizer/Events/Index', [
            'events' => $events,
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