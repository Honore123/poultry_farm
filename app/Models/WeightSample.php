<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightSample extends Model
{
    protected $fillable = [
        'batch_id',
        'date',
        'sample_size',
        'avg_weight_g',
    ];

    protected $casts = [
        'date' => 'date',
        'avg_weight_g' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}

