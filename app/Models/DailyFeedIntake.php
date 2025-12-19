<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DailyFeedIntake extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Daily Feed Intake {$eventName}");
    }

    protected $fillable = [
        'batch_id',
        'date',
        'feed_item_id',
        'inventory_lot_id',
        'kg_given',
    ];

    protected $casts = [
        'date' => 'date',
        'kg_given' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function feedItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'feed_item_id');
    }

    public function inventoryLot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class);
    }
}

