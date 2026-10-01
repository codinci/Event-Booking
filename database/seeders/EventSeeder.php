<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        Event::factory()
            ->count(4)
            ->has(
                TicketType::factory()
                    ->count(3)
                    ->sequence(
                        ['name' => 'Early Bird'],
                        ['name' => 'Regular'],
                        ['name' => 'VIP'],
                    ),
                'ticketTypes'
            )
            ->create([
                'status' => EventStatus::Published,
            ]);
    }
}
