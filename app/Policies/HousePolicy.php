<?php

namespace App\Policies;

use App\Models\House;
use App\Models\User;

class HousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_houses');
    }

    public function view(User $user, House $house): bool
    {
        return $user->can('view_houses');
    }

    public function create(User $user): bool
    {
        return $user->can('create_houses');
    }

    public function update(User $user, House $house): bool
    {
        return $user->can('edit_houses');
    }

    public function delete(User $user, House $house): bool
    {
        return $user->can('delete_houses');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_houses');
    }
}

