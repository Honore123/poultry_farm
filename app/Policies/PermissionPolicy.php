<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_permissions');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('view_permissions');
    }

    public function create(User $user): bool
    {
        return $user->is_super_admin && $user->can('create_permissions');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->is_super_admin && $user->can('edit_permissions');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->is_super_admin && $user->can('delete_permissions');
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_super_admin && $user->can('delete_permissions');
    }
}
