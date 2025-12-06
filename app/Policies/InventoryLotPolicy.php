<?php

namespace App\Policies;

use App\Models\InventoryLot;
use App\Models\User;

class InventoryLotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_inventory_lots');
    }

    public function view(User $user, InventoryLot $inventoryLot): bool
    {
        return $user->can('view_inventory_lots');
    }

    public function create(User $user): bool
    {
        return $user->can('create_inventory_lots');
    }

    public function update(User $user, InventoryLot $inventoryLot): bool
    {
        return $user->can('edit_inventory_lots');
    }

    public function delete(User $user, InventoryLot $inventoryLot): bool
    {
        return $user->can('delete_inventory_lots');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_inventory_lots');
    }
}

