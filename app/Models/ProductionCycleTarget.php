<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductionCycleTarget extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Production Cycle Target {$eventName}");
    }

    protected $fillable = [
        'cycle_end_week',
        'livability_pct',
        'eggs_hh',
        'egg_mass_kg',
        'avg_feed_intake_g_day',
        'cum_fcr_kg_per_kg',
        'body_weight_g',
    ];
}
