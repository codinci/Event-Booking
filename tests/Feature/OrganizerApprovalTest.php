<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrganizerApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_created_with_organizer_request_has_correct_fields(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'organizer_requested_at' => now(),
        ]);

        $this->assertSame(UserRole::User, $user->role);
        $this->assertNotNull($user->organizer_requested_at);
        $this->assertNull($user->organizer_rejected_at);
        $this->assertTrue($user->hasRequestedOrganizerRole());
    }

    public function test_user_created_without_organizer_request_has_correct_fields(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);

        $this->assertSame(UserRole::User, $user->role);
        $this->assertNull($user->organizer_requested_at);
        $this->assertNull($user->organizer_rejected_at);
        $this->assertFalse($user->hasRequestedOrganizerRole());
    }

    public function test_admin_can_view_pending_organizer_requests(): void
    {
        $admin = User::factory()->admin()->create();

        $pendingUser = User::factory()->create([
            'role' => UserRole::User,
            'organizer_requested_at' => now(),
        ]);

        $approvedOrganizer = User::factory()
            ->organizer()
            ->create();

        $this->actingAs($admin)
            ->get(route('admin.organizer-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/OrganizerRequests/Index')
                ->has('requests', 1)
                ->where('requests.0.id', $pendingUser->id)
            );
    }

    public function test_admin_can_approve_organizer_request(): void
    {
        $admin = User::factory()->admin()->create();

        $pendingUser = User::factory()->create([
            'role' => UserRole::User,
            'organizer_requested_at' => now(),
            'name' => 'John Organizer',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.organizer-requests.approve', $pendingUser))
            ->assertRedirect(route('admin.organizer-requests.index'))
            ->assertSessionHas('success', 'John Organizer has been approved as an organizer.');

        $approvedUser = $pendingUser->fresh();
        $this->assertSame(UserRole::Organizer, $approvedUser->role);
        $this->assertNull($approvedUser->organizer_requested_at);
    }

    public function test_admin_can_reject_organizer_request(): void
    {
        $admin = User::factory()->admin()->create();

        $pendingUser = User::factory()->create([
            'role' => UserRole::User,
            'organizer_requested_at' => now(),
            'name' => 'John Organizer',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.organizer-requests.reject', $pendingUser))
            ->assertRedirect(route('admin.organizer-requests.index'))
            ->assertSessionHas('success', "John Organizer's organizer request has been rejected.");

        $rejectedUser = $pendingUser->fresh();
        $this->assertSame(UserRole::User, $rejectedUser->role);
        $this->assertNotNull($rejectedUser->organizer_rejected_at);
    }

    public function test_cannot_approve_non_pending_request(): void
    {
        $admin = User::factory()->admin()->create();

        $organizer = User::factory()->organizer()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizer-requests.approve', $organizer))
            ->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $organizer->id,
            'role' => UserRole::Organizer->value,
        ]);
    }

    public function test_cannot_reject_non_pending_request(): void
    {
        $admin = User::factory()->admin()->create();

        $organizer = User::factory()->organizer()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizer-requests.reject', $organizer))
            ->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $organizer->id,
            'role' => UserRole::Organizer->value,
        ]);
    }

    public function test_guest_cannot_view_organizer_requests(): void
    {
        $this->get(route('admin.organizer-requests.index'))
            ->assertRedirect();
    }

    public function test_regular_user_cannot_view_organizer_requests(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.organizer-requests.index'))
            ->assertForbidden();
    }

    public function test_organizer_cannot_view_organizer_requests(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('admin.organizer-requests.index'))
            ->assertForbidden();
    }

    public function test_abilities_object_includes_correct_values_for_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('auth.abilities.canManageEvents', false)
            ->where('auth.abilities.isAdmin', false)
            ->where('auth.abilities.canApproveOrganizers', false)
            ->where('auth.abilities.isPendingOrganizer', false)
        );
    }

    public function test_abilities_object_includes_correct_values_for_organizer(): void
    {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer)->get(route('home'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('auth.abilities.canManageEvents', true)
            ->where('auth.abilities.isAdmin', false)
            ->where('auth.abilities.canApproveOrganizers', false)
            ->where('auth.abilities.isPendingOrganizer', false)
        );
    }

    public function test_abilities_object_includes_correct_values_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('auth.abilities.canManageEvents', true)
            ->where('auth.abilities.isAdmin', true)
            ->where('auth.abilities.canApproveOrganizers', true)
            ->where('auth.abilities.isPendingOrganizer', false)
        );
    }

    public function test_abilities_object_includes_correct_values_for_pending_user(): void
    {
        $pendingUser = User::factory()->create([
            'organizer_requested_at' => now(),
        ]);

        $response = $this->actingAs($pendingUser)->get(route('home'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('auth.abilities.canManageEvents', false)
            ->where('auth.abilities.isAdmin', false)
            ->where('auth.abilities.canApproveOrganizers', false)
            ->where('auth.abilities.isPendingOrganizer', true)
        );
    }
}
