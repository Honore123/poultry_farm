<?php

namespace App\Policies;

use App\Models\SalesOrderPayment;
use App\Models\User;

class SalesOrderPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_sales_order_payments');
    }

    public function view(User $user, SalesOrderPayment $payment): bool
    {
        return $user->can('view_sales_order_payments');
    }

    public function create(User $user): bool
    {
        return $user->can('create_sales_order_payments');
    }

    public function update(User $user, SalesOrderPayment $payment): bool
    {
        return $user->can('edit_sales_order_payments');
    }

    public function delete(User $user, SalesOrderPayment $payment): bool
    {
        return $user->can('delete_sales_order_payments');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_sales_order_payments');
    }
}
