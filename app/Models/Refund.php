<?php

namespace App\Models;

use App\Enums\RefundReason;
use App\Enums\RefundStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $reference
 * @property int $payment_attempt_id
 * @property int $booking_id
 * @property RefundReason $reason
 * @property int $amount
 * @property string|null $phone
 * @property RefundStatus $status
 * @property int|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property string|null $rejection_reason
 * @property string|null $originator_conversation_id
 * @property string|null $conversation_id
 * @property string|null $transaction_id
 * @property string|null $result_code
 * @property string|null $result_desc
 * @property CarbonImmutable|null $sent_at
 * @property-read PaymentAttempt $paymentAttempt
 * @property-read Booking $booking
 * @property-read User|null $approver
 */
class Refund extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reason' => RefundReason::class,
            'status' => RefundStatus::class,
            'approved_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PaymentAttempt, $this> */
    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Atomically move the refund from one status to another.
     * Returns false when another request already moved it.
     *
     * @param  RefundStatus|list<RefundStatus>  $from
     * @param  array<string, mixed>  $attributes
     */
    public function transition(RefundStatus|array $from, RefundStatus $to, array $attributes = []): bool
    {
        $claimed = static::whereKey($this->id)
            ->whereIn('status', is_array($from) ? $from : [$from])
            ->update(['status' => $to, ...$attributes, 'updated_at' => now()]) === 1;

        if ($claimed) {
            $this->refresh();
        }

        return $claimed;
    }
}
