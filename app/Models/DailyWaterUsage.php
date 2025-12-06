<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyWaterUsage extends Model
{
    protected $fillable = [
        'batch_id',
        'date',
        'liters_used',
    ];

    protected $casts = [
        'date' => 'date',
        'liters_used' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}

