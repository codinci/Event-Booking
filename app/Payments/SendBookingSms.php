<?php

namespace App\Payments;

use App\Models\PaymentAttempt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SendBookingSms
{
    public function send(PaymentAttempt $attempt): void
    {
        if ($attempt->gateway === 'fake') {
            PaymentAttempt::whereKey($attempt->id)->where('sms_status', 'pending')->update(['sms_status' => 'simulated']);

            return;
        }

        if (config('payments.sms.username') !== 'sandbox' || blank(config('payments.sms.key'))) {
            return;
        }

        $claimed = PaymentAttempt::whereKey($attempt->id)->where('sms_status', 'pending')
            ->update(['sms_status' => 'sending']);

        if (! $claimed) {
            return;
        }

        $booking = $attempt->booking;
        try {
            $response = Http::asForm()->timeout(8)->acceptJson()
                ->withHeaders(['apiKey' => config('payments.sms.key')])
                ->post('https://api.sandbox.africastalking.com/version1/messaging', [
                    'username' => 'sandbox',
                    'to' => $attempt->phone,
                    'message' => "Booking {$booking->reference} confirmed for {$booking->event->title}.",
                ]);
        } catch (ConnectionException) {
            $attempt->update(['sms_status' => 'review']);

            return;
        }

        $accepted = $response->successful()
            && (int) $response->json('SMSMessageData.Recipients.0.statusCode') === 101;
        $attempt->update(['sms_status' => $accepted ? 'accepted' : 'review']);
    }
}
