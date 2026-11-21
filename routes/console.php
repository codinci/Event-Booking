<?php

use App\Enums\BookingStatus;
use App\Enums\RefundStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\PaymentAttempt;
use App\Models\Refund;
use App\Models\User;
use App\Payments\BookingPayments;
use App\Payments\RefundPayouts;
use App\Payments\SendBookingPaymentEmail;
use App\Payments\SendBookingSms;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payments:reconcile', function (BookingPayments $payments, SendBookingSms $sms, SendBookingPaymentEmail $email, RefundPayouts $refunds) {
    if (! app()->environment('local', 'testing')) {
        return;
    }

    PaymentAttempt::whereIn('status', ['pending', 'review'])->where('next_check_at', '<=', now())
        ->orderBy('next_check_at')->limit(20)->get()
        ->each(fn (PaymentAttempt $attempt) => $payments->reconcile($attempt));

    PaymentAttempt::where('sms_status', 'sending')->where('updated_at', '<=', now()->subMinutes(5))
        ->update(['sms_status' => 'review']);

    PaymentAttempt::where('sms_status', 'pending')->limit(20)->get()
        ->each(fn (PaymentAttempt $attempt) => $sms->send($attempt));

    PaymentAttempt::where('email_status', 'sending')->where('updated_at', '<=', now()->subMinutes(5))
        ->update(['email_status' => 'review']);

    PaymentAttempt::where('email_status', 'pending')->limit(20)->get()
        ->each(fn (PaymentAttempt $attempt) => $email->send($attempt));

    Refund::where('status', RefundStatus::Approved)->orderBy('approved_at')->limit(20)->get()
        ->each(fn (Refund $refund) => $refunds->send($refund));

    $refunds->flagStale();
})->purpose('Reconcile sandbox payments and SMS, and send approved refunds');

Artisan::command('bookings:expire-holds', function (BookingPayments $payments) {
    Booking::where('status', BookingStatus::Pending)->where('payment_hold_expires_at', '<=', now())
        ->eachById(fn (Booking $booking) => $payments->expire($booking->id));
})->purpose('Release tickets held by unpaid bookings whose payment window has passed');

Artisan::command('users:make-admin {email}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("No user with email {$email}.");

        return 1;
    }

    $user->forceFill(['role' => UserRole::Admin])->save();
    $this->info("{$user->name} is now an admin.");
})->purpose('Give an existing user the admin role so they can approve refunds');

Schedule::command('payments:reconcile')->everyThirtySeconds()->withoutOverlapping(5);

// Runs in every environment: unpaid holds must release tickets in production too.
Schedule::command('bookings:expire-holds')->everyMinute()->withoutOverlapping();
