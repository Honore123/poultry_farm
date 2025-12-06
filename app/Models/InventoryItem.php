<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
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

