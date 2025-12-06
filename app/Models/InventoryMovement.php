<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'lot_id',
        'ts',
        'direction',
        'qty',
        'reference',
        'batch_id',
    ];

    protected $casts = [
        'ts' => 'datetime',
        'qty' => 'decimal:3',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'lot_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}

