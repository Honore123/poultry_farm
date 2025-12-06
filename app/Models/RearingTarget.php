<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RearingTarget extends Model
{
    protected $fillable = [
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
