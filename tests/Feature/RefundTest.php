<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundReason;
use App\Enums\RefundStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Event;
use App\Models\PaymentAttempt;
use App\Models\Refund;
use App\Models\TicketType;
use App\Models\User;
use App\Payments\BookingPayments;
use App\Payments\RefundRequests;
use App\services\BookingService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    private const B2C_URL = 'https://sandbox.safaricom.co.ke/mpesa/b2c/v3/paymentrequest';

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.mailers.payment_sandbox' => ['transport' => 'array']]);
        $this->withoutVite();
    }

    public function test_customer_cancels_paid_booking_releases_tickets_and_opens_fee_deducted_refund(): void
    {
        [$booking, $ticket] = $this->paidBooking(price: '1000.00');

        $this->actingAs($booking->user)->post(route('bookings.cancel', $booking))
            ->assertRedirect(route('bookings.show', $booking));
        $this->post(route('bookings.cancel', $booking))->assertSessionHasErrors('booking');

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame(PaymentStatus::RefundPending, $booking->payment_status);
        $this->assertEquals(1, $ticket->fresh()->available_quantity);
        $this->assertDatabaseCount('refunds', 1);
        $this->assertDatabaseHas('refunds', [
            'booking_id' => $booking->id,
            'reason' => 'customer_cancelled',
            'status' => 'awaiting_approval',
            'amount' => 80000,
            'phone' => '+254700000001',
        ]);
    }

    public function test_customer_cannot_cancel_inside_cutoff_someone_elses_or_an_unpaid_booking(): void
    {
        [$booking] = $this->paidBooking(startsIn: now()->addHours(23));
        $this->actingAs($booking->user)->post(route('bookings.cancel', $booking))->assertSessionHasErrors('booking');

        [$other] = $this->paidBooking();
        $this->actingAs(User::factory()->create())->post(route('bookings.cancel', $other))->assertForbidden();

        $unpaid = $this->reserve()[0];
        $this->actingAs($unpaid->user)->post(route('bookings.cancel', $unpaid))->assertSessionHasErrors('booking');

        $this->assertDatabaseCount('refunds', 0);
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_cancellation_refund_rounds_down_to_whole_shillings(): void
    {
        [$booking] = $this->paidBooking(price: '13.00');
        $attempt = PaymentAttempt::where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame(1040, (int) $attempt->organizer_share);
        $this->assertSame(1000, app(RefundRequests::class)->amount($attempt, RefundReason::CustomerCancelled));
        $this->assertSame(1300, app(RefundRequests::class)->amount($attempt, RefundReason::LatePayment));
    }

    public function test_organizer_cancelling_event_refunds_paid_bookings_and_releases_reservations(): void
    {
        [$paid, $ticket] = $this->paidBooking(quantity: 2);
        $event = $paid->event;
        $pending = app(BookingService::class)->create(User::factory()->create(), $event, [
            ['ticket_type_id' => $ticket->id, 'quantity' => 1],
        ]);

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('organizer.events.cancel', $event))->assertForbidden();

        $this->actingAs($event->organizer)->post(route('organizer.events.cancel', $event))
            ->assertRedirect(route('organizer.events.index'))
            ->assertSessionHas('success', 'Event cancelled. 1 paid booking will be refunded after admin approval.');
        $this->post(route('organizer.events.cancel', $event))->assertRedirect();

        $this->assertSame(EventStatus::Cancelled, $event->fresh()->status);
        $this->assertSame(BookingStatus::Cancelled, $paid->fresh()->status);
        $this->assertSame(BookingStatus::Cancelled, $pending->fresh()->status);
        $this->assertSame(PaymentStatus::Failed, $pending->fresh()->payment_status);
        $this->assertEquals(2, $ticket->fresh()->available_quantity);
        $this->assertDatabaseCount('refunds', 1);
        $this->assertDatabaseHas('refunds', ['booking_id' => $paid->id, 'reason' => 'event_cancelled', 'amount' => 80000]);
    }

    public function test_only_admins_can_see_and_act_on_refunds(): void
    {
        $refund = $this->openRefund();
        $customer = $refund->booking->user;

        foreach ([$customer, $refund->booking->event->organizer] as $user) {
            $this->actingAs($user)->get(route('admin.refunds.index'))->assertForbidden();
            $this->post(route('admin.refunds.approve', $refund))->assertForbidden();
            $this->post(route('admin.refunds.reject', $refund), ['reason' => 'No'])->assertForbidden();
            $this->post(route('admin.refunds.resolve', $refund), ['outcome' => 'completed'])->assertForbidden();
        }

        $this->actingAs($this->admin())->get(route('admin.refunds.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Refunds/Index')
                ->where('counts.awaiting', 1)
                ->where('refunds.data.0.amount', 800));
        $this->assertSame(RefundStatus::AwaitingApproval, $refund->fresh()->status);
    }

    public function test_approved_fake_refund_completes_once_on_the_next_reconcile(): void
    {
        $refund = $this->openRefund();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.refunds.approve', $refund))->assertSessionHasNoErrors();
        $this->post(route('admin.refunds.approve', $refund))->assertSessionHasErrors('refund');
        $this->assertSame($admin->id, $refund->fresh()->approved_by);

        Http::fake();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();
        Http::assertNothingSent();

        $this->assertSame(RefundStatus::Completed, $refund->fresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $refund->booking->fresh()->payment_status);
        $this->assertSame('refunded', $refund->paymentAttempt->fresh()->refund_status);
    }

    public function test_rejecting_a_refund_requires_a_reason_and_keeps_the_money(): void
    {
        $refund = $this->openRefund();

        $this->actingAs($this->admin())->post(route('admin.refunds.reject', $refund))->assertSessionHasErrors('reason');
        $this->post(route('admin.refunds.reject', $refund), ['reason' => 'Duplicate request'])->assertSessionHasNoErrors();
        $this->post(route('admin.refunds.approve', $refund))->assertSessionHasErrors('refund');

        $refund->refresh();
        $this->assertSame(RefundStatus::Rejected, $refund->status);
        $this->assertSame('Duplicate request', $refund->rejection_reason);
        $this->assertSame(PaymentStatus::Paid, $refund->booking->fresh()->payment_status);
    }

    public function test_daraja_refund_is_sent_once_and_completed_by_a_matching_result_callback(): void
    {
        $refund = $this->openRefund(gateway: 'daraja');
        $this->configureDaraja();
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
            self::B2C_URL => Http::response([
                'ConversationID' => 'AG_1',
                'OriginatorConversationID' => $refund->reference,
                'ResponseCode' => '0',
                'ResponseDescription' => 'Accept the service request successfully.',
            ]),
        ]);

        $this->actingAs($this->admin())->post(route('admin.refunds.approve', $refund));
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();

        Http::assertSentCount(2); // One token request and one payout.
        Http::assertSent(fn ($request) => $request->url() === self::B2C_URL
            && $request['OriginatorConversationID'] === $refund->reference
            && $request['InitiatorName'] === 'testapi'
            && $request['SecurityCredential'] === 'encrypted-credential'
            && $request['CommandID'] === 'BusinessPayment'
            && $request['Amount'] === 800
            && $request['PartyA'] === '600000'
            && $request['PartyB'] === '254700000001'
            && $request['ResultURL'] === 'https://sandbox.example.test'.route('payments.daraja.b2c.result', $refund->reference, false)
            && $request['QueueTimeOutURL'] === 'https://sandbox.example.test'.route('payments.daraja.b2c.timeout', $refund->reference, false));
        $this->assertSame(RefundStatus::Sent, $refund->fresh()->status);
        $this->assertSame('AG_1', $refund->fresh()->conversation_id);

        $this->post(route('payments.daraja.b2c.result', $refund->reference), $this->b2cResult('someone-else', 0))->assertNoContent();
        $this->assertSame(RefundStatus::Sent, $refund->fresh()->status);

        $this->post(route('payments.daraja.b2c.result', $refund->reference), $this->b2cResult($refund->reference, 0))->assertNoContent();
        $this->post(route('payments.daraja.b2c.result', $refund->reference), $this->b2cResult($refund->reference, 2001))->assertNoContent();

        $refund->refresh();
        $this->assertSame(RefundStatus::Completed, $refund->status);
        $this->assertSame('TXN123', $refund->transaction_id);
        $this->assertSame(PaymentStatus::Refunded, $refund->booking->fresh()->payment_status);
    }

    public function test_forged_result_callback_without_the_daraja_conversation_id_is_ignored(): void
    {
        $refund = $this->sentDarajaRefund();

        // Everything an outsider can read from the callback URL, but not Daraja's ConversationID.
        foreach ([['ConversationID' => 'AG_guess'], ['ConversationID' => null], ['ConversationID' => '']] as $forged) {
            $this->post(route('payments.daraja.b2c.result', $refund->reference), [
                'Result' => array_merge($this->b2cResult($refund->reference, 0)['Result'], $forged),
            ])->assertNoContent();
        }
        $this->post(route('payments.daraja.b2c.result', $refund->reference), [
            'Result' => array_diff_key($this->b2cResult($refund->reference, 0)['Result'], ['ConversationID' => true]),
        ])->assertNoContent();

        $this->assertSame(RefundStatus::Sent, $refund->fresh()->status);
        $this->assertSame(PaymentStatus::RefundPending, $refund->booking->fresh()->payment_status);
    }

    public function test_failed_daraja_result_can_be_approved_again_and_old_callbacks_no_longer_apply(): void
    {
        $refund = $this->sentDarajaRefund();

        $this->post(route('payments.daraja.b2c.result', $refund->reference), $this->b2cResult($refund->reference, 2001, 'The initiator information is invalid.'))
            ->assertNoContent();

        $refund->refresh();
        $this->assertSame(RefundStatus::Failed, $refund->status);
        $this->assertSame('2001', $refund->result_code);
        $this->actingAs($this->admin())->post(route('admin.refunds.approve', $refund))->assertSessionHasNoErrors();
        $refund->refresh();
        $this->assertSame(RefundStatus::Approved, $refund->status);
        $this->assertNull($refund->conversation_id);

        // The resend is accepted under a new ConversationID; a replay about the first attempt is ignored.
        $refund->update(['status' => RefundStatus::Sent, 'conversation_id' => 'AG_2', 'sent_at' => now()]);
        $this->post(route('payments.daraja.b2c.result', $refund->reference), $this->b2cResult($refund->reference, 0))->assertNoContent();
        $this->assertSame(RefundStatus::Sent, $refund->fresh()->status);
    }

    public function test_refund_without_a_result_moves_to_review_and_can_be_resolved(): void
    {
        $refund = $this->sentDarajaRefund();

        $this->travel(14)->minutes();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame(RefundStatus::Sent, $refund->fresh()->status);

        $this->travel(2)->minutes();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame(RefundStatus::Review, $refund->fresh()->status);

        $this->actingAs($this->admin())->post(route('admin.refunds.approve', $refund))->assertSessionHasErrors('refund');
        $this->post(route('admin.refunds.resolve', $refund), ['outcome' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame(RefundStatus::Completed, $refund->fresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $refund->booking->fresh()->payment_status);
    }

    public function test_connection_error_moves_refund_to_review_without_resending(): void
    {
        $refund = $this->openRefund(gateway: 'daraja');
        $this->configureDaraja();
        $payouts = 0;
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
            self::B2C_URL => function () use (&$payouts) {
                $payouts++;

                throw new ConnectionException('Timed out');
            },
        ]);

        $this->actingAs($this->admin())->post(route('admin.refunds.approve', $refund));
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(RefundStatus::Review, $refund->fresh()->status);
        $this->assertSame(1, $payouts);
    }

    public function test_server_error_from_daraja_moves_refund_to_review_without_resending(): void
    {
        $refund = $this->openRefund(gateway: 'daraja');
        $this->configureDaraja();
        $payouts = 0;
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
            self::B2C_URL => function () use (&$payouts) {
                $payouts++;

                return Http::response(['errorMessage' => 'Internal error'], 500);
            },
        ]);

        $this->actingAs($this->admin())->post(route('admin.refunds.approve', $refund));
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();

        $refund->refresh();
        $this->assertSame(RefundStatus::Review, $refund->status);
        $this->assertSame(1, $payouts);
    }

    public function test_rejected_b2c_request_marks_refund_failed(): void
    {
        $refund = $this->openRefund(gateway: 'daraja');
        $this->configureDaraja();
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
            self::B2C_URL => Http::response(['errorCode' => '400.002.02', 'errorMessage' => 'Bad Request - Invalid PartyA'], 400),
        ]);

        $this->actingAs($this->admin())->post(route('admin.refunds.approve', $refund));
        $this->artisan('payments:reconcile')->assertSuccessful();

        $refund->refresh();
        $this->assertSame(RefundStatus::Failed, $refund->status);
        $this->assertSame('400.002.02', $refund->result_code);
        $this->assertSame('Bad Request - Invalid PartyA', $refund->result_desc);
    }

    public function test_queue_timeout_moves_sent_refund_to_review_and_it_cannot_be_reapproved(): void
    {
        $refund = $this->sentDarajaRefund();

        $this->post(route('payments.daraja.b2c.timeout', $refund->reference))->assertNoContent();

        $this->assertSame(RefundStatus::Review, $refund->fresh()->status);
        $this->actingAs($this->admin())->post(route('admin.refunds.approve', $refund))->assertSessionHasErrors('refund');
        $this->assertSame(RefundStatus::Review, $refund->fresh()->status);
    }

    public function test_unexpected_send_error_moves_refund_to_review_and_other_refunds_still_send(): void
    {
        $broken = $this->openRefund(gateway: 'daraja');
        $other = $this->openRefund();
        $this->configureDaraja();
        $payouts = 0;
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
            // Accepted, but without the ConversationID needed to verify the result.
            self::B2C_URL => function () use (&$payouts) {
                $payouts++;

                return Http::response(['ResponseCode' => '0', 'ResponseDescription' => 'Accepted']);
            },
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.refunds.approve', $broken));
        $this->travel(1)->seconds();
        $this->post(route('admin.refunds.approve', $other));
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();

        $broken->refresh();
        $this->assertSame(RefundStatus::Review, $broken->status);
        $this->assertStringContainsString('ConversationID', (string) $broken->result_desc);
        $this->assertSame(1, $payouts);
        $this->assertSame(RefundStatus::Completed, $other->fresh()->status);
    }

    public function test_refund_interrupted_while_sending_moves_to_review(): void
    {
        $refund = $this->openRefund();
        $refund->update(['status' => RefundStatus::Sending]);

        $this->travel(4)->minutes();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame(RefundStatus::Sending, $refund->fresh()->status);

        $this->travel(2)->minutes();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame(RefundStatus::Review, $refund->fresh()->status);
    }

    public function test_make_admin_command_promotes_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'ops@example.test']);

        $this->artisan('users:make-admin', ['email' => 'ops@example.test'])->assertSuccessful();
        $this->artisan('users:make-admin', ['email' => 'missing@example.test'])->assertFailed();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }

    private function sentDarajaRefund(): Refund
    {
        $refund = $this->openRefund(gateway: 'daraja');
        $refund->update([
            'status' => RefundStatus::Sent,
            'originator_conversation_id' => $refund->reference,
            'conversation_id' => 'AG_1',
            'sent_at' => now(),
        ]);

        return $refund;
    }

    /** A customer-cancelled refund of a KES 1,000 payment, awaiting approval. */
    private function openRefund(string $gateway = 'fake'): Refund
    {
        [$booking] = $this->paidBooking();
        // Receipts were already handled at payment time; only the refund path is under test.
        PaymentAttempt::where('booking_id', $booking->id)->update(['gateway' => $gateway, 'sms_status' => null, 'email_status' => null]);

        $this->actingAs($booking->user)->post(route('bookings.cancel', $booking))->assertSessionHasNoErrors();

        return Refund::where('booking_id', $booking->id)->firstOrFail();
    }

    /** @return array{0: Booking, 1: TicketType} */
    private function paidBooking(string $price = '1000.00', int $quantity = 1, ?CarbonInterface $startsIn = null): array
    {
        [$booking, $ticket] = $this->reserve($price, $quantity, $startsIn);
        app(BookingPayments::class)->start($booking, 'fake', '+254700000001');
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);

        return [$booking->fresh(), $ticket];
    }

    /** @return array{0: Booking, 1: TicketType} */
    private function reserve(string $price = '1000.00', int $quantity = 1, ?CarbonInterface $startsIn = null): array
    {
        $event = Event::factory()->create($startsIn ? ['starts_at' => $startsIn, 'ends_at' => $startsIn->copy()->addHours(3)] : []);
        $ticket = TicketType::factory()->for($event)->create([
            'price' => $price,
            'quantity' => $quantity,
            'available_quantity' => $quantity,
        ]);

        return [app(BookingService::class)->create(User::factory()->create(), $event, [
            ['ticket_type_id' => $ticket->id, 'quantity' => 1],
        ]), $ticket];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function configureDaraja(): void
    {
        config([
            'payments.daraja_callback_url' => 'https://sandbox.example.test',
            'payments.daraja' => ['key' => 'test', 'secret' => 'test', 'shortcode' => '174379', 'passkey' => 'test'],
            'payments.daraja_b2c' => [
                'shortcode' => '600000',
                'initiator_name' => 'testapi',
                'security_credential' => 'encrypted-credential',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function b2cResult(string $originatorConversationId, int $code, string $description = 'The service request is processed successfully.'): array
    {
        return ['Result' => [
            'ResultType' => 0,
            'ResultCode' => $code,
            'ResultDesc' => $description,
            'OriginatorConversationID' => $originatorConversationId,
            'ConversationID' => 'AG_1',
            'TransactionID' => 'TXN123',
        ]];
    }
}
