<?php

namespace App\Payments;

use Illuminate\Support\Manager;

class RefundManager extends Manager
{
    public function getDefaultDriver(): ?string
    {
        return null;
    }

    protected function createFakeDriver(): RefundGateway
    {
        return new FakeRefundGateway;
    }

    protected function createDarajaDriver(): RefundGateway
    {
        return new DarajaB2cGateway(
            $this->config->get('payments.daraja'),
            $this->config->get('payments.daraja_b2c'),
        );
    }
}
