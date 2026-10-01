<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(50, 500);

        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement([
                'Early Bird',
                'Regular',
                'VIP',
            ]),
            'price' => fake()->randomFloat(2, 500, 10000),
            'quantity' => $quantity,
            'available_quantity' => $quantity,
        ];
    }
}
