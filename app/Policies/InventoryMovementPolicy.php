<?php

namespace App\Policies;

use App\Models\InventoryMovement;
use App\Models\User;

class InventoryMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_inventory_movements');
    }

    public function view(User $user, InventoryMovement $inventoryMovement): bool
    {
        return $user->can('view_inventory_movements');
    }

    public function create(User $user): bool
    {
        return $user->can('create_inventory_movements');
    }

    public function update(User $user, InventoryMovement $inventoryMovement): bool
    {
        return $user->can('edit_inventory_movements');
    }

    public function delete(User $user, InventoryMovement $inventoryMovement): bool
    {
        return $user->can('delete_inventory_movements');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_inventory_movements');
    }
}

