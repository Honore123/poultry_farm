<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeedIntakeTarget extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Feed Intake Target {$eventName}");
    }

    protected $fillable = [
        'min_week',
        'max_week',
        'stage',
        'grams_per_bird_per_day_min',
        'grams_per_bird_per_day_max',
    ];
}

