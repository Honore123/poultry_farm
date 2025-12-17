<?php

namespace App\Filament\Resources\DailyProductionResource\Widgets;

use App\Models\DailyProduction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EggQualityStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Get totals for all-time eggs
        $totals = DailyProduction::query()
            ->select([
                DB::raw('SUM(eggs_total) as total_eggs'),
                DB::raw('SUM(eggs_soft) as soft_eggs'),
                DB::raw('SUM(eggs_cracked) as cracked_eggs'),
            ])
            ->first();

        $totalEggs = (int) ($totals->total_eggs ?? 0);
        $softEggs = (int) ($totals->soft_eggs ?? 0);
        $crackedEggs = (int) ($totals->cracked_eggs ?? 0);

        // Eggs that can be sold (all except soft and cracked)
        $sellableEggs = $totalEggs - $softEggs - $crackedEggs;
        
        // Eggs that cannot be sold (soft + cracked)
        $unsellableEggs = $softEggs + $crackedEggs;

        // Calculate percentages
        $sellablePercentage = $totalEggs > 0 ? round(($sellableEggs / $totalEggs) * 100, 1) : 0;
        $unsellablePercentage = $totalEggs > 0 ? round(($unsellableEggs / $totalEggs) * 100, 1) : 0;

        // Get today's stats for comparison
        $todayTotals = DailyProduction::query()
            ->whereDate('date', today())
            ->select([
                DB::raw('SUM(eggs_total) as total_eggs'),
                DB::raw('SUM(eggs_soft) as soft_eggs'),
                DB::raw('SUM(eggs_cracked) as cracked_eggs'),
            ])
            ->first();

        $todaySellable = (int) ($todayTotals->total_eggs ?? 0) 
            - (int) ($todayTotals->soft_eggs ?? 0) 
            - (int) ($todayTotals->cracked_eggs ?? 0);
        
        $todayUnsellable = (int) ($todayTotals->soft_eggs ?? 0) 
            + (int) ($todayTotals->cracked_eggs ?? 0);

        return [
            Stat::make('Sellable Eggs', number_format($sellableEggs))
                ->description($sellablePercentage . '% of total production')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-default',
                ])
                ->chart($this->getSellableEggsTrend()),

            Stat::make('Unsellable Eggs', number_format($unsellableEggs))
                ->description($unsellablePercentage . '% of total production')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'cursor-default',
                ])
                ->chart($this->getUnsellableEggsTrend()),

            Stat::make('Today\'s Sellable', number_format($todaySellable))
                ->description('Today\'s unsellable: ' . number_format($todayUnsellable))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }

    /**
     * Get sellable eggs trend for the last 7 days
     */
    protected function getSellableEggsTrend(): array
    {
        return DailyProduction::query()
            ->whereBetween('date', [now()->subDays(7), now()])
            ->orderBy('date')
            ->groupBy('date')
            ->pluck(DB::raw('SUM(eggs_total) - SUM(COALESCE(eggs_soft, 0)) - SUM(COALESCE(eggs_cracked, 0))'))
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }

    /**
     * Get unsellable eggs trend for the last 7 days
     */
    protected function getUnsellableEggsTrend(): array
    {
        return DailyProduction::query()
            ->whereBetween('date', [now()->subDays(7), now()])
            ->orderBy('date')
            ->groupBy('date')
            ->pluck(DB::raw('SUM(COALESCE(eggs_soft, 0)) + SUM(COALESCE(eggs_cracked, 0))'))
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }
}

