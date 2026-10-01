<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_a_booking(): void
    {
        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'Regular',
            'price' => 50,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $response = $this->post(route('bookings.store', $event), [
            'items' => [
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_authenticated_user_can_create_a_booking(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'Regular',
            'price' => 50,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('bookings.store', $event), [
                'items' => [
                    [
                        'ticket_type_id' => $ticketType->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::Pending->value,
            'total_amount' => 100,
        ]);

        $this->assertDatabaseHas('booking_items', [
            'ticket_type_id' => $ticketType->id,
            'quantity' => 2,
            'unit_price' => 50,
            'total' => 100,
        ]);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'available_quantity' => 98,
        ]);
    }

    public function test_booking_requires_items(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('bookings.store', $event), []);

        $response->assertSessionHasErrors('items');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_requires_at_least_one_item(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('bookings.store', $event), [
                'items' => [],
            ]);

        $response->assertSessionHasErrors('items');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_ticket_type_id_must_be_distinct(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'Regular',
            'price' => 50,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('bookings.store', $event), [
                'items' => [
                    [
                        'ticket_type_id' => $ticketType->id,
                        'quantity' => 1,
                    ],
                    [
                        'ticket_type_id' => $ticketType->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items.1.ticket_type_id');

        $this->assertDatabaseCount('bookings', 0);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'available_quantity' => 100,
        ]);
    }

    public function test_ticket_quantity_must_be_at_least_one(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'Regular',
            'price' => 50,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('bookings.store', $event), [
                'items' => [
                    [
                        'ticket_type_id' => $ticketType->id,
                        'quantity' => 0,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items.0.quantity');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_ticket_type_from_another_event_cannot_be_booked(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $otherEvent = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $otherEvent->id,
            'name' => 'Regular',
            'price' => 50,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('bookings.store', $event), [
                'items' => [
                    [
                        'ticket_type_id' => $ticketType->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items');

        $this->assertDatabaseCount('bookings', 0);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'available_quantity' => 100,
        ]);
    }

    public function test_user_cannot_book_more_tickets_than_available(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'Regular',
            'price' => 50,
            'quantity' => 2,
            'available_quantity' => 2,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('bookings.store', $event), [
                'items' => [
                    [
                        'ticket_type_id' => $ticketType->id,
                        'quantity' => 3,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items');

        $this->assertDatabaseCount('bookings', 0);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'available_quantity' => 2,
        ]);

        Queue::assertNothingPushed();
    }
}