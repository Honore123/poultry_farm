<?php

namespace App\Policies;

use App\Models\RearingTarget;
use App\Models\User;

class RearingTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_rearing_targets');
    }

    public function view(User $user, RearingTarget $target): bool
    {
        return $user->can('view_rearing_targets');
    }

    public function create(User $user): bool
    {
        return $user->can('create_rearing_targets');
    }

    public function update(User $user, RearingTarget $target): bool
    {
        return $user->can('edit_rearing_targets');
    }

    public function delete(User $user, RearingTarget $target): bool
    {
        return $user->can('delete_rearing_targets');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_rearing_targets');
    }
}
