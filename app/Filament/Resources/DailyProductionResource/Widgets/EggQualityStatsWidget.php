<?php

namespace App\Filament\Resources\DailyProductionResource\Widgets;

use App\Models\DailyProduction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class EggQualityStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    public ?string $fromDate = null;
    public ?string $untilDate = null;

    #[On('updateDailyProductionWidgetFilters')]
    public function updateFilters(?string $fromDate = null, ?string $untilDate = null): void
    {
        $this->fromDate = $fromDate;
        $this->untilDate = $untilDate;
    }

    protected function getStats(): array
    {
        // Build the base query with date filters
        $query = DailyProduction::query();
        
        if ($this->fromDate) {
            $query->whereDate('date', '>=', $this->fromDate);
        }
        if ($this->untilDate) {
            $query->whereDate('date', '<=', $this->untilDate);
        }

        // Get totals for filtered period
        $totals = (clone $query)
            ->select([
                DB::raw('SUM(eggs_total) as total_eggs'),
                DB::raw('SUM(eggs_soft) as soft_eggs'),
                DB::raw('SUM(eggs_cracked) as cracked_eggs'),
                DB::raw('SUM(eggs_dirty) as dirty_eggs'),
                DB::raw('SUM(eggs_small) as small_eggs'),
                DB::raw('AVG(egg_weight_avg_g) as avg_weight'),
            ])
            ->first();

        $totalEggs = (int) ($totals->total_eggs ?? 0);
        $softEggs = (int) ($totals->soft_eggs ?? 0);
        $crackedEggs = (int) ($totals->cracked_eggs ?? 0);
        $dirtyEggs = (int) ($totals->dirty_eggs ?? 0);
        $smallEggs = (int) ($totals->small_eggs ?? 0);
        $avgWeight = $totals->avg_weight ? round($totals->avg_weight, 1) : null;

        // Eggs that can be sold (all except soft and cracked)
        $sellableEggs = $totalEggs - $softEggs - $crackedEggs;
        
        // Eggs that cannot be sold (soft + cracked)
        $unsellableEggs = $softEggs + $crackedEggs;

        // Calculate percentages
        $sellablePercentage = $totalEggs > 0 ? round(($sellableEggs / $totalEggs) * 100, 1) : 0;
        $unsellablePercentage = $totalEggs > 0 ? round(($unsellableEggs / $totalEggs) * 100, 1) : 0;

        // Build period description
        $periodDesc = 'All time';
        if ($this->fromDate && $this->untilDate) {
            $periodDesc = \Carbon\Carbon::parse($this->fromDate)->format('M d') . ' - ' . \Carbon\Carbon::parse($this->untilDate)->format('M d, Y');
        } elseif ($this->fromDate) {
            $periodDesc = 'From ' . \Carbon\Carbon::parse($this->fromDate)->format('M d, Y');
        } elseif ($this->untilDate) {
            $periodDesc = 'Until ' . \Carbon\Carbon::parse($this->untilDate)->format('M d, Y');
        }

        // Calculate trays (30 eggs per tray)
        $sellableTrays = number_format($sellableEggs / 30, 0);

        return [
            Stat::make('Total Eggs', number_format($totalEggs))
                ->description($periodDesc)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->chart($this->getTotalEggsTrend()),

            Stat::make('Sellable Eggs', number_format($sellableEggs))
                ->description($sellablePercentage . '% of total (' . $sellableTrays . ' trays)')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($this->getSellableEggsTrend()),

            Stat::make('Unsellable Eggs', number_format($unsellableEggs))
                ->description($unsellablePercentage . '% - Cracked: ' . number_format($crackedEggs) . ' | Soft: ' . number_format($softEggs))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart($this->getUnsellableEggsTrend()),

            Stat::make('Avg Egg Weight', $avgWeight ? $avgWeight . 'g' : 'N/A')
                ->description('Dirty: ' . number_format($dirtyEggs) . ' | Small: ' . number_format($smallEggs))
                ->descriptionIcon('heroicon-m-scale')
                ->color('info'),
        ];
    }

    /**
     * Get total eggs trend for the filtered period (or last 7 days)
     */
    protected function getTotalEggsTrend(): array
    {
        $query = DailyProduction::query();
        
        if ($this->fromDate && $this->untilDate) {
            $query->whereBetween('date', [$this->fromDate, $this->untilDate]);
        } else {
            $query->whereBetween('date', [now()->subDays(7), now()]);
        }

        return $query
            ->orderBy('date')
            ->groupBy('date')
            ->pluck(DB::raw('SUM(eggs_total)'))
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }

    /**
     * Get sellable eggs trend for the filtered period (or last 7 days)
     */
    protected function getSellableEggsTrend(): array
    {
        $query = DailyProduction::query();
        
        if ($this->fromDate && $this->untilDate) {
            $query->whereBetween('date', [$this->fromDate, $this->untilDate]);
        } else {
            $query->whereBetween('date', [now()->subDays(7), now()]);
        }

        return $query
            ->orderBy('date')
            ->groupBy('date')
            ->pluck(DB::raw('SUM(eggs_total) - SUM(COALESCE(eggs_soft, 0)) - SUM(COALESCE(eggs_cracked, 0))'))
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }

    /**
     * Get unsellable eggs trend for the filtered period (or last 7 days)
     */
    protected function getUnsellableEggsTrend(): array
    {
        $query = DailyProduction::query();
        
        if ($this->fromDate && $this->untilDate) {
            $query->whereBetween('date', [$this->fromDate, $this->untilDate]);
        } else {
            $query->whereBetween('date', [now()->subDays(7), now()]);
        }

        return $query
            ->orderBy('date')
            ->groupBy('date')
            ->pluck(DB::raw('SUM(COALESCE(eggs_soft, 0)) + SUM(COALESCE(eggs_cracked, 0))'))
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }
}
