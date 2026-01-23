<?php

namespace App\Policies;

use App\Models\ProductionTarget;
use App\Models\User;

class ProductionTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_production_targets');
    }

    public function view(User $user, ProductionTarget $target): bool
    {
        return $user->can('view_production_targets');
    }

    public function create(User $user): bool
    {
        return $user->can('create_production_targets');
    }

    public function update(User $user, ProductionTarget $target): bool
    {
        return $user->can('edit_production_targets');
    }

    public function delete(User $user, ProductionTarget $target): bool
    {
        return $user->can('delete_production_targets');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_production_targets');
    }
}
