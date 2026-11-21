<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExistingBookingTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEventWithTicket(): array
    {
        $event = Event::factory()->create([
            'status' => EventStatus::Published,
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(3),
        ]);

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'price' => 50,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        return [$event, $ticketType];
    }

    private function attemptBooking(User $user, Event $event, TicketType $ticketType)
    {
        return $this->actingAs($user)->post(route('bookings.store', $event), [
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_user_with_a_confirmed_booking_cannot_book_the_same_event_again(): void
    {
        [$event, $ticketType] = $this->publishedEventWithTicket();
        $user = User::factory()->create();

        Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'payment_hold_expires_at' => null,
        ]);

        $this->attemptBooking($user, $event, $ticketType)
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('ticket_types', ['id' => $ticketType->id, 'available_quantity' => 100]);
    }

    public function test_user_with_an_unexpired_pending_booking_cannot_book_the_same_event_again(): void
    {
        [$event, $ticketType] = $this->publishedEventWithTicket();
        $user = User::factory()->create();

        Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'payment_hold_expires_at' => now()->addMinutes(5),
        ]);

        $this->attemptBooking($user, $event, $ticketType)
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_user_with_an_expired_pending_booking_can_book_the_event_again(): void
    {
        [$event, $ticketType] = $this->publishedEventWithTicket();
        $user = User::factory()->create();

        Booking::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'payment_hold_expires_at' => now()->subMinute(),
        ]);

        $this->attemptBooking($user, $event, $ticketType)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_user_with_a_cancelled_booking_can_book_the_event_again(): void
    {
        [$event, $ticketType] = $this->publishedEventWithTicket();
        $user = User::factory()->create();

        Booking::factory()->cancelled()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        $this->attemptBooking($user, $event, $ticketType)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_another_users_booking_does_not_block_booking(): void
    {
        [$event, $ticketType] = $this->publishedEventWithTicket();
        $user = User::factory()->create();

        Booking::factory()->confirmed()->create([
            'event_id' => $event->id,
            'payment_hold_expires_at' => null,
        ]);

        $this->attemptBooking($user, $event, $ticketType)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_user_can_book_a_different_event_while_holding_a_booking(): void
    {
        [$event, $ticketType] = $this->publishedEventWithTicket();
        [$otherEvent, $otherTicketType] = $this->publishedEventWithTicket();
        $user = User::factory()->create();

        Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'payment_hold_expires_at' => null,
        ]);

        $this->attemptBooking($user, $otherEvent, $otherTicketType)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_booking_page_redirects_to_the_existing_booking(): void
    {
        [$event] = $this->publishedEventWithTicket();
        $user = User::factory()->create();

        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'payment_hold_expires_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('bookings.create', $event))
            ->assertRedirect(route('bookings.show', $booking));
    }

    public function test_event_page_shows_the_users_existing_booking(): void
    {
        [$event] = $this->publishedEventWithTicket();
        $user = User::factory()->create();

        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'payment_hold_expires_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Events/Show')
                ->where('existingBooking.id', $booking->id)
                ->where('existingBooking.reference', $booking->reference)
            );
    }

    public function test_event_page_has_no_existing_booking_for_guests_or_users_without_one(): void
    {
        [$event] = $this->publishedEventWithTicket();

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Events/Show')
                ->where('existingBooking', null)
            );

        $this->actingAs(User::factory()->create())
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('existingBooking', null)
            );
    }
}
