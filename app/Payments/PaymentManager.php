<?php

namespace App\Payments;

use Illuminate\Support\Manager;

class PaymentManager extends Manager
{
    public function getDefaultDriver(): ?string
    {
        return null;
    }

    protected function createFakeDriver(): PaymentGateway
    {
        return new FakeGateway;
    }

    protected function createDarajaDriver(): PaymentGateway
    {
        return new DarajaGateway($this->config->get('payments.daraja'));
    }
}
