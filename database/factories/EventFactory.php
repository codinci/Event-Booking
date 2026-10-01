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
        $events = [
            [
                'title' => 'Nairobi Tech Summit 2026',
                'venue' => 'KICC',
                'location' => 'Nairobi, Kenya',
                'image_url' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678',
            ],
            [
                'title' => 'Kenya Music & Arts Festival',
                'venue' => 'Sarit Expo Centre',
                'location' => 'Westlands, Nairobi',
                'image_url' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30',
            ],
            [
                'title' => 'Laravel & Vue Developer Meetup',
                'venue' => 'iHub',
                'location' => 'Kilimani, Nairobi',
                'image_url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87',
            ],
            [
                'title' => 'Business Networking Evening',
                'venue' => 'Radisson Blu',
                'location' => 'Upper Hill, Nairobi',
                'image_url' => 'https://images.unsplash.com/photo-1511578314322-379afb476865',
            ],
        ];

        $event = fake()->randomElement($events);

        $startsAt = fake()->dateTimeBetween('+1 week', '+3 months');
        $endsAt = (clone $startsAt)->modify('+3 hours');

        return [
            'organizer_id' => User::factory()->organizer(),

            'title' => $event['title'],
            'description' => fake()->paragraph(),
            'venue' => $event['venue'],
            'location' => $event['location'],
            'image_url' => $event['image_url'],

            'starts_at' => $startsAt,
            'ends_at' => $endsAt,

            'status' => EventStatus::Published,
        ];
    }
}
