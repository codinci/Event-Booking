<?php

namespace App\Payments;

use App\Models\PaymentAttempt;

class FakeGateway implements PaymentGateway
{
    public function start(PaymentAttempt $attempt): string
    {
        return $attempt->reference;
    }

    public function check(PaymentAttempt $attempt): PaymentResult
    {
        return PaymentResult::Succeeded;
    }
}
