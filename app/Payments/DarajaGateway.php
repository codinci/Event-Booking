<?php

namespace App\Payments;

use App\Models\PaymentAttempt;
use Illuminate\Http\Client\PendingRequest;

class DarajaGateway implements PaymentGateway
{
    /** @param array<string, mixed> $config Daraja `key`, `secret`, `shortcode` and `passkey`. */
    public function __construct(private readonly array $config) {}

    public function start(PaymentAttempt $attempt): ?string
    {
        $response = $this->request()->post('/mpesa/stkpush/v1/processrequest', $this->credentials() + [
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => intdiv($attempt->amount, 100),
            'PartyA' => ltrim($attempt->phone, '+'),
            'PartyB' => $this->config['shortcode'],
            'PhoneNumber' => ltrim($attempt->phone, '+'),
            'CallBackURL' => DarajaClient::callbackBase().route('payments.daraja.callback', $attempt->reference, false),
            'AccountReference' => substr(str_replace('-', '', $attempt->reference), 0, 12),
            'TransactionDesc' => 'Event booking',
        ])->throw();

        $reference = $response->json('CheckoutRequestID');

        return is_string($reference) && $reference !== '' ? $reference : null;
    }

    public function check(PaymentAttempt $attempt): PaymentResult
    {
        $response = $this->request()->post('/mpesa/stkpushquery/v1/query', $this->credentials() + [
            'CheckoutRequestID' => $attempt->provider_reference,
        ])->throw();

        if ($response->json('CheckoutRequestID') !== $attempt->provider_reference) {
            return PaymentResult::Pending;
        }

        return match ((string) $response->json('ResultCode')) {
            '0' => PaymentResult::Succeeded,
            '1032', '1037' => PaymentResult::Failed,
            default => PaymentResult::Pending,
        };
    }

    /** @return array<string, string> */
    private function credentials(): array
    {
        $timestamp = now()->setTimezone('Africa/Nairobi')->format('YmdHis');
        $shortcode = $this->config['shortcode'];

        return [
            'BusinessShortCode' => $shortcode,
            'Password' => base64_encode($shortcode.$this->config['passkey'].$timestamp),
            'Timestamp' => $timestamp,
        ];
    }

    private function request(): PendingRequest
    {
        return DarajaClient::request($this->config);
    }
}
