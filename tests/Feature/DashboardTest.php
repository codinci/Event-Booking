<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_view_dashboard(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Organizer/Dashboard')
                ->has('upcomingEvents')
                ->has('totalRevenue')
                ->has('pendingRefunds')
                ->has('recentBookings')
            );
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('pendingRefunds')
                ->has('reviewRefunds')
                ->has('totalPlatformFees')
                ->has('totalEvents')
                ->has('totalUsers')
                ->has('stuckPayments')
            );
    }

    public function test_regular_user_cannot_view_organizer_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_organizer_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect();
    }

    public function test_regular_user_cannot_view_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_organizer_cannot_view_admin_dashboard(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
