<?php

namespace App\Policies;

use App\Models\DailyWaterUsage;
use App\Models\User;

class DailyWaterUsagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_daily_water_usages');
    }

    public function view(User $user, DailyWaterUsage $dailyWaterUsage): bool
    {
        return $user->can('view_daily_water_usages');
    }

    public function create(User $user): bool
    {
        return $user->can('create_daily_water_usages');
    }

    public function update(User $user, DailyWaterUsage $dailyWaterUsage): bool
    {
        if (!$user->can('edit_daily_water_usages')) {
            return false;
        }

        // Staff can only edit same-day entries
        if ($user->hasRole('staff')) {
            return $dailyWaterUsage->date->isToday();
        }

        return true;
    }

    public function delete(User $user, DailyWaterUsage $dailyWaterUsage): bool
    {
        if (!$user->can('delete_daily_water_usages')) {
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
        return $user->can('delete_daily_water_usages') && !$user->hasRole('staff');
    }
}

