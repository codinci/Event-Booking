<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizerRequestController extends Controller
{
    public function index(): Response
    {
        $pendingRequests = User::whereNotNull('organizer_requested_at')
            ->whereNull('organizer_rejected_at')
            ->where('role', UserRole::User)
            ->select(['id', 'name', 'email', 'organizer_requested_at'])
            ->latest('organizer_requested_at')
            ->get();

        return Inertia::render('Admin/OrganizerRequests/Index', [
            'requests' => $pendingRequests,
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        if ($user->role !== UserRole::User || ! $user->hasRequestedOrganizerRole()) {
            abort(422, 'This user does not have a pending organizer request.');
        }

        $name = $user->name;
        $user->forceFill([
            'role' => UserRole::Organizer,
            'organizer_requested_at' => null,
        ])->save();

        return redirect()
            ->route('admin.organizer-requests.index')
            ->with('success', "{$name} has been approved as an organizer.");
    }

    public function reject(User $user): RedirectResponse
    {
        if ($user->role !== UserRole::User || ! $user->hasRequestedOrganizerRole()) {
            abort(422, 'This user does not have a pending organizer request.');
        }

        $name = $user->name;
        $user->forceFill([
            'organizer_rejected_at' => now(),
        ])->save();

        return redirect()
            ->route('admin.organizer-requests.index')
            ->with('success', "{$name}'s organizer request has been rejected.");
    }
}
