<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;

class BatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_batches');
    }

    public function view(User $user, Batch $batch): bool
    {
        return $user->can('view_batches');
    }

    public function create(User $user): bool
    {
        return $user->can('create_batches');
    }

    public function update(User $user, Batch $batch): bool
    {
        return $user->can('edit_batches');
    }

    public function delete(User $user, Batch $batch): bool
    {
        return $user->can('delete_batches');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_batches');
    }
}

