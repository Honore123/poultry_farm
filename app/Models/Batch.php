<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Batch extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Batch {$eventName}");
    }

    protected $fillable = [
        'farm_id',
        'house_id',
        'code',
        'breed',
        'source',
        'placement_date',
        'placement_qty',
        'status',
    ];

    protected $casts = [
        'placement_date' => 'date',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function dailyProductions(): HasMany
    {
        return $this->hasMany(DailyProduction::class);
    }

    public function dailyFeedIntakes(): HasMany
    {
        return $this->hasMany(DailyFeedIntake::class);
    }

    public function dailyWaterUsages(): HasMany
    {
        return $this->hasMany(DailyWaterUsage::class);
    }

    public function weightSamples(): HasMany
    {
        return $this->hasMany(WeightSample::class);
    }

    public function mortalityLogs(): HasMany
    {
        return $this->hasMany(MortalityLog::class);
    }

    public function vaccinationEvents(): HasMany
    {
        return $this->hasMany(VaccinationEvent::class);
    }

    public function healthTreatments(): HasMany
    {
        return $this->hasMany(HealthTreatment::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}

