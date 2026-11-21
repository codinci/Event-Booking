<?php

namespace App\Payments;

use App\Models\PaymentAttempt;

interface PaymentGateway
{
    public function start(PaymentAttempt $attempt): ?string;

    public function check(PaymentAttempt $attempt): PaymentResult;
}
