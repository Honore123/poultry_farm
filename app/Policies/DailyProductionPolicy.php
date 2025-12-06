<?php

namespace App\Policies;

use App\Models\DailyProduction;
use App\Models\User;

class DailyProductionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_daily_productions');
    }

    public function view(User $user, DailyProduction $dailyProduction): bool
    {
        return $user->can('view_daily_productions');
    }

    public function create(User $user): bool
    {
        return $user->can('create_daily_productions');
    }

    public function update(User $user, DailyProduction $dailyProduction): bool
    {
        if (!$user->can('edit_daily_productions')) {
            return false;
        }

        // Staff can only edit same-day entries
        if ($user->hasRole('staff')) {
            return $dailyProduction->date->isToday();
        }

        return true;
    }

    public function delete(User $user, DailyProduction $dailyProduction): bool
    {
        if (!$user->can('delete_daily_productions')) {
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
        return $user->can('delete_daily_productions') && !$user->hasRole('staff');
    }
}

