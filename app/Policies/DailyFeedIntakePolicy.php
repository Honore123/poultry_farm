<?php

namespace App\Policies;

use App\Models\DailyFeedIntake;
use App\Models\User;

class DailyFeedIntakePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_daily_feed_intakes');
    }

    public function view(User $user, DailyFeedIntake $dailyFeedIntake): bool
    {
        return $user->can('view_daily_feed_intakes');
    }

    public function create(User $user): bool
    {
        return $user->can('create_daily_feed_intakes');
    }

    public function update(User $user, DailyFeedIntake $dailyFeedIntake): bool
    {
        if (!$user->can('edit_daily_feed_intakes')) {
            return false;
        }

        // Staff can only edit same-day entries
        if ($user->hasRole('staff')) {
            return $dailyFeedIntake->date->isToday();
        }

        return true;
    }

    public function delete(User $user, DailyFeedIntake $dailyFeedIntake): bool
    {
        if (!$user->can('delete_daily_feed_intakes')) {
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
        return $user->can('delete_daily_feed_intakes') && !$user->hasRole('staff');
    }
}

