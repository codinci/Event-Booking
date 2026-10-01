<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use App\Jobs\ProcessBooking;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_booking(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'price' => 50,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $booking = app(BookingService::class)->create(
            $user,
            $event,
            [
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 2,
                ],
            ],
        );

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => BookingStatus::Pending->value,
            'total_amount' => 100,
        ]);

        $this->assertDatabaseHas('booking_items', [
            'booking_id' => $booking->id,
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

    public function test_booking_can_contain_multiple_ticket_types(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $regular = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'Regular',
            'price' => 50,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $vip = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'VIP',
            'price' => 100,
            'quantity' => 50,
            'available_quantity' => 50,
        ]);

        $booking = app(BookingService::class)->create(
            $user,
            $event,
            [
                [
                    'ticket_type_id' => $regular->id,
                    'quantity' => 2,
                ],
                [
                    'ticket_type_id' => $vip->id,
                    'quantity' => 1,
                ],
            ],
        );

        $this->assertEquals(200, $booking->total_amount);

        $this->assertDatabaseCount('booking_items', 2);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $regular->id,
            'available_quantity' => 98,
        ]);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $vip->id,
            'available_quantity' => 49,
        ]);
    }

    public function test_user_cannot_book_more_tickets_than_available(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'price' => 50,
            'quantity' => 2,
            'available_quantity' => 2,
        ]);

        $this->expectException(ValidationException::class);

        app(BookingService::class)->create(
            $user,
            $event,
            [
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 3,
                ],
            ],
        );

        $this->assertDatabaseCount('bookings', 0);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'available_quantity' => 2,
        ]);
    }

    public function test_user_cannot_book_sold_out_ticket_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'price' => 50,
            'quantity' => 10,
            'available_quantity' => 0,
        ]);

        $this->expectException(ValidationException::class);

        app(BookingService::class)->create(
            $user,
            $event,
            [
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 1,
                ],
            ],
        );

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_ticket_type_must_belong_to_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $otherEvent = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $otherEvent->id,
            'available_quantity' => 10,
        ]);

        $this->expectException(ValidationException::class);

        app(BookingService::class)->create(
            $user,
            $event,
            [
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 1,
                ],
            ],
        );

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_duplicate_ticket_type_is_rejected_by_the_service(): void
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

        $this->expectException(ValidationException::class);

        app(BookingService::class)->create(
            $user,
            $event,
            [
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 1,
                ],
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 2,
                ],
            ],
        );

        $this->assertDatabaseCount('bookings', 0);

        $this->assertDatabaseCount('booking_items', 0);

        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'available_quantity' => 100,
        ]);
    }

    public function test_post_booking_job_is_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'price' => 50,
            'quantity' => 10,
            'available_quantity' => 10,
        ]);

        $booking = app(BookingService::class)->create(
            $user,
            $event,
            [
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 1,
                ],
            ],
        );

        Queue::assertPushed(
            ProcessBooking::class,
            fn (ProcessBooking $job) => $job->booking->is($booking),
        );
    }

    public function test_post_booking_job_is_not_dispatched_when_booking_fails(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $event = Event::factory()->create();

        $ticketType = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'Regular',
            'price' => 50,
            'quantity' => 1,
            'available_quantity' => 1,
        ]);

        try {
            app(BookingService::class)->create(
                $user,
                $event,
                [
                    [
                        'ticket_type_id' => $ticketType->id,
                        'quantity' => 2,
                    ],
                ],
            );
        } catch (ValidationException) {
            // Expected.
        }

        Queue::assertNothingPushed();
    }
}