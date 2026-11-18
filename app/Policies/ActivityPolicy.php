<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSg() || $user->isDirector();
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->canAccessDirection($activity->direction_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isDirector();
    }

    public function update(User $user, Activity $activity): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isDirector() && $user->canAccessDirection($activity->direction_id);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }

    public function restore(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }
}
