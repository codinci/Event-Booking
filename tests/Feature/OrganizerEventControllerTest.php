<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrganizerEventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_view_their_events(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        Event::factory()
            ->count(2)
            ->create([
                'organizer_id' => $organizer->id,
            ]);

        $this->actingAs($organizer)
            ->get(route('organizer.events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Organizer/Events/Index')
                ->has('events.data', 2)
            );
    }

    public function test_organizer_only_sees_their_own_events(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $otherOrganizer = User::factory()
            ->organizer()
            ->create();

        Event::factory()->create([
            'organizer_id' => $organizer->id,
            'title' => 'My Event',
        ]);

        Event::factory()->create([
            'organizer_id' => $otherOrganizer->id,
            'title' => 'Other Event',
        ]);

        $response = $this->actingAs($organizer)
            ->get(route('organizer.events.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Organizer/Events/Index')
                ->has('events.data', 1)
                ->where('events.data.0.title', 'My Event')
            );
    }

    public function test_organizer_can_view_create_event_page(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $this->actingAs($organizer)
            ->get(route('organizer.events.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Organizer/Events/Create')
            );
    }

    public function test_regular_user_cannot_access_organizer_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('organizer.events.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('organizer.events.create'))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_organizer_routes(): void
    {
        $this->get(route('organizer.events.index'))
            ->assertRedirect();

        $this->get(route('organizer.events.create'))
            ->assertRedirect();
    }

    public function test_organizer_can_create_an_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $data = [
            'title' => 'Laravel Conference 2026',
            'description' => 'A Laravel conference.',
            'venue' => 'Nairobi Convention Centre',
            'starts_at' => '2026-11-15 09:00:00',
            'ends_at' => '2026-11-15 17:00:00',
        ];

        $this->actingAs($organizer)
            ->post(route('organizer.events.store'), $data)
            ->assertRedirect(route('organizer.events.index'))
            ->assertSessionHas('success', 'Event created successfully.');

        $this->assertDatabaseHas('events', [
            'organizer_id' => $organizer->id,
            'title' => 'Laravel Conference 2026',
            'venue' => 'Nairobi Convention Centre',
            'status' => EventStatus::Draft->value,
        ]);
    }

    public function test_created_event_belongs_to_authenticated_organizer(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $otherOrganizer = User::factory()
            ->organizer()
            ->create();

        $data = [
            'title' => 'My Conference',
            'description' => 'Conference description.',
            'venue' => 'Nairobi',
            'starts_at' => '2026-11-15 09:00:00',
            'ends_at' => '2026-11-15 17:00:00',
        ];

        $this->actingAs($organizer)
            ->post(route('organizer.events.store'), $data)
            ->assertRedirect();

        $this->assertDatabaseHas('events', [
            'organizer_id' => $organizer->id,
            'title' => 'My Conference',
        ]);

        $this->assertDatabaseMissing('events', [
            'organizer_id' => $otherOrganizer->id,
            'title' => 'My Conference',
        ]);
    }

    public function test_event_creation_requires_required_fields(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $this->actingAs($organizer)
            ->post(route('organizer.events.store'), [])
            ->assertSessionHasErrors([
                'title',
                'venue',
                'starts_at',
                'ends_at',
            ]);
    }

    public function test_event_end_time_must_be_after_start_time(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $data = [
            'title' => 'Invalid Event',
            'description' => 'Invalid dates.',
            'venue' => 'Nairobi',
            'starts_at' => '2026-11-15 17:00:00',
            'ends_at' => '2026-11-15 09:00:00',
        ];

        $this->actingAs($organizer)
            ->post(route('organizer.events.store'), $data)
            ->assertSessionHasErrors('ends_at');

        $this->assertDatabaseMissing('events', [
            'title' => 'Invalid Event',
        ]);
    }

    public function test_organizer_can_edit_their_own_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
        ]);

        $this->actingAs($organizer)
            ->get(route('organizer.events.edit', $event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Organizer/Events/Edit')
                ->where('event.id', $event->id)
            );
    }

    public function test_organizer_cannot_edit_another_organizers_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $otherOrganizer = User::factory()
            ->organizer()
            ->create();

        $event = Event::factory()->create([
            'organizer_id' => $otherOrganizer->id,
        ]);

        $this->actingAs($organizer)
            ->get(route('organizer.events.edit', $event))
            ->assertForbidden();
    }

    public function test_organizer_can_update_their_own_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'title' => 'Original Title',
        ]);

        $data = [
            'title' => 'Updated Event',
            'description' => 'Updated description.',
            'venue' => 'Updated Venue',
            'starts_at' => '2026-12-01 09:00:00',
            'ends_at' => '2026-12-01 17:00:00',
        ];

        $this->actingAs($organizer)
            ->put(route('organizer.events.update', $event), $data)
            ->assertRedirect(route('organizer.events.index'))
            ->assertSessionHas('success', 'Event updated successfully.');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'organizer_id' => $organizer->id,
            'title' => 'Updated Event',
            'venue' => 'Updated Venue',
        ]);
    }

    public function test_organizer_cannot_update_another_organizers_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $otherOrganizer = User::factory()
            ->organizer()
            ->create();

        $event = Event::factory()->create([
            'organizer_id' => $otherOrganizer->id,
            'title' => 'Original Title',
        ]);

        $data = [
            'title' => 'Hacked Title',
            'description' => 'Attempted update.',
            'venue' => 'Hacked Venue',
            'starts_at' => '2026-12-01 09:00:00',
            'ends_at' => '2026-12-01 17:00:00',
        ];

        $this->actingAs($organizer)
            ->put(route('organizer.events.update', $event), $data)
            ->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Original Title',
        ]);
    }

    public function test_organizer_can_delete_their_own_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
        ]);

        $this->actingAs($organizer)
            ->delete(route('organizer.events.destroy', $event))
            ->assertRedirect(route('organizer.events.index'))
            ->assertSessionHas('success', 'Event deleted successfully.');

        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_organizer_cannot_delete_another_organizers_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $otherOrganizer = User::factory()
            ->organizer()
            ->create();

        $event = Event::factory()->create([
            'organizer_id' => $otherOrganizer->id,
        ]);

        $this->actingAs($organizer)
            ->delete(route('organizer.events.destroy', $event))
            ->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
        ]);
    }
}