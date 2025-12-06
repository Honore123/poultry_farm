<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_expenses');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->can('view_expenses');
    }

    public function create(User $user): bool
    {
        return $user->can('create_expenses');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->can('edit_expenses');
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->can('delete_expenses');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_expenses');
    }
}

