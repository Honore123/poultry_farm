<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthTreatment extends Model
{
    protected $fillable = [
        'batch_id',
        'date',
        'product',
        'reason',
        'dosage',
        'dosage_per_liter_ml',
        'duration_days',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}

