<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookingIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('bookings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_unverified_users_are_redirected_to_email_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_user_sees_an_empty_list_when_they_have_no_bookings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Index')
                ->has('bookings', 0)
            );
    }

    public function test_user_only_sees_their_own_bookings(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = Booking::factory()->create(['user_id' => $user->id]);
        Booking::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Index')
                ->has('bookings', 1)
                ->where('bookings.0.reference', $mine->reference)
            );
    }

    public function test_each_booking_includes_event_status_and_total_details(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'title' => 'Nairobi Tech Summit 2026',
            'venue' => 'KICC',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(3),
        ]);

        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'total_amount' => 150,
        ]);

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Index')
                ->where('bookings.0.id', $booking->id)
                ->where('bookings.0.reference', $booking->reference)
                ->where('bookings.0.status', BookingStatus::Confirmed->value)
                ->where('bookings.0.total_amount', '150.00')
                ->where('bookings.0.event.id', $event->id)
                ->where('bookings.0.event.title', 'Nairobi Tech Summit 2026')
                ->where('bookings.0.event.venue', 'KICC')
                ->where('bookings.0.is_past', false)
            );
    }

    public function test_bookings_for_past_events_are_flagged_as_past(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDays(3)->addHours(3),
        ]);

        Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Index')
                ->where('bookings.0.is_past', true)
            );
    }

    public function test_bookings_are_ordered_by_event_date_most_recent_first(): void
    {
        $user = User::factory()->create();

        $soon = Event::factory()->create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(3)]);
        $past = Event::factory()->create(['starts_at' => now()->subDays(10), 'ends_at' => now()->subDays(10)->addHours(3)]);
        $later = Event::factory()->create(['starts_at' => now()->addDays(30), 'ends_at' => now()->addDays(30)->addHours(3)]);

        $soonBooking = Booking::factory()->create(['user_id' => $user->id, 'event_id' => $soon->id]);
        $pastBooking = Booking::factory()->create(['user_id' => $user->id, 'event_id' => $past->id]);
        $laterBooking = Booking::factory()->create(['user_id' => $user->id, 'event_id' => $later->id]);

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Index')
                ->has('bookings', 3)
                ->where('bookings.0.id', $laterBooking->id)
                ->where('bookings.1.id', $soonBooking->id)
                ->where('bookings.2.id', $pastBooking->id)
            );
    }

    public function test_cancelled_bookings_are_listed_with_their_status(): void
    {
        $user = User::factory()->create();

        $booking = Booking::factory()->cancelled()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Index')
                ->where('bookings.0.id', $booking->id)
                ->where('bookings.0.status', BookingStatus::Cancelled->value)
            );
    }
}
