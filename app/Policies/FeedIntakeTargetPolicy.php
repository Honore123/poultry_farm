<?php

namespace App\Policies;

use App\Models\FeedIntakeTarget;
use App\Models\User;

class FeedIntakeTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_feed_intake_targets');
    }

    public function view(User $user, FeedIntakeTarget $feedIntakeTarget): bool
    {
        return $user->can('view_feed_intake_targets');
    }

    public function create(User $user): bool
    {
        return $user->can('create_feed_intake_targets');
    }

    public function update(User $user, FeedIntakeTarget $feedIntakeTarget): bool
    {
        return $user->can('edit_feed_intake_targets');
    }

    public function delete(User $user, FeedIntakeTarget $feedIntakeTarget): bool
    {
        return $user->can('delete_feed_intake_targets');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_feed_intake_targets');
    }
}

