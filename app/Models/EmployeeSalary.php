<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeSalary extends TenantModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Employee Salary {$eventName}");
    }

    protected $fillable = [
        'user_id',
        'employee_name',
        'employee_phone',
        'position',
        'salary_amount',
        'payment_day',
        'payment_schedule',
        'first_half_payment_day',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'salary_amount' => 'decimal:2',
        'payment_day' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function getIsLinkedToUserAttribute(): bool
    {
        return $this->user_id !== null;
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name . ' (' . $this->employee_name . ')';
        }
        return $this->employee_name;
    }
}

