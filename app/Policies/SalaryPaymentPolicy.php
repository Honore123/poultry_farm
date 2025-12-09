<?php

namespace App\Policies;

use App\Models\SalaryPayment;
use App\Models\User;

class SalaryPaymentPolicy
{
    /**
     * Admin can view all, others can only view their own
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('view_salary_payments');
    }

    /**
     * Admin can view all, users can view their own payment records
     */
    public function view(User $user, SalaryPayment $salaryPayment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can view payments for their own salary record
        return $salaryPayment->employeeSalary?->user_id === $user->id;
    }

    /**
     * Only admin can create payment records
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Only admin can update payment records
     */
    public function update(User $user, SalaryPayment $salaryPayment): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Only admin can delete payment records
     */
    public function delete(User $user, SalaryPayment $salaryPayment): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
}

