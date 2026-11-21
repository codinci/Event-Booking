<?php

namespace App\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Enums\RefundStatus;
use App\Events\PaymentSucceeded;
use App\Models\Booking;
use App\Models\PaymentAttempt;
use App\Services\ReleaseBookingTicketsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingPayments
{
    public function __construct(
        private readonly PaymentManager $manager,
        private readonly ReleaseBookingTicketsService $release,
        private readonly RefundRequests $refunds,
    ) {}

    /** @return array<string, string> Gateway key => label, for gateways usable in this environment. */
    public function gateways(): array
    {
        if (! app()->environment('local', 'testing')) {
            return [];
        }

        $gateways = ['fake' => 'Simulated payment'];

        if (collect(config()->array('payments.daraja'))->every(fn ($value) => filled($value))
            && str_starts_with(config('payments.daraja_callback_url') ?: config('app.url'), 'https://')) {
            $gateways['daraja'] = 'M-PESA sandbox';
        }

        return $gateways;
    }

    public function start(Booking $booking, string $gateway, ?string $phone): void
    {
        $attempt = DB::transaction(function () use ($booking, $gateway, $phone) {
            $booking->user()->lockForUpdate()->firstOrFail();
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (! $booking->payment_hold_expires_at?->isFuture() || $booking->status !== BookingStatus::Pending) {
                throw ValidationException::withMessages(['gateway' => 'This reservation is no longer payable.']);
            }

            $unresolved = PaymentAttempt::query()
                ->where(fn ($query) => $query->whereIn('status', ['pending', 'review'])
                    // A late payment that hasn't been refunded yet means the customer is already out of pocket.
                    ->orWhereHas('refund', fn ($query) => $query
                        ->where('reason', RefundReason::LatePayment)->whereIn('status', RefundStatus::unresolved())))
                ->whereHas('booking', fn ($query) => $query
                    ->where('user_id', $booking->user_id)->where('event_id', $booking->event_id))
                ->exists();

            if ($unresolved) {
                throw ValidationException::withMessages(['gateway' => 'A previous payment for this event is still being checked.']);
            }

            $amount = (int) str_replace('.', '', $booking->total_amount);
            if ($gateway === 'daraja' && ($amount < 100 || $amount % 100 !== 0)) {
                throw ValidationException::withMessages(['gateway' => 'M-PESA requires a positive whole-shilling amount.']);
            }

            $share = intdiv($amount * 80 + 50, 100);
            $booking->update(['payment_status' => PaymentStatus::Processing]);

            return PaymentAttempt::create([
                'booking_id' => $booking->id,
                'organizer_id' => $booking->event->organizer_id,
                'reference' => (string) Str::uuid(),
                'gateway' => $gateway,
                'status' => 'pending',
                'currency' => 'KES',
                'amount' => $amount,
                'organizer_share' => $share,
                'platform_share' => $amount - $share,
                'phone' => $phone,
                'next_check_at' => now()->addSeconds(30),
                'review_at' => now()->addMinutes(config('payments.review_minutes')),
            ]);
        }, 3);

        try {
            $reference = $this->manager->driver($gateway)->start($attempt);
        } catch (ConnectionException|RequestException) {
            // The provider may have received the submission. Reconcile without resending.
            return;
        }

        if ($reference !== null) {
            $attempt->update(['provider_reference' => $reference]);
        }

        if ($gateway === 'fake') {
            $this->reconcile($attempt);
        }
    }

    public function reconcile(PaymentAttempt $attempt): void
    {
        $result = PaymentResult::Pending;
        $delay = 30;
        $cooldown = (int) Cache::get('payments:'.$attempt->gateway.':query-cooldown', 0);
        if ($cooldown > now()->getTimestamp()) {
            $delay = $cooldown - now()->getTimestamp();
        } elseif ($attempt->provider_reference !== null) {
            try {
                $result = $this->manager->driver($attempt->gateway)->check($attempt);
            } catch (RequestException $exception) {
                $delay = 60;
                if ($exception->response->status() === 429) {
                    $retryAfter = $exception->response->header('Retry-After');
                    $delay = ctype_digit($retryAfter) ? max(60, (int) $retryAfter) : 120;
                    Cache::put('payments:'.$attempt->gateway.':query-cooldown', now()->getTimestamp() + $delay, $delay);
                }
            } catch (ConnectionException) {
                $delay = 60;
            }
        }

        $confirmed = DB::transaction(function () use ($attempt, $result, $delay): bool {
            $booking = Booking::query()->lockForUpdate()->findOrFail($attempt->booking_id);
            $attempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if (! in_array($attempt->status, ['pending', 'review'], true)) {
                return false;
            }

            if ($result === PaymentResult::Pending) {
                $review = $attempt->review_at->isPast();
                $attempt->update([
                    'status' => $review ? 'review' : 'pending',
                    'next_check_at' => now()->addSeconds($review ? max(900, $delay) : $delay),
                ]);

                return false;
            }

            $active = $booking->status === BookingStatus::Pending
                && $booking->tickets_released_at === null
                && $booking->payment_hold_expires_at?->isFuture();

            if ($result === PaymentResult::Failed) {
                $attempt->update(['status' => 'failed', 'next_check_at' => null]);
                if ($active) {
                    $booking->update(['payment_status' => PaymentStatus::Pending]);
                }

                return false;
            }

            if (! $active) {
                $this->release->failAndRelease($booking);
                $attempt->update([
                    'status' => 'late_success',
                    'next_check_at' => null,
                ]);
                $this->refunds->request($attempt, RefundReason::LatePayment);

                return false;
            }

            $booking->update(['status' => BookingStatus::Confirmed, 'payment_status' => PaymentStatus::Paid]);
            $attempt->update([
                'status' => 'succeeded',
                'next_check_at' => null,
                'sms_status' => $attempt->phone ? 'pending' : null,
                'receipt_number' => 'RCT-'.$booking->reference,
                'email_status' => 'pending',
            ]);

            return true;
        }, 3);

        // Dispatched after commit so listeners never see an unconfirmed booking.
        if ($confirmed) {
            PaymentSucceeded::dispatch($attempt->booking_id);
        }
    }

    public function expire(int $bookingId): void
    {
        DB::transaction(function () use ($bookingId) {
            $booking = Booking::query()->lockForUpdate()->find($bookingId);

            if ($booking?->status === BookingStatus::Pending && $booking->payment_hold_expires_at?->isPast()) {
                $this->release->failAndRelease($booking);
            }
        }, 3);
    }
}
