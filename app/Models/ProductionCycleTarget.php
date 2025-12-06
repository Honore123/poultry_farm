<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionCycleTarget extends Model
{
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
