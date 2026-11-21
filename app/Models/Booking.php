<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $event_id
 * @property string $reference
 * @property BookingStatus $status
 * @property PaymentStatus|null $payment_status
 * @property string $total_amount
 * @property CarbonImmutable|null $tickets_released_at
 * @property CarbonImmutable|null $payment_hold_expires_at
 * @property CarbonInterface|null $created_at
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'reference',
        'status',
        'total_amount',
        'payment_status',
        'tickets_released_at',
        'payment_hold_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'status' => BookingStatus::class,
            'total_amount' => 'decimal:2',
            'payment_hold_expires_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<BookingItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * Bookings that still hold the customer's place for the event: confirmed
     * bookings, and pending ones whose payment hold has not expired yet.
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('status', BookingStatus::Confirmed)
            ->orWhere(fn (Builder $query) => $query
                ->where('status', BookingStatus::Pending)
                ->where('payment_hold_expires_at', '>', now())));
    }

    /** @return HasMany<PaymentAttempt, $this> */
    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
