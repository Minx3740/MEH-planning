<?php

namespace App\Policies;

use App\Models\Direction;
use App\Models\User;

class DirectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSg() || $user->isDirector();
    }

    public function view(User $user, Direction $direction): bool
    {
        return $user->canAccessDirection($direction);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Direction $direction): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isDirector() && $user->canAccessDirection($direction);
    }

    public function delete(User $user, Direction $direction): bool
    {
        return $user->isAdmin();
    }
}
