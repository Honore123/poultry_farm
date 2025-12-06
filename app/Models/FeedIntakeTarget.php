<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedIntakeTarget extends Model
{
    protected $fillable = [
        'min_week',
        'max_week',
        'stage',
        'grams_per_bird_per_day_min',
        'grams_per_bird_per_day_max',
    ];
}

