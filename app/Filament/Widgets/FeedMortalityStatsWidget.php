<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\DailyFeedIntake;
use App\Models\MortalityLog;
use App\Models\ProductionTarget;
use App\Models\RearingTarget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FeedMortalityStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->can('view_dashboard') ?? false;
    }

    protected function getStats(): array
    {
        // Feed per egg from view (last 7 days)
        $feedPerEgg = DB::table('v_feed_per_egg')
            ->whereBetween('date', [now()->subDays(7), now()])
            ->whereNotNull('kg_per_egg')
            ->avg('kg_per_egg');

        // Today's feed consumption
        $todayFeed = DailyFeedIntake::whereDate('date', today())->sum('kg_given');

        // 7-day feed total
        $weekFeed = DailyFeedIntake::whereBetween('date', [now()->subDays(7), now()])->sum('kg_given');

        // Calculate feed per bird per day (actual)
        $feedTargetInfo = $this->getWeightedFeedTarget();

        // Mortality stats
        $weekMortality = MortalityLog::whereBetween('date', [now()->subDays(7), now()])->sum('count');
        $todayMortality = MortalityLog::whereDate('date', today())->sum('count');

        // Calculate livability (birds alive / birds placed)
        $activeBatches = Batch::whereIn('status', ['brooding', 'growing', 'laying'])->get();
        $totalBirdsPlaced = $activeBatches->sum('placement_qty');
        $totalDeaths = MortalityLog::whereIn('batch_id', $activeBatches->pluck('id'))->sum('count');
        $birdsAlive = $totalBirdsPlaced - $totalDeaths;
        
        $livability = $totalBirdsPlaced > 0 
            ? round(($birdsAlive / $totalBirdsPlaced) * 100, 2) 
            : 0;
        
        $mortalityRate = $totalBirdsPlaced > 0 
            ? round(($totalDeaths / $totalBirdsPlaced) * 100, 2) 
            : 0;

        // Get target livability
        $livabilityTarget = $this->getWeightedLivabilityTarget();

        return [
            Stat::make('Feed/Bird/Day', $feedTargetInfo['actual_g'] ? number_format($feedTargetInfo['actual_g'], 0) . 'g' : 'N/A')
                ->description($feedTargetInfo['target_g'] 
                    ? 'Target: ' . number_format($feedTargetInfo['target_g'], 0) . 'g'
                    : 'Week total: ' . number_format($weekFeed, 1) . ' kg')
                ->descriptionIcon($this->getFeedStatusIcon($feedTargetInfo))
                ->color($this->getFeedStatusColor($feedTargetInfo))
                ->chart($this->getFeedTrend()),

            Stat::make('Feed per Egg (7d)', $feedPerEgg ? number_format($feedPerEgg * 1000, 0) . 'g' : 'N/A')
                ->description('Lower is better efficiency')
                ->descriptionIcon('heroicon-m-scale')
                ->color($feedPerEgg && $feedPerEgg < 0.15 ? 'success' : 'warning'),

            Stat::make('Livability', $livability . '%')
                ->description($livabilityTarget 
                    ? 'Target: ' . number_format($livabilityTarget, 1) . '%'
                    : "Deaths: {$totalDeaths} | Week: {$weekMortality}")
                ->descriptionIcon($livabilityTarget && $livability >= $livabilityTarget 
                    ? 'heroicon-m-check-circle' 
                    : 'heroicon-m-exclamation-triangle')
                ->color($this->getLivabilityColor($livability, $livabilityTarget)),
        ];
    }

    /**
     * Calculate weighted average feed target and actual for all active batches
     */
    protected function getWeightedFeedTarget(): array
    {
        $activeBatches = Batch::whereIn('status', ['brooding', 'growing', 'laying'])->get();
        
        if ($activeBatches->isEmpty()) {
            return ['actual_g' => null, 'target_g' => null, 'within_range' => null];
        }

        $totalBirds = 0;
        $weightedTarget = 0;
        $totalFeedToday = 0;
        $batchesWithFeed = 0;

        foreach ($activeBatches as $batch) {
            $week = $batch->placement_date->diffInWeeks(now());
            $birdsAlive = $batch->placement_qty - MortalityLog::where('batch_id', $batch->id)->sum('count');
            
            if ($birdsAlive <= 0) continue;

            // Get today's feed for this batch
            $batchFeed = DailyFeedIntake::where('batch_id', $batch->id)
                ->whereDate('date', today())
                ->sum('kg_given');
            
            if ($batchFeed > 0) {
                $totalFeedToday += $batchFeed * 1000; // Convert to grams
                $batchesWithFeed += $birdsAlive;
            }

            // Get target for this batch
            if ($week < 18) {
                $target = RearingTarget::where('week', $week)->first();
                if ($target) {
                    $avgTarget = ($target->daily_feed_min_g + $target->daily_feed_max_g) / 2;
                    $weightedTarget += $avgTarget * $birdsAlive;
                    $totalBirds += $birdsAlive;
                }
            } else {
                $target = ProductionTarget::where('week', $week)->first();
                if ($target && $target->feed_intake_per_day_g) {
                    $weightedTarget += $target->feed_intake_per_day_g * $birdsAlive;
                    $totalBirds += $birdsAlive;
                }
            }
        }

        $actualG = $batchesWithFeed > 0 ? $totalFeedToday / $batchesWithFeed : null;
        $targetG = $totalBirds > 0 ? $weightedTarget / $totalBirds : null;

        $withinRange = null;
        if ($actualG !== null && $targetG !== null) {
            $withinRange = $actualG >= ($targetG * 0.9) && $actualG <= ($targetG * 1.1);
        }

        return [
            'actual_g' => $actualG,
            'target_g' => $targetG,
            'within_range' => $withinRange,
        ];
    }

    /**
     * Get weighted average livability target
     */
    protected function getWeightedLivabilityTarget(): ?float
    {
        $activeBatches = Batch::whereIn('status', ['brooding', 'growing', 'laying'])->get();
        
        if ($activeBatches->isEmpty()) {
            return null;
        }

        $totalBirds = 0;
        $weightedTarget = 0;

        foreach ($activeBatches as $batch) {
            $week = $batch->placement_date->diffInWeeks(now());
            $birdsPlaced = $batch->placement_qty;
            
            if ($week >= 18) {
                $target = ProductionTarget::where('week', $week)->first();
                if ($target && $target->livability_pct) {
                    $weightedTarget += $target->livability_pct * $birdsPlaced;
                    $totalBirds += $birdsPlaced;
                }
            }
        }

        return $totalBirds > 0 ? $weightedTarget / $totalBirds : null;
    }

    protected function getFeedStatusIcon(array $feedInfo): string
    {
        if ($feedInfo['within_range'] === null) return 'heroicon-m-beaker';
        return $feedInfo['within_range'] ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle';
    }

    protected function getFeedStatusColor(array $feedInfo): string
    {
        if ($feedInfo['within_range'] === null) return 'info';
        return $feedInfo['within_range'] ? 'success' : 'warning';
    }

    protected function getLivabilityColor(float $actual, ?float $target): string
    {
        if ($target !== null) {
            if ($actual >= $target) return 'success';
            if ($actual >= $target - 2) return 'warning';
            return 'danger';
        }
        
        // Fallback
        if ($actual >= 97) return 'success';
        if ($actual >= 95) return 'warning';
        return 'danger';
    }

    protected function getFeedTrend(): array
    {
        return DailyFeedIntake::query()
            ->whereBetween('date', [now()->subDays(7), now()])
            ->orderBy('date')
            ->groupBy('date')
            ->pluck(DB::raw('SUM(kg_given)'))
            ->toArray();
    }
}

