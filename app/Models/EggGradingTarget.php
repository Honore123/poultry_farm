<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantOrTemplate;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EggGradingTarget extends Model
{
    use LogsActivity;
    use BelongsToTenantOrTemplate;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Egg Grading Target {$eventName}");
    }

    protected $fillable = [
        'tenant_id',
        'week',
        'avg_egg_weight_g',
        'pct_small',
        'pct_medium',
        'pct_large',
        'pct_xl',
    ];
}
