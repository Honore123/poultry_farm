<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_inventory_items');
    }

    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->can('view_inventory_items');
    }

    public function create(User $user): bool
    {
        return $user->can('create_inventory_items');
    }

    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->can('edit_inventory_items');
    }

    public function delete(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->can('delete_inventory_items');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_inventory_items');
    }
}

