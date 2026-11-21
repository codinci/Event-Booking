<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $booking_id
 * @property string $status
 * @property int $amount
 * @property string|null $refund_status
 * @property string|null $receipt_number
 * @property CarbonImmutable|null $next_check_at
 * @property CarbonImmutable $review_at
 */
class PaymentAttempt extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'next_check_at' => 'immutable_datetime',
            'review_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return HasOne<Refund, $this> */
    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }
}
