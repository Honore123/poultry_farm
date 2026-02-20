<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin || $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        return $user->hasRole('admin') || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->is_super_admin || $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public function delete(User $user, User $model): bool
    {
        // Can't delete yourself
        if ($user->id === $model->id) {
            return false;
        }
        if ($user->is_super_admin) {
            return true;
        }

        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_super_admin || $user->hasRole('admin');
    }
}
