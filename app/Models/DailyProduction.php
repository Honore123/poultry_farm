<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyProduction extends Model
{
    protected $fillable = [
        'batch_id',
        'date',
        'eggs_total',
        'eggs_soft',
        'eggs_cracked',
        'eggs_dirty',
        'egg_weight_avg_g',
        'lighting_hours',
    ];

    protected $casts = [
        'date' => 'date',
        'egg_weight_avg_g' => 'decimal:2',
        'lighting_hours' => 'decimal:1',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}

