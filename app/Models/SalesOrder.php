<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesOrder extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Sales Order {$eventName}");
    }

    protected $fillable = [
        'customer_id',
        'order_date',
        'status',
        'payment_status',
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

    public function payments(): HasMany
    {
        return $this->hasMany(SalesOrderPayment::class);
    }

    /**
     * Calculate total amount for this order
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->items->sum(fn ($item) => $item->qty * $item->unit_price);
    }

    /**
     * Calculate total amount paid for this order
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments->sum('amount');
    }

    /**
     * Calculate remaining amount to be paid
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    /**
     * Update payment status based on payments
     */
    public function updatePaymentStatus(): void
    {
        $totalAmount = $this->total_amount;
        $totalPaid = $this->total_paid;

        if ($totalPaid <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($totalPaid >= $totalAmount) {
            $this->payment_status = 'paid';
        } else {
            $this->payment_status = 'partial';
        }

        $this->saveQuietly();
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
     * Get net stock adjustment from all egg stock adjustments
     */
    public static function getStockAdjustment(): int
    {
        return EggStockAdjustment::getNetAdjustment();
    }

    /**
     * Get available eggs (sellable - sold + stock adjustments)
     */
    public static function getAvailableEggs(): int
    {
        $sellable = self::getTotalSellableEggs();
        $sold = self::getTotalEggsSold();
        $adjustment = self::getStockAdjustment();

        return $sellable - $sold + $adjustment;
    }

    /**
     * Get available eggs breakdown for display
     */
    public static function getAvailableEggsBreakdown(): array
    {
        $sellable = self::getTotalSellableEggs();
        $sold = self::getTotalEggsSold();
        $adjustment = self::getStockAdjustment();
        $available = $sellable - $sold + $adjustment;

        return [
            'sellable' => $sellable,
            'sold' => $sold,
            'adjustment' => $adjustment,
            'available' => $available,
        ];
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

