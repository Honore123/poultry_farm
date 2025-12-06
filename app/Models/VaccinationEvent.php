<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaccinationEvent extends Model
{
    protected $fillable = [
        'batch_id',
        'date',
        'vaccine',
        'method',
        'administered_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}

