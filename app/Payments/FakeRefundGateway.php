<?php

namespace App\Payments;

use App\Models\Refund;

class FakeRefundGateway implements RefundGateway
{
    public function send(Refund $refund): ?string
    {
        return null;
    }
}
