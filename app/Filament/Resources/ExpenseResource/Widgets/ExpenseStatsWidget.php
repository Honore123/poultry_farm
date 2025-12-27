<?php

namespace App\Filament\Resources\ExpenseResource\Widgets;

use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class ExpenseStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    public ?string $fromDate = null;
    public ?string $untilDate = null;

    #[On('updateExpenseWidgetFilters')]
    public function updateFilters(?string $fromDate = null, ?string $untilDate = null): void
    {
        $this->fromDate = $fromDate;
        $this->untilDate = $untilDate;
    }

    protected function getStats(): array
    {
        $query = Expense::query();

        // Apply date range filter
        if ($this->fromDate) {
            $query->whereDate('date', '>=', $this->fromDate);
        }
        if ($this->untilDate) {
            $query->whereDate('date', '<=', $this->untilDate);
        }

        $totalExpenses = (clone $query)->sum('amount');
        $expenseCount = (clone $query)->count();
        
        // Get breakdown by category
        $categoryBreakdown = (clone $query)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        $topCategories = $categoryBreakdown->map(function ($item) {
            $label = Expense::CATEGORIES[$item->category] ?? ucfirst($item->category);
            return $label . ': RWF ' . number_format($item->total, 0);
        })->join(' | ');

        // Average expense per entry
        $avgExpense = $expenseCount > 0 ? $totalExpenses / $expenseCount : 0;

        // Build period description
        $periodDesc = 'All time';
        if ($this->fromDate && $this->untilDate) {
            $periodDesc = \Carbon\Carbon::parse($this->fromDate)->format('M d') . ' - ' . \Carbon\Carbon::parse($this->untilDate)->format('M d, Y');
        } elseif ($this->fromDate) {
            $periodDesc = 'From ' . \Carbon\Carbon::parse($this->fromDate)->format('M d, Y');
        } elseif ($this->untilDate) {
            $periodDesc = 'Until ' . \Carbon\Carbon::parse($this->untilDate)->format('M d, Y');
        }

        return [
            Stat::make('Total Expenses', 'RWF ' . number_format($totalExpenses, 0))
                ->description($periodDesc)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Number of Expenses', number_format($expenseCount))
                ->description('Expense entries')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Average per Entry', 'RWF ' . number_format($avgExpense, 0))
                ->description($topCategories ?: 'No category data')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('info'),
        ];
    }
}

