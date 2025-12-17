<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    public const EGGS_PER_TRAY = 30;

    protected $fillable = [
        'sales_order_id',
        'product',
        'qty',
        'uom',
        'unit_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'qty' => 'integer',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Check if this item is an egg product
     */
    public function isEggProduct(): bool
    {
        $product = strtolower($this->product);
        return str_contains($product, 'egg');
    }

    /**
     * Calculate total individual eggs for this item
     */
    public function getEggCountAttribute(): int
    {
        if (!$this->isEggProduct()) {
            return 0;
        }

        $uom = strtolower($this->uom);
        
        if ($uom === 'tray' || $uom === 'trays') {
            return $this->qty * self::EGGS_PER_TRAY;
        }
        
        // For pieces, eggs, or any other unit, treat as individual eggs
        return $this->qty;
    }
}

