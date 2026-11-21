<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentSucceeded;
use App\Models\Event;
use App\Models\PaymentAttempt;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\BookingPayments;
use App\Payments\FakeGateway;
use App\Payments\PaymentManager;
use App\Payments\PaymentResult;
use App\services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event as EventBus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SandboxPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.mailers.payment_sandbox' => ['transport' => 'array']]);
    }

    public function test_booking_owner_confirms_one_simulated_payment_and_records_both_shares(): void
    {
        [$booking] = $this->reserve();

        $this->actingAs(User::factory()->create())
            ->post(route('bookings.pay', $booking), ['gateway' => 'fake'])->assertForbidden();

        $this->actingAs($booking->user)->post(route('bookings.pay', $booking), [
            'gateway' => 'fake',
            'phone' => '+254700000001',
        ])->assertRedirect(route('bookings.show', $booking));

        $this->post(route('bookings.pay', $booking), ['gateway' => 'fake'])->assertSessionHasErrors('gateway');
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $booking->fresh()->payment_status);
        $this->assertDatabaseCount('payment_attempts', 1);
        $this->assertDatabaseHas('payment_attempts', [
            'organizer_id' => $booking->event->organizer_id,
            'amount' => 100000,
            'organizer_share' => 80000,
            'platform_share' => 20000,
            'sms_status' => 'pending',
        ]);

        Http::fake();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertDatabaseHas('payment_attempts', ['sms_status' => 'simulated']);
        Http::assertNothingSent();
    }

    public function test_expiry_releases_tickets_once_and_late_success_goes_to_refund(): void
    {
        [$booking, $ticket] = $this->reserve();
        $driver = new class extends FakeGateway
        {
            public PaymentResult $result = PaymentResult::Pending;

            public function check(PaymentAttempt $attempt): PaymentResult
            {
                return $this->result;
            }
        };
        app(PaymentManager::class)->extend('fake', fn () => $driver);

        $this->actingAs($booking->user)
            ->post(route('bookings.pay', $booking), ['gateway' => 'fake'])->assertRedirect();
        $this->post(route('bookings.pay', $booking), ['gateway' => 'fake'])->assertSessionHasErrors('gateway');

        $this->travel(6)->minutes();
        $payments = app(BookingPayments::class);
        $payments->expire($booking->id);
        $payments->expire($booking->id);

        $driver->result = PaymentResult::Succeeded;
        $attempt = PaymentAttempt::firstOrFail();
        $payments->reconcile($attempt);
        $payments->reconcile($attempt);

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertEquals(1, $ticket->fresh()->available_quantity);
        $this->assertDatabaseHas('payment_attempts', [
            'status' => 'late_success',
            'refund_status' => 'requested',
            'sms_status' => null,
        ]);
        $this->assertDatabaseCount('refunds', 1);
        $this->assertDatabaseHas('refunds', [
            'payment_attempt_id' => $attempt->id,
            'reason' => 'late_payment',
            'status' => 'awaiting_approval',
            'amount' => 100000,
        ]);

        // The unrefunded late payment still blocks paying again for the same event.
        $again = app(BookingService::class)->create($booking->user, $booking->event, [
            ['ticket_type_id' => $ticket->id, 'quantity' => 1],
        ]);
        $this->post(route('bookings.pay', $again), ['gateway' => 'fake'])
            ->assertSessionHasErrors(['gateway' => 'A previous payment for this event is still being checked.']);
    }

    public function test_expired_holds_are_released_by_the_expiry_command_in_production(): void
    {
        [$booking, $ticket] = $this->reserve();

        $this->travel(6)->minutes();
        $this->app['env'] = 'production';

        $this->artisan('bookings:expire-holds')->assertSuccessful();

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertEquals(1, $ticket->fresh()->available_quantity);
    }

    public function test_successful_payment_dispatches_payment_succeeded(): void
    {
        [$booking] = $this->reserve();
        EventBus::fake([PaymentSucceeded::class]);

        $this->actingAs($booking->user)
            ->post(route('bookings.pay', $booking), ['gateway' => 'fake'])
            ->assertRedirect();

        EventBus::assertDispatched(
            PaymentSucceeded::class,
            fn (PaymentSucceeded $event) => $event->bookingId === $booking->id,
        );
    }

    public function test_daraja_callback_waits_for_verified_query_and_submits_sms_once(): void
    {
        [$booking] = $this->reserve();
        $messages = [];
        EventBus::listen(MessageSent::class, function (MessageSent $event) use (&$messages): void {
            $messages[] = $event->message;
        });
        config([
            'app.url' => 'http://localhost',
            'payments.daraja_callback_url' => 'https://sandbox.example.test',
            'payments.daraja' => ['key' => 'test', 'secret' => 'test', 'shortcode' => '174379', 'passkey' => 'test'],
            'payments.sms' => ['username' => 'sandbox', 'key' => 'test'],
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
            'sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response(['CheckoutRequestID' => 'test-checkout']),
            'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([
                'CheckoutRequestID' => 'test-checkout',
                'ResultCode' => '0',
            ]),
            'api.sandbox.africastalking.com/*' => Http::response([
                'SMSMessageData' => ['Recipients' => [['statusCode' => 101]]],
            ]),
        ]);

        $this->actingAs($booking->user)->post(route('bookings.pay', $booking), [
            'gateway' => 'daraja',
            'phone' => '+254700000001',
        ])->assertRedirect(route('bookings.show', $booking));

        $attempt = PaymentAttempt::firstOrFail();
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            && $request['PartyA'] === '254700000001'
            && $request['PhoneNumber'] === '254700000001'
            && $request['CallBackURL'] === 'https://sandbox.example.test'.route('payments.daraja.callback', $attempt->reference, false));
        $this->post(route('payments.daraja.callback', $attempt->reference), ['ResultCode' => 0])->assertNoContent();
        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
        $this->assertCount(0, $messages);
        $this->get(route('bookings.receipt', $booking))->assertNotFound();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertSame('accepted', $attempt->fresh()->sms_status);
        $this->assertSame('sent', $attempt->fresh()->email_status);
        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertSame($booking->user->email, $message->getTo()[0]->getAddress());
        $this->assertStringNotContainsString('/receipt', $message->getTextBody());
        $this->assertCount(1, $message->getAttachments());
        $attachment = $message->getAttachments()[0];
        $this->assertSame('RCT-'.$booking->reference.'.html', $attachment->getFilename());
        $this->assertStringContainsString('KES 1,000.00', $attachment->getBody());
        $this->get(route('bookings.receipt', $booking))
            ->assertOk()
            ->assertSee('RCT-'.$booking->reference)
            ->assertSee('KES 1,000.00');

        $this->post(route('payments.daraja.callback', $attempt->reference))->assertNoContent();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertCount(1, $messages);
        Http::assertSentCount(5); // Two token requests, checkout, query, and one SMS.
        $this->assertDatabaseCount('payment_attempts', 1);
        $this->assertDatabaseHas('payment_attempts', ['receipt_number' => 'RCT-'.$booking->reference]);
        $this->actingAs(User::factory()->create())->get(route('bookings.receipt', $booking))->assertForbidden();
    }

    public function test_daraja_rate_limit_keeps_attempt_pending_and_defers_another_query(): void
    {
        [$booking] = $this->reserve();
        config(['payments.daraja' => ['key' => 'test', 'secret' => 'test', 'shortcode' => '174379', 'passkey' => 'test']]);
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
            'sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response(['CheckoutRequestID' => 'test-checkout']),
            'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([], 429, ['Retry-After' => '90']),
        ]);

        $payments = app(BookingPayments::class);
        $payments->start($booking, 'daraja', '+254700000001');
        $attempt = PaymentAttempt::firstOrFail();
        $payments->reconcile($attempt);
        $payments->reconcile($attempt->fresh());

        $attempt->refresh();
        $this->assertSame('pending', $attempt->status);
        $this->assertTrue($attempt->next_check_at->greaterThan(now()->addSeconds(85)));
        Http::assertSentCount(4);
    }

    private function reserve(): array
    {
        $event = Event::factory()->create();
        $ticket = TicketType::factory()->for($event)->create([
            'price' => '1000.00',
            'quantity' => 1,
            'available_quantity' => 1,
        ]);

        return [app(BookingService::class)->create(User::factory()->create(), $event, [
            ['ticket_type_id' => $ticket->id, 'quantity' => 1],
        ]), $ticket];
    }
}
