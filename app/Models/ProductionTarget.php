<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionTarget extends Model
{
    protected $fillable = [
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
