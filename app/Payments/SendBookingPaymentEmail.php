<?php

namespace App\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\PaymentAttempt;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBookingPaymentEmail
{
    public function send(PaymentAttempt $attempt): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $claimed = PaymentAttempt::whereKey($attempt->id)->where('email_status', 'pending')
            ->where('status', 'succeeded')->whereNotNull('receipt_number')
            ->whereHas('booking', fn ($query) => $query
                ->where('status', BookingStatus::Confirmed)
                ->where('payment_status', PaymentStatus::Paid))
            ->update(['email_status' => 'sending']);

        if (! $claimed) {
            return;
        }

        try {
            $booking = $attempt->booking;
            $body = "Your sandbox booking {$booking->reference} for {$booking->event->title} is confirmed. Your receipt is attached.";
            $receipt = view('bookings.receipt', ['booking' => $booking, 'attempt' => $attempt])->render();

            $sent = Mail::mailer('payment_sandbox')->raw($body, fn (Message $message) => $message
                ->to($booking->user->email)
                ->subject("Sandbox booking {$booking->reference} confirmed")
                ->attachData($receipt, $attempt->receipt_number.'.html', ['mime' => 'text/html']));
        } catch (Throwable $exception) {
            $attempt->update(['email_status' => 'review']);
            report($exception);

            return;
        }

        $attempt->update(['email_status' => $sent === null ? 'review' : 'sent']);
    }
}
