<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MortalityLog extends Model
{
    protected $fillable = [
        'batch_id',
        'date',
        'count',
        'cause',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}

