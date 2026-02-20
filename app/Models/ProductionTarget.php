<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantOrTemplate;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductionTarget extends Model
{
    use LogsActivity;
    use BelongsToTenantOrTemplate;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Production Target {$eventName}");
    }

    protected $fillable = [
        'tenant_id',
        'week',
        'hen_day_production_pct',
        'avg_egg_weight_g',
        'egg_mass_per_day_g',
        'feed_intake_per_day_g',
        'fcr_week',
        'cum_eggs_hh',
        'cum_egg_mass_kg',
        'cum_feed_kg',
        'cum_fcr',
        'livability_pct',
        'body_weight_g',
    ];

}
