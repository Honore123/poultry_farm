<?php

namespace App\Policies;

use App\Models\EggStockAdjustment;
use App\Models\User;

class EggStockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_egg_stock_adjustments');
    }

    public function view(User $user, EggStockAdjustment $eggStockAdjustment): bool
    {
        return $user->can('view_egg_stock_adjustments');
    }

    public function create(User $user): bool
    {
        return $user->can('create_egg_stock_adjustments');
    }

    public function update(User $user, EggStockAdjustment $eggStockAdjustment): bool
    {
        return $user->can('edit_egg_stock_adjustments');
    }

    public function delete(User $user, EggStockAdjustment $eggStockAdjustment): bool
    {
        return $user->can('delete_egg_stock_adjustments');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_egg_stock_adjustments');
    }
}

