<?php

namespace App\Payments;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Moves refunds through approval, payout and settlement. Every step is an
 * atomic status transition, so double clicks, concurrent scheduler runs and
 * repeated callbacks can never send or settle a refund twice.
 */
class RefundPayouts
{
    public function __construct(private readonly RefundManager $gateways) {}

    public function approve(Refund $refund, User $admin): bool
    {
        // Clearing the previous attempt's ConversationID means a late callback
        // about a failed earlier payout can't settle the resend.
        return $refund->transition([RefundStatus::AwaitingApproval, RefundStatus::Failed], RefundStatus::Approved, [
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => null,
            'conversation_id' => null,
            'sent_at' => null,
        ]);
    }

    public function reject(Refund $refund, User $admin, string $reason): bool
    {
        return DB::transaction(function () use ($refund, $admin, $reason) {
            $rejected = $refund->transition([RefundStatus::AwaitingApproval, RefundStatus::Failed], RefundStatus::Rejected, [
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            if ($rejected) {
                $refund->paymentAttempt()->update(['refund_status' => 'rejected']);
                // The money stays with the platform, so the booking is no longer awaiting a refund.
                Booking::whereKey($refund->booking_id)->where('payment_status', PaymentStatus::RefundPending)
                    ->update(['payment_status' => PaymentStatus::Paid]);
            }

            return $rejected;
        });
    }

    /** Settle a refund that is in review after checking the outcome on the Daraja portal. */
    public function resolve(Refund $refund, RefundStatus $outcome): bool
    {
        if ($outcome === RefundStatus::Completed) {
            return $this->complete($refund, [RefundStatus::Review]);
        }

        return $refund->transition(RefundStatus::Review, RefundStatus::Failed);
    }

    /** Submit an approved refund to its gateway. */
    public function send(Refund $refund): void
    {
        if (! $refund->transition(RefundStatus::Approved, RefundStatus::Sending)) {
            return;
        }

        try {
            $conversationId = $this->gateways->driver($refund->paymentAttempt->gateway)->send($refund);
        } catch (ConnectionException) {
            // The provider may have received the payout. Never resend it automatically.
            $refund->transition(RefundStatus::Sending, RefundStatus::Review, [
                'result_desc' => 'No response from the gateway. Check the Daraja portal before resending.',
            ]);

            return;
        } catch (RequestException $exception) {
            $status = $exception->response->status();
            $rejected = $status >= 400 && $status < 500 || $exception->response->successful();
            $refund->transition(RefundStatus::Sending, $rejected ? RefundStatus::Failed : RefundStatus::Review, [
                'result_code' => (string) ($exception->response->json('errorCode') ?? $exception->response->json('ResponseCode') ?? $status),
                'result_desc' => substr((string) ($exception->response->json('errorMessage')
                    ?? $exception->response->json('ResponseDescription') ?? 'Gateway rejected the payout.'), 0, 255),
            ]);

            return;
        } catch (Throwable $exception) {
            // Anything else (bad config, an unexpected response) leaves it unknown
            // whether the payout went out, so a person has to check it. Report the
            // error but keep going so one bad refund doesn't block the rest.
            report($exception);
            $refund->transition(RefundStatus::Sending, RefundStatus::Review, [
                'result_desc' => substr('Unexpected error while sending: '.$exception->getMessage(), 0, 255),
            ]);

            return;
        }

        if ($conversationId === null) {
            $this->complete($refund, [RefundStatus::Sending]);

            return;
        }

        $refund->transition(RefundStatus::Sending, RefundStatus::Sent, [
            'originator_conversation_id' => $refund->reference,
            'conversation_id' => $conversationId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Apply a Daraja B2C result callback.
     *
     * The reference and OriginatorConversationID are both in the callback URL, so
     * anyone who sees it could forge them. Only the ConversationID Daraja returned
     * when it accepted the payout proves the callback is about that payout.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleResult(string $reference, array $payload): void
    {
        $result = $payload['Result'] ?? [];
        $refund = Refund::where('reference', $reference)->where('status', RefundStatus::Sent)->first();

        if (! $refund || ! is_array($result) || $refund->conversation_id === null
            || ! hash_equals($refund->conversation_id, (string) ($result['ConversationID'] ?? ''))
            || ($result['OriginatorConversationID'] ?? null) !== $refund->reference) {
            return;
        }

        $code = (string) ($result['ResultCode'] ?? '');
        $details = [
            'result_code' => $code,
            'result_desc' => substr((string) ($result['ResultDesc'] ?? ''), 0, 255),
            'transaction_id' => $result['TransactionID'] ?? null,
        ];

        if ($code === '0') {
            $this->complete($refund, [RefundStatus::Sent], $details);
        } else {
            $refund->transition(RefundStatus::Sent, RefundStatus::Failed, $details);
        }
    }

    /**
     * Daraja's queue timed out on the payout. That doesn't prove it wasn't paid,
     * so it goes to review rather than failed, where it could be approved again.
     */
    public function handleTimeout(string $reference): void
    {
        Refund::where('reference', $reference)->first()?->transition(RefundStatus::Sent, RefundStatus::Review, [
            'result_desc' => 'Daraja queue timeout. Check the Daraja portal before resending.',
        ]);
    }

    /** Refunds stuck without an outcome need a person to check them. */
    public function flagStale(): void
    {
        Refund::where('status', RefundStatus::Sent)
            ->where('sent_at', '<=', now()->subMinutes(config('payments.refund_result_minutes')))
            ->update([
                'status' => RefundStatus::Review,
                'result_desc' => 'No result from Daraja. Check the Daraja portal.',
                'updated_at' => now(),
            ]);

        // A process that died mid-send leaves the refund in "sending" with no outcome.
        Refund::where('status', RefundStatus::Sending)
            ->where('updated_at', '<=', now()->subMinutes(5))
            ->update([
                'status' => RefundStatus::Review,
                'result_desc' => 'Sending was interrupted. Check the Daraja portal before resending.',
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  list<RefundStatus>  $from
     * @param  array<string, mixed>  $details
     */
    private function complete(Refund $refund, array $from, array $details = []): bool
    {
        return DB::transaction(function () use ($refund, $from, $details) {
            if (! $refund->transition($from, RefundStatus::Completed, $details)) {
                return false;
            }

            $refund->paymentAttempt()->update(['refund_status' => 'refunded']);
            Booking::whereKey($refund->booking_id)->update(['payment_status' => PaymentStatus::Refunded]);

            return true;
        });
    }
}
