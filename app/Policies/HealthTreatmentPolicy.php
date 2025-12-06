<?php

namespace App\Policies;

use App\Models\HealthTreatment;
use App\Models\User;

class HealthTreatmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_health_treatments');
    }

    public function view(User $user, HealthTreatment $healthTreatment): bool
    {
        return $user->can('view_health_treatments');
    }

    public function create(User $user): bool
    {
        return $user->can('create_health_treatments');
    }

    public function update(User $user, HealthTreatment $healthTreatment): bool
    {
        if (!$user->can('edit_health_treatments')) {
            return false;
        }

        // Staff can only edit same-day entries
        if ($user->hasRole('staff')) {
            return $healthTreatment->date->isToday();
        }

        return true;
    }

    public function delete(User $user, HealthTreatment $healthTreatment): bool
    {
        if (!$user->can('delete_health_treatments')) {
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
        return $user->can('delete_health_treatments') && !$user->hasRole('staff');
    }
}

