<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantOrTemplate;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeedIntakeTarget extends Model
{
    use LogsActivity;
    use BelongsToTenantOrTemplate;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Feed Intake Target {$eventName}");
    }

    protected $fillable = [
        'tenant_id',
        'min_week',
        'max_week',
        'stage',
        'grams_per_bird_per_day_min',
        'grams_per_bird_per_day_max',
    ];
}
