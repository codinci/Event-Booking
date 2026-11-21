<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\PaymentAttempt;
use App\Models\Refund;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $pendingRefunds = Refund::query()
            ->where('status', 'awaiting_approval')
            ->count();

        $reviewRefunds = Refund::query()
            ->where('status', 'review')
            ->count();

        $totalPlatformFees = PaymentAttempt::query()
            ->where('status', 'completed')
            ->get()
            ->sum(fn ($attempt) => $attempt->amount - $attempt->organizer_share);

        $totalEvents = Event::count();
        $totalUsers = User::count();

        $stuckPayments = PaymentAttempt::query()
            ->whereIn('status', ['pending', 'review'])
            ->where('next_check_at', '<', now())
            ->count();

        return Inertia::render('Admin/Dashboard', [
            'pendingRefunds' => $pendingRefunds,
            'reviewRefunds' => $reviewRefunds,
            'totalPlatformFees' => $totalPlatformFees / 100,
            'totalEvents' => $totalEvents,
            'totalUsers' => $totalUsers,
            'stuckPayments' => $stuckPayments,
        ]);
    }
}
