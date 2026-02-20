<?php

namespace App\Models;

use App\Mail\SalaryPaymentMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalaryPayment extends TenantModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Salary Payment {$eventName}");
    }

    protected $fillable = [
        'employee_salary_id',
        'expense_id',
        'payment_date',
        'payment_period',
        'payment_type',
        'base_salary',
        'bonus',
        'deductions',
        'net_amount',
        'status',
        'payment_method',
        'reference',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'base_salary' => 'decimal:2',
        'bonus' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate net amount before saving
        static::saving(function ($payment) {
            $payment->net_amount = $payment->base_salary + $payment->bonus - $payment->deductions;
        });

        // Send email notification when payment status is 'paid'
        static::saved(function ($payment) {
            // Only send notification if status is 'paid'
            if ($payment->status !== 'paid') {
                return;
            }

            // Send notification if:
            // 1. This is a new record created with 'paid' status, OR
            // 2. The status was changed to 'paid' from another status
            $shouldSendNotification = $payment->wasRecentlyCreated || $payment->wasChanged('status');
            
            if ($shouldSendNotification) {
                $payment->sendPaymentNotification();
            }
        });
    }

    /**
     * Send payment notification email to employee
     */
    public function sendPaymentNotification(): void
    {
        // Get employee email - try linked user first, then fall back to any stored email
        $employeeSalary = $this->employeeSalary;
        
        if (!$employeeSalary) {
            return;
        }

        $email = null;

        // Check if linked to a user with email
        if ($employeeSalary->user && $employeeSalary->user->email) {
            $email = $employeeSalary->user->email;
        }

        // Only send if we have an email
        if ($email) {
            try {
                Mail::to($email)->send(new SalaryPaymentMail($this));
            } catch (\Exception $e) {
                // Log the error but don't fail the payment save
                \Illuminate\Support\Facades\Log::error('Failed to send salary payment email: ' . $e->getMessage());
            }
        }
    }

    public function employeeSalary(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalary::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Create an expense record for this salary payment
     */
    public function createExpenseRecord(): Expense
    {
        $paymentTypeLabel = match($this->payment_type) {
            'first_half' => ' (1st Half)',
            'second_half' => ' (2nd Half)',
            default => '',
        };

        $expense = Expense::create([
            'date' => $this->payment_date,
            'category' => 'salary',
            'description' => "Salary payment for {$this->employeeSalary->employee_name} - {$this->payment_period}{$paymentTypeLabel}",
            'amount' => $this->net_amount,
        ]);

        $this->update(['expense_id' => $expense->id]);

        return $expense;
    }

    /**
     * Get human-readable payment type label
     */
    public function getPaymentTypeLabelAttribute(): string
    {
        return match($this->payment_type) {
            'first_half' => 'First Half',
            'second_half' => 'Second Half',
            default => 'Full Payment',
        };
    }
}

