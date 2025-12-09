<?php

namespace App\Filament\Pages;

use App\Models\EmployeeSalary;
use App\Models\SalaryPayment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MySalary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'My Salary';

    protected static ?string $navigationGroup = 'Account';

    protected static ?int $navigationSort = 200;

    protected static string $view = 'filament.pages.my-salary';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        // Only show to non-admin users who have a salary record
        if ($user?->hasRole('admin')) {
            return false;
        }

        return EmployeeSalary::where('user_id', $user?->id)->exists();
    }

    public function getEmployeeSalary(): ?EmployeeSalary
    {
        return EmployeeSalary::with('payments')
            ->where('user_id', Auth::id())
            ->first();
    }

    public function getPaymentHistory(): array
    {
        $employeeSalary = $this->getEmployeeSalary();
        
        if (!$employeeSalary) {
            return [];
        }

        return $employeeSalary->payments()
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_date' => $payment->payment_date->format('M d, Y'),
                    'payment_period' => $payment->payment_period,
                    'base_salary' => number_format($payment->base_salary, 0) . ' RWF',
                    'bonus' => number_format($payment->bonus, 0) . ' RWF',
                    'deductions' => number_format($payment->deductions, 0) . ' RWF',
                    'net_amount' => number_format($payment->net_amount, 0) . ' RWF',
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'reference' => $payment->reference,
                ];
            })
            ->toArray();
    }

    public function getSalaryStats(): array
    {
        $employeeSalary = $this->getEmployeeSalary();
        
        if (!$employeeSalary) {
            return [
                'current_salary' => 0,
                'total_earned' => 0,
                'total_payments' => 0,
                'this_year' => 0,
                'last_payment_date' => null,
            ];
        }

        $thisYear = Carbon::now()->year;
        
        return [
            'current_salary' => $employeeSalary->salary_amount,
            'total_earned' => $employeeSalary->payments()
                ->where('status', 'paid')
                ->sum('net_amount'),
            'total_payments' => $employeeSalary->payments()
                ->where('status', 'paid')
                ->count(),
            'this_year' => $employeeSalary->payments()
                ->where('status', 'paid')
                ->whereYear('payment_date', $thisYear)
                ->sum('net_amount'),
            'last_payment_date' => $employeeSalary->payments()
                ->where('status', 'paid')
                ->latest('payment_date')
                ->first()?->payment_date?->format('M d, Y'),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'employeeSalary' => $this->getEmployeeSalary(),
            'paymentHistory' => $this->getPaymentHistory(),
            'stats' => $this->getSalaryStats(),
        ];
    }
}

