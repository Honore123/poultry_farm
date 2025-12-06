<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EggGradingTarget extends Model
{
    protected $fillable = [
        'week',
        'avg_egg_weight_g',
        'pct_small',
        'pct_medium',
        'pct_large',
        'pct_xl',
    ];
}
