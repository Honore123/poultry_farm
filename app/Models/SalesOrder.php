<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SalesOrder extends Model
{
    protected $fillable = [
        'customer_id',
        'order_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    /**
     * Calculate total eggs in this order
     */
    public function getTotalEggsAttribute(): int
    {
        return $this->items->sum(fn ($item) => $item->egg_count);
    }

    /**
     * Get total eggs sold from confirmed/delivered orders
     */
    public static function getTotalEggsSold(): int
    {
        $orders = self::whereIn('status', ['confirmed', 'delivered'])
            ->with('items')
            ->get();

        return $orders->sum(fn ($order) => $order->total_eggs);
    }

    /**
     * Get total sellable eggs from production (all eggs - soft - cracked)
     */
    public static function getTotalSellableEggs(): int
    {
        $totals = DailyProduction::query()
            ->select([
                DB::raw('SUM(eggs_total) as total_eggs'),
                DB::raw('SUM(COALESCE(eggs_soft, 0)) as soft_eggs'),
                DB::raw('SUM(COALESCE(eggs_cracked, 0)) as cracked_eggs'),
            ])
            ->first();

        $total = (int) ($totals->total_eggs ?? 0);
        $soft = (int) ($totals->soft_eggs ?? 0);
        $cracked = (int) ($totals->cracked_eggs ?? 0);

        return $total - $soft - $cracked;
    }

    /**
     * Get available eggs (sellable - sold)
     */
    public static function getAvailableEggs(): int
    {
        return self::getTotalSellableEggs() - self::getTotalEggsSold();
    }

    /**
     * Check if there are enough eggs available for this order
     */
    public function hasEnoughEggsAvailable(): bool
    {
        $available = self::getAvailableEggs();
        
        // If this order is already confirmed/delivered, add its eggs back to available for comparison
        if (in_array($this->status, ['confirmed', 'delivered'])) {
            $available += $this->total_eggs;
        }

        return $available >= $this->total_eggs;
    }
}

