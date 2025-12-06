<?php

namespace App\Policies;

use App\Models\MortalityLog;
use App\Models\User;

class MortalityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_mortality_logs');
    }

    public function view(User $user, MortalityLog $mortalityLog): bool
    {
        return $user->can('view_mortality_logs');
    }

    public function create(User $user): bool
    {
        return $user->can('create_mortality_logs');
    }

    public function update(User $user, MortalityLog $mortalityLog): bool
    {
        if (!$user->can('edit_mortality_logs')) {
            return false;
        }

        // Staff can only edit same-day entries
        if ($user->hasRole('staff')) {
            return $mortalityLog->date->isToday();
        }

        return true;
    }

    public function delete(User $user, MortalityLog $mortalityLog): bool
    {
        if (!$user->can('delete_mortality_logs')) {
            return false;
        }

        // Staff cannot delete any entries
        if ($user->hasRole('staff')) {
            return false;
        }

        return true;
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_mortality_logs') && !$user->hasRole('staff');
    }
}

