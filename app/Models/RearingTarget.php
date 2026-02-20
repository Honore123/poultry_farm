<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantOrTemplate;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RearingTarget extends Model
{
    use LogsActivity;
    use BelongsToTenantOrTemplate;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Rearing Target {$eventName}");
    }

    protected $fillable = [
        'tenant_id',
        'week',
        'age_days_from',
        'age_days_to',
        'daily_feed_min_g',
        'daily_feed_max_g',
        'cumulative_feed_min_g',
        'cumulative_feed_max_g',
        'body_weight_min_g',
        'body_weight_max_g',
    ];
}
