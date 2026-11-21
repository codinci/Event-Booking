<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Payments\RefundPayouts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RefundController extends Controller
{
    private const FILTERS = [
        'awaiting' => [RefundStatus::AwaitingApproval, RefundStatus::Failed],
        'review' => [RefundStatus::Review],
        'all' => null,
    ];

    public function index(Request $request): Response
    {
        $filter = array_key_exists($request->query('status'), self::FILTERS) ? $request->query('status') : 'awaiting';

        $refunds = Refund::query()
            ->with(['booking:id,reference,user_id,event_id', 'booking.user:id,name,email', 'booking.event:id,title', 'approver:id,name'])
            ->when(self::FILTERS[$filter], fn ($query, $statuses) => $query->whereIn('status', $statuses))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Refund $refund) => [
                'id' => $refund->id,
                'reason' => $refund->reason->value,
                'status' => $refund->status->value,
                'amount' => $refund->amount / 100,
                'phone' => $refund->phone,
                'booking_reference' => $refund->booking->reference,
                'event_title' => $refund->booking->event->title,
                'customer' => $refund->booking->user->name,
                'customer_email' => $refund->booking->user->email,
                'approver' => $refund->approver?->name,
                'rejection_reason' => $refund->rejection_reason,
                'result_desc' => $refund->result_desc,
                'transaction_id' => $refund->transaction_id,
                'created_at' => $refund->created_at,
                'urls' => [
                    'approve' => route('admin.refunds.approve', $refund),
                    'reject' => route('admin.refunds.reject', $refund),
                    'resolve' => route('admin.refunds.resolve', $refund),
                ],
            ]);

        return Inertia::render('Admin/Refunds/Index', [
            'refunds' => $refunds,
            'filter' => $filter,
            'counts' => [
                'awaiting' => Refund::whereIn('status', self::FILTERS['awaiting'])->count(),
                'review' => Refund::whereIn('status', self::FILTERS['review'])->count(),
            ],
        ]);
    }

    public function approve(Request $request, Refund $refund, RefundPayouts $payouts): RedirectResponse
    {
        return $this->outcome($payouts->approve($refund, $request->user()), 'Refund approved. It will be sent within a minute.');
    }

    public function reject(Request $request, Refund $refund, RefundPayouts $payouts): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        return $this->outcome($payouts->reject($refund, $request->user(), $validated['reason']), 'Refund rejected.');
    }

    public function resolve(Request $request, Refund $refund, RefundPayouts $payouts): RedirectResponse
    {
        $validated = $request->validate(['outcome' => ['required', Rule::in(['completed', 'failed'])]]);

        return $this->outcome($payouts->resolve($refund, RefundStatus::from($validated['outcome'])), 'Refund updated.');
    }

    private function outcome(bool $changed, string $message): RedirectResponse
    {
        return $changed
            ? back()->with('success', $message)
            : back()->withErrors(['refund' => 'This refund was already updated. Refresh to see its current status.']);
    }
}
