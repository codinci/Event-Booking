<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_create_an_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $this->assertTrue(
            $organizer->can('create', Event::class)
        );
    }

    public function test_regular_user_cannot_create_an_event(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);

        $this->assertFalse(
            $user->can('create', Event::class)
        );
    }

    public function test_organizer_can_update_their_own_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
        ]);

        $this->assertTrue(
            $organizer->can('update', $event)
        );
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
        ]);

        $this->assertFalse(
            $organizer->can('update', $event)
        );
    }

    public function test_regular_user_cannot_update_an_event(): void
    {
        $user = User::factory()->create();

        $event = Event::factory()->create();

        $this->assertFalse(
            $user->can('update', $event)
        );
    }

    public function test_organizer_can_delete_their_own_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
        ]);

        $this->assertTrue(
            $organizer->can('delete', $event)
        );
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

        $this->assertFalse(
            $organizer->can('delete', $event)
        );
    }

    public function test_organizer_can_publish_their_own_event(): void
    {
        $organizer = User::factory()
            ->organizer()
            ->create();

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
        ]);

        $this->assertTrue(
            $organizer->can('publish', $event)
        );
    }

    public function test_organizer_cannot_publish_another_organizers_event(): void
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

        $this->assertFalse(
            $organizer->can('publish', $event)
        );
    }
}