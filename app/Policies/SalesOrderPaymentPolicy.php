<?php

namespace App\Policies;

use App\Models\SalesOrderPayment;
use App\Models\User;

class SalesOrderPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_sales_orders');
    }

    public function view(User $user, SalesOrderPayment $salesOrderPayment): bool
    {
        return $user->can('view_sales_orders');
    }

    public function create(User $user): bool
    {
        return $user->can('edit_sales_orders');
    }

    public function update(User $user, SalesOrderPayment $salesOrderPayment): bool
    {
        return $user->can('edit_sales_orders');
    }

    public function delete(User $user, SalesOrderPayment $salesOrderPayment): bool
    {
        return $user->can('delete_sales_orders');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_sales_orders');
    }
}

