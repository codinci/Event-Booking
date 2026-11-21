<?php

namespace App\Payments;

use App\Enums\RefundReason;
use App\Enums\RefundStatus;
use App\Models\PaymentAttempt;
use App\Models\Refund;
use Illuminate\Support\Str;

class RefundRequests
{
    /**
     * Open a refund for a successful payment. Each payment gets at most
     * one refund, so calling this again returns the existing one.
     */
    public function request(PaymentAttempt $attempt, RefundReason $reason): Refund
    {
        $refund = Refund::createOrFirst(['payment_attempt_id' => $attempt->id], [
            'reference' => (string) Str::uuid(),
            'booking_id' => $attempt->booking_id,
            'reason' => $reason,
            'amount' => $this->amount($attempt, $reason),
            'phone' => $attempt->phone,
            'status' => RefundStatus::AwaitingApproval,
        ]);

        if ($refund->wasRecentlyCreated) {
            $attempt->update(['refund_status' => 'requested']);
        }

        return $refund;
    }

    /**
     * Late payments are refunded in full because the customer got nothing.
     * Cancellations refund the organizer's share; the platform keeps its fee.
     * B2C only pays whole shillings, so the amount is rounded down.
     */
    public function amount(PaymentAttempt $attempt, RefundReason $reason): int
    {
        $cents = $reason === RefundReason::LatePayment
            ? (int) $attempt->amount
            : (int) $attempt->organizer_share;

        return intdiv($cents, 100) * 100;
    }
}
