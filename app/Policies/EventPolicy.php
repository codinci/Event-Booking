<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Organizer || $user->role === UserRole::Admin;
    }

    public function update(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id
            && ($user->role === UserRole::Organizer || $user->role === UserRole::Admin);
    }

    public function delete(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id
            && ($user->role === UserRole::Organizer || $user->role === UserRole::Admin);
    }

    public function publish(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id
            && ($user->role === UserRole::Organizer || $user->role === UserRole::Admin);
    }
}
