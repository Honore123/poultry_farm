<?php

namespace App\Policies;

use App\Models\EmployeeSalary;
use App\Models\User;

class EmployeeSalaryPolicy
{
    /**
     * Admin can view all, others can only view their own
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('view_employee_salaries');
    }

    /**
     * Admin can view all, users can view their own salary record
     */
    public function view(User $user, EmployeeSalary $employeeSalary): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can view their own salary record
        return $employeeSalary->user_id === $user->id;
    }

    /**
     * Only admin can create salary records
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Only admin can update salary records
     */
    public function update(User $user, EmployeeSalary $employeeSalary): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Only admin can delete salary records
     */
    public function delete(User $user, EmployeeSalary $employeeSalary): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
}

