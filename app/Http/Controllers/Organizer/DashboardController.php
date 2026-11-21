<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\PaymentAttempt;
use App\Models\Refund;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $upcomingEvents = Event::query()
            ->where('organizer_id', $user->id)
            ->where('status', EventStatus::Published)
            ->where('starts_at', '>', now())
            ->with('ticketTypes:id,event_id,name,price,quantity,available_quantity')
            ->withCount(['bookings as paid_bookings_count' => fn ($q) => $q->where('payment_status', PaymentStatus::Paid)])
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $totalRevenue = PaymentAttempt::query()
            ->whereHas('booking', fn ($q) => $q->whereHas('event', fn ($e) => $e->where('organizer_id', $user->id)))
            ->where('status', 'completed')
            ->sum('organizer_share');

        $pendingRefunds = Refund::query()
            ->whereHas('paymentAttempt', fn ($q) => $q->whereHas('booking', fn ($bq) => $bq->whereHas('event', fn ($e) => $e->where('organizer_id', $user->id))))
            ->whereIn('status', ['awaiting_approval', 'review'])
            ->count();

        $recentBookings = Event::query()
            ->where('organizer_id', $user->id)
            ->with(['bookings' => fn ($q) => $q->latest()->limit(5)])
            ->get()
            ->flatMap(fn (Event $e) => $e->bookings)
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        $events = [];
        foreach ($upcomingEvents as $event) {
            $events[] = [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at,
                'paid_bookings_count' => $event->getAttribute('paid_bookings_count'),
                'ticket_types' => $event->ticketTypes->map(function ($tt) {
                    return [
                        'name' => $tt->name,
                        'sold' => $tt->quantity - $tt->available_quantity,
                        'available' => $tt->available_quantity,
                    ];
                })->values()->toArray(),
            ];
        }

        $bookings = [];
        foreach ($recentBookings as $booking) {
            $bookings[] = [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'user_name' => $booking->user->name,
                'event_title' => $booking->event->title,
                'status' => $booking->status->value,
                'created_at' => $booking->created_at,
            ];
        }

        return Inertia::render('Organizer/Dashboard', [
            'upcomingEvents' => $events,
            'totalRevenue' => $totalRevenue / 100,
            'pendingRefunds' => $pendingRefunds,
            'recentBookings' => $bookings,
        ]);
    }
}
