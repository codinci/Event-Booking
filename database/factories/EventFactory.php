<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\User;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 week', '+3 months');
        $endsAt = (clone $startsAt)->modify('+3 hours');

        return [
            'organizer_id' => User::factory()->organizer(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'venue' => fake()->address(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => EventStatus::Draft,
        ];
    }
}
