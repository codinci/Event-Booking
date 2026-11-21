<?php

namespace App\Payments;

use App\Models\Refund;

interface RefundGateway
{
    /**
     * Submit the payout. Returns the provider's conversation id when it
     * accepted the payout for processing, or null when it completed at once.
     */
    public function send(Refund $refund): ?string;
}
