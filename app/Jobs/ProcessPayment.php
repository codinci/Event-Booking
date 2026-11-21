<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPayment implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $bookingId) {}

    public function handle(): void
    {
        // Ignore simulation jobs queued before sandbox checkout was introduced.
    }
}
