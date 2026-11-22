<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PaymentAttempt;
use App\Payments\BookingPayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingPaymentController extends Controller
{
    public function receipt(Request $request, Booking $booking): View
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $attempt = PaymentAttempt::where('booking_id', $booking->id)->where('status', 'succeeded')
            ->whereNotNull('receipt_number')->firstOrFail();

        return view('bookings.receipt', [
            'booking' => $booking,
            'attempt' => $attempt,
            'backUrl' => route('bookings.show', $booking),
        ]);
    }

    public function store(Request $request, Booking $booking, BookingPayments $payments): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'gateway' => ['required', Rule::in(array_keys($payments->gateways()))],
            'phone' => ['nullable', 'required_if:gateway,daraja', 'regex:/^\+254[17]\d{8}$/'],
        ]);

        $payments->start($booking, $validated['gateway'], $validated['phone'] ?? null);

        return to_route('bookings.show', $booking);
    }

    public function callback(string $reference): Response
    {
        if (app()->environment('local', 'testing')) {
            PaymentAttempt::where('reference', $reference)
                ->where('gateway', 'daraja')->whereIn('status', ['pending', 'review'])
                ->whereNotNull('provider_reference')->update(['next_check_at' => now()]);
        }

        return response()->noContent();
    }
}
