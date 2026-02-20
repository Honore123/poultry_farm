<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesOrderPayment extends TenantModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Sales Order Payment {$eventName}");
    }

    protected $fillable = [
        'sales_order_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // Update the sales order payment status after saving a payment
        static::saved(function ($payment) {
            $payment->salesOrder->updatePaymentStatus();
        });

        // Update the sales order payment status after deleting a payment
        static::deleted(function ($payment) {
            $payment->salesOrder->updatePaymentStatus();
        });
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}

