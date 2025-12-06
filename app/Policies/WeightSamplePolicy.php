<?php

namespace App\Policies;

use App\Models\WeightSample;
use App\Models\User;

class WeightSamplePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_weight_samples');
    }

    public function view(User $user, WeightSample $weightSample): bool
    {
        return $user->can('view_weight_samples');
    }

    public function create(User $user): bool
    {
        return $user->can('create_weight_samples');
    }

    public function update(User $user, WeightSample $weightSample): bool
    {
        if (!$user->can('edit_weight_samples')) {
            return false;
        }

        // Staff can only edit same-day entries
        if ($user->hasRole('staff')) {
            return $weightSample->date->isToday();
        }

        return true;
    }

    public function delete(User $user, WeightSample $weightSample): bool
    {
        if (!$user->can('delete_weight_samples')) {
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
        return $user->can('delete_weight_samples') && !$user->hasRole('staff');
    }
}

