<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\DailyProduction;
use App\Models\DailyFeedIntake;
use App\Models\MortalityLog;
use App\Models\ProductionTarget;
use App\Models\RearingTarget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ProductionStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('view_dashboard') ?? false;
    }

    protected function getStats(): array
    {
        // Today's laying rate from view
        $todayLayingRate = DB::table('v_daily_laying_rate')
            ->whereDate('date', today())
            ->avg('laying_rate_pct');

        // 7-day average laying rate
        $weekLayingRate = DB::table('v_daily_laying_rate')
            ->whereBetween('date', [now()->subDays(7), now()])
            ->avg('laying_rate_pct');

        // Today's total eggs
        $todayEggs = DailyProduction::whereDate('date', today())->sum('eggs_total');

        // 7-day total eggs
        $weekEggs = DailyProduction::whereBetween('date', [now()->subDays(7), now()])->sum('eggs_total');

        // Yesterday comparison for trend
        $yesterdayEggs = DailyProduction::whereDate('date', today()->subDay())->sum('eggs_total');
        $eggTrend = $yesterdayEggs > 0 
            ? round((($todayEggs - $yesterdayEggs) / $yesterdayEggs) * 100, 1) 
            : 0;

        // Calculate weighted average target for all active batches in production
        $targetInfo = $this->getWeightedTargetProduction();

        return [
            Stat::make('Today\'s Eggs', number_format($todayEggs))
                ->description($eggTrend >= 0 ? "+{$eggTrend}% vs yesterday" : "{$eggTrend}% vs yesterday")
                ->descriptionIcon($eggTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($eggTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getEggTrend()),

            Stat::make('Laying Rate Today', $todayLayingRate ? number_format($todayLayingRate, 1) . '%' : 'N/A')
                ->description($targetInfo['target_pct'] 
                    ? 'Target: ' . number_format($targetInfo['target_pct'], 1) . '%' 
                    : '7-day avg: ' . ($weekLayingRate ? number_format($weekLayingRate, 1) . '%' : 'N/A'))
                ->descriptionIcon($targetInfo['target_pct'] 
                    ? ($todayLayingRate >= $targetInfo['target_pct'] ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                    : 'heroicon-m-chart-bar')
                ->color($this->getLayingRateColor($todayLayingRate, $targetInfo['target_pct'])),

            Stat::make('This Week\'s Eggs', number_format($weekEggs))
                ->description($targetInfo['batches_in_production'] > 0 
                    ? $targetInfo['batches_in_production'] . ' batches in production'
                    : 'Last 7 days total')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }

    /**
     * Get weighted average production target across all active batches
     */
    protected function getWeightedTargetProduction(): array
    {
        $activeBatches = Batch::whereIn('status', ['laying'])->get();
        
        if ($activeBatches->isEmpty()) {
            return ['target_pct' => null, 'batches_in_production' => 0];
        }

        $totalBirds = 0;
        $weightedTarget = 0;

        foreach ($activeBatches as $batch) {
            $week = (int) $batch->placement_date->diffInWeeks(now());
            $birdsAlive = $batch->placement_qty - MortalityLog::where('batch_id', $batch->id)->sum('count');
            
            if ($week >= 18) {
                $target = ProductionTarget::where('week', $week)->first();
                if ($target && $target->hen_day_production_pct && $birdsAlive > 0) {
                    $weightedTarget += $target->hen_day_production_pct * $birdsAlive;
                    $totalBirds += $birdsAlive;
                }
            }
        }

        return [
            'target_pct' => $totalBirds > 0 ? $weightedTarget / $totalBirds : null,
            'batches_in_production' => $activeBatches->count(),
        ];
    }

    protected function getLayingRateColor(?float $actual, ?float $target): string
    {
        if ($actual === null) return 'gray';
        
        if ($target !== null) {
            // Compare to target
            if ($actual >= $target) return 'success';
            if ($actual >= $target * 0.9) return 'warning';
            return 'danger';
        }
        
        // Fallback to absolute values
        if ($actual >= 80) return 'success';
        if ($actual >= 60) return 'warning';
        return 'danger';
    }

    protected function getEggTrend(): array
    {
        return DailyProduction::query()
            ->whereBetween('date', [now()->subDays(7), now()])
            ->orderBy('date')
            ->groupBy('date')
            ->pluck(DB::raw('SUM(eggs_total)'))
            ->toArray();
    }
}

