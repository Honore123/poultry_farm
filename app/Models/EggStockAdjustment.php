<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EggStockAdjustment extends TenantModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Egg Stock Adjustment {$eventName}");
    }

    protected $fillable = [
        'date',
        'adjustment_type',
        'quantity',
        'physical_count',
        'system_count',
        'reason',
        'notes',
        'adjusted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'physical_count' => 'integer',
        'system_count' => 'integer',
    ];

    /**
     * Common adjustment reasons
     */
    public const REASONS = [
        'physical_count_variance' => 'Physical Count Variance',
        'breakage' => 'Breakage/Damage',
        'theft_loss' => 'Theft/Loss',
        'expired' => 'Expired/Spoiled',
        'recording_error' => 'Recording Error Correction',
        'found_eggs' => 'Found Eggs (previously unrecorded)',
        'quality_downgrade' => 'Quality Downgrade',
        'other' => 'Other',
    ];

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Get total adjustment value (positive for increases, negative for decreases)
     */
    public function getAdjustmentValueAttribute(): int
    {
        return $this->adjustment_type === 'increase' 
            ? $this->quantity 
            : -$this->quantity;
    }

    /**
     * Get net adjustment from all stock adjustments
     */
    public static function getNetAdjustment(): int
    {
        $increases = self::where('adjustment_type', 'increase')->sum('quantity');
        $decreases = self::where('adjustment_type', 'decrease')->sum('quantity');

        return $increases - $decreases;
    }

    /**
     * Get net adjustment up to a specific date
     */
    public static function getNetAdjustmentUntil(\Carbon\Carbon $date): int
    {
        $increases = self::where('adjustment_type', 'increase')
            ->whereDate('date', '<=', $date)
            ->sum('quantity');
        $decreases = self::where('adjustment_type', 'decrease')
            ->whereDate('date', '<=', $date)
            ->sum('quantity');

        return $increases - $decreases;
    }

    /**
     * Get summary stats for adjustments
     */
    public static function getAdjustmentStats(): array
    {
        $totalIncreases = self::where('adjustment_type', 'increase')->sum('quantity');
        $totalDecreases = self::where('adjustment_type', 'decrease')->sum('quantity');
        $adjustmentCount = self::count();
        $netAdjustment = $totalIncreases - $totalDecreases;

        return [
            'total_increases' => (int) $totalIncreases,
            'total_decreases' => (int) $totalDecreases,
            'net_adjustment' => $netAdjustment,
            'adjustment_count' => $adjustmentCount,
        ];
    }
}

