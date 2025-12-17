<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesOrderItem extends Model
{
    use LogsActivity;

    public const EGGS_PER_TRAY = 30;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Sales Order Item {$eventName}");
    }

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

