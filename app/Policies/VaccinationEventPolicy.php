<?php

namespace App\Policies;

use App\Models\VaccinationEvent;
use App\Models\User;

class VaccinationEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_vaccination_events');
    }

    public function view(User $user, VaccinationEvent $vaccinationEvent): bool
    {
        return $user->can('view_vaccination_events');
    }

    public function create(User $user): bool
    {
        return $user->can('create_vaccination_events');
    }

    public function update(User $user, VaccinationEvent $vaccinationEvent): bool
    {
        if (!$user->can('edit_vaccination_events')) {
            return false;
        }

        // Staff can only edit same-day entries
        if ($user->hasRole('staff')) {
            return $vaccinationEvent->date->isToday();
        }

        return true;
    }

    public function delete(User $user, VaccinationEvent $vaccinationEvent): bool
    {
        if (!$user->can('delete_vaccination_events')) {
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
        return $user->can('delete_vaccination_events') && !$user->hasRole('staff');
    }
}

