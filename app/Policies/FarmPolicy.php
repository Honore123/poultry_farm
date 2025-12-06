<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;

class FarmPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_farms');
    }

    public function view(User $user, Farm $farm): bool
    {
        return $user->can('view_farms');
    }

    public function create(User $user): bool
    {
        return $user->can('create_farms');
    }

    public function update(User $user, Farm $farm): bool
    {
        return $user->can('edit_farms');
    }

    public function delete(User $user, Farm $farm): bool
    {
        return $user->can('delete_farms');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_farms');
    }
}

