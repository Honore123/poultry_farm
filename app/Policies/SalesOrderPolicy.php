<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_sales_orders');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('view_sales_orders');
    }

    public function create(User $user): bool
    {
        return $user->can('create_sales_orders');
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('edit_sales_orders');
    }

    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('delete_sales_orders');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_sales_orders');
    }
}

