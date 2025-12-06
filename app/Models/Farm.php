<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $fillable = [
        'name',
        'location',
    ];

    public function houses(): HasMany
    {
        return $this->hasMany(House::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}

