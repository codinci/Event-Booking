<?php

namespace App\Payments;

use App\Models\Refund;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class DarajaB2cGateway implements RefundGateway
{
    /**
     * @param  array<string, mixed>  $credentials  Daraja app `key` and `secret`.
     * @param  array<string, mixed>  $config  B2C `shortcode`, `initiator_name`, `security_credential`.
     */
    public function __construct(private readonly array $credentials, private readonly array $config) {}

    public function send(Refund $refund): string
    {
        $base = DarajaClient::callbackBase();

        $response = DarajaClient::request($this->credentials)->post('/mpesa/b2c/v3/paymentrequest', [
            'OriginatorConversationID' => $refund->reference,
            'InitiatorName' => $this->config['initiator_name'],
            'SecurityCredential' => $this->config['security_credential'],
            'CommandID' => 'BusinessPayment',
            'Amount' => intdiv($refund->amount, 100),
            'PartyA' => $this->config['shortcode'],
            'PartyB' => ltrim((string) $refund->phone, '+'),
            'Remarks' => 'Event booking refund',
            'QueueTimeOutURL' => $base.route('payments.daraja.b2c.timeout', $refund->reference, false),
            'ResultURL' => $base.route('payments.daraja.b2c.result', $refund->reference, false),
            'Occasion' => substr((string) $refund->booking->reference, 0, 100),
        ])->throw();

        // A non-zero ResponseCode means Daraja did not accept the payout.
        if ((string) $response->json('ResponseCode') !== '0') {
            throw new RequestException($response);
        }

        // Daraja accepted the payout, so it may be paid. Without its ConversationID
        // the result callback can't be verified, so the caller must not treat this
        // as a rejection that is safe to resend.
        $conversationId = $response->json('ConversationID');

        if (! is_string($conversationId) || $conversationId === '') {
            throw new RuntimeException('Daraja accepted the payout without returning a ConversationID.');
        }

        return $conversationId;
    }
}
