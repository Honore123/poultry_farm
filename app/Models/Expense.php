<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Expense {$eventName}");
    }

    protected $fillable = [
        'date',
        'category',
        'description',
        'amount',
        'total_kgs',
        'farm_id',
        'house_id',
        'batch_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'total_kgs' => 'decimal:2',
    ];

    /**
     * Available expense categories
     */
    public const CATEGORIES = [
        'feed' => 'Feed',
        'labor' => 'Labor',
        'salary' => 'Salary',
        'utilities' => 'Utilities',
        'veterinary' => 'Veterinary',
        'maintenance' => 'Maintenance',
        'transport' => 'Transport',
        'packaging' => 'Packaging',
        'other' => 'Other',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the salary payment that created this expense (if any)
     */
    public function salaryPayment(): HasOne
    {
        return $this->hasOne(SalaryPayment::class);
    }

    /**
     * Check if this expense is from a salary payment
     */
    public function getIsSalaryExpenseAttribute(): bool
    {
        return $this->category === 'salary' && $this->salaryPayment()->exists();
    }
}

