<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLot extends Model
{
    protected $fillable = [
        'item_id',
        'supplier_id',
        'lot_code',
        'expiry',
        'qty_on_hand',
        'uom',
    ];

    protected $casts = [
        'expiry' => 'date',
        'qty_on_hand' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'lot_id');
    }
}

