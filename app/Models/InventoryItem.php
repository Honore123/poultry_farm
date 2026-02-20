<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryItem extends TenantModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Inventory Item {$eventName}");
    }

    protected $fillable = [
        'sku',
        'name',
        'category',
        'uom',
    ];

    public function inventoryLots(): HasMany
    {
        return $this->hasMany(InventoryLot::class, 'item_id');
    }

    public function dailyFeedIntakes(): HasMany
    {
        return $this->hasMany(DailyFeedIntake::class, 'feed_item_id');
    }
}

