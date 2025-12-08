<?php

namespace App\Filament\Resources\ProductionTargetResource\Widgets;

use App\Models\Batch;
use App\Models\DailyFeedIntake;
use App\Models\MortalityLog;
use App\Models\ProductionTarget;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class ProductionStatsWidget extends BaseWidget
{
    public ?int $selectedBatchId = null;

    #[On('production-batch-selected')]
    public function updateBatch(?int $batchId): void
    {
        $this->selectedBatchId = $batchId;
    }

    protected function getStats(): array
    {
        if (!$this->selectedBatchId) {
            return [
                Stat::make('Current Week', 'Select a batch')
                    ->description('Choose a batch to see projections')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray'),
                Stat::make('Target kg/week', '-')
                    ->description('Flock total target')
                    ->icon('heroicon-o-scale')
                    ->color('gray'),
                Stat::make('Feed Consumed', '-')
                    ->description('This week')
                    ->icon('heroicon-o-beaker')
                    ->color('gray'),
                Stat::make('Target HD%', '-')
                    ->description('Hen Day Production')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray'),
            ];
        }

        $batch = Batch::find($this->selectedBatchId);
        if (!$batch) {
            return $this->getEmptyStats();
        }

        // Calculate current week
        $currentWeek = (int) $batch->placement_date->diffInWeeks(now());
        
        // Get birds alive
        $totalMortality = MortalityLog::where('batch_id', $this->selectedBatchId)->sum('count');
        $birdsAlive = $batch->placement_qty - $totalMortality;

        // Calculate week start and end dates
        $weekStartDate = $batch->placement_date->copy()->addWeeks($currentWeek);
        $weekEndDate = $weekStartDate->copy()->addDays(6);

        // Get feed consumed this week
        $feedConsumedThisWeek = DailyFeedIntake::where('batch_id', $this->selectedBatchId)
            ->whereBetween('date', [$weekStartDate->format('Y-m-d'), $weekEndDate->format('Y-m-d')])
            ->sum('kg_given');

        // Get target for current week
        $target = ProductionTarget::where('week', $currentWeek)->first();

        if (!$target || $currentWeek < 18) {
            return [
                Stat::make('Current Week', "Week {$currentWeek}")
                    ->description($batch->code . ' - ' . number_format($birdsAlive) . ' birds alive')
                    ->icon('heroicon-o-calendar')
                    ->color($currentWeek < 18 ? 'warning' : 'success'),
                Stat::make('Target kg/week', $currentWeek < 18 ? 'N/A (Week < 18)' : 'No target set')
                    ->description($currentWeek < 18 ? 'Use Rearing Targets' : 'Add production target for this week')
                    ->icon('heroicon-o-scale')
                    ->color('warning'),
                Stat::make('Feed Consumed', number_format($feedConsumedThisWeek, 1) . ' kg')
                    ->description("Week {$currentWeek}: {$weekStartDate->format('M d')} - {$weekEndDate->format('M d')}")
                    ->icon('heroicon-o-beaker')
                    ->color('primary'),
                Stat::make('Target HD%', $currentWeek < 18 ? 'N/A' : 'No target set')
                    ->description($currentWeek < 18 ? 'Not in production yet' : 'Add production target')
                    ->icon('heroicon-o-chart-bar')
                    ->color('warning'),
            ];
        }

        // Calculate flock total target
        $kgWeekTarget = $target->feed_intake_per_day_g * 7 * $birdsAlive / 1000;

        // Determine consumption status color
        $consumptionColor = 'primary';
        if ($feedConsumedThisWeek > 0) {
            $tolerance = $kgWeekTarget * 0.1; // 10% tolerance
            if ($feedConsumedThisWeek >= ($kgWeekTarget - $tolerance) && $feedConsumedThisWeek <= ($kgWeekTarget + $tolerance)) {
                $consumptionColor = 'success';
            } elseif ($feedConsumedThisWeek < ($kgWeekTarget - $tolerance)) {
                $consumptionColor = 'warning';
            } else {
                $consumptionColor = 'danger';
            }
        }

        return [
            Stat::make('Current Week', "Week {$currentWeek}")
                ->description($batch->code . ' - ' . number_format($birdsAlive) . ' birds alive')
                ->icon('heroicon-o-calendar')
                ->color('success'),
            Stat::make('Target kg/week', number_format($kgWeekTarget, 1) . ' kg')
                ->description("Target: {$target->feed_intake_per_day_g}g/bird/day × 7 days")
                ->icon('heroicon-o-scale')
                ->color('info'),
            Stat::make('Feed Consumed', number_format($feedConsumedThisWeek, 1) . ' kg')
                ->description("Week {$currentWeek}: {$weekStartDate->format('M d')} - {$weekEndDate->format('M d')}")
                ->icon('heroicon-o-beaker')
                ->color($consumptionColor),
            Stat::make('Target HD%', number_format($target->hen_day_production_pct, 1) . '%')
                ->description('Hen Day Production target')
                ->icon('heroicon-o-chart-bar')
                ->color('success'),
        ];
    }

    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Current Week', 'N/A')
                ->color('gray'),
            Stat::make('Target kg/week', '-')
                ->color('gray'),
            Stat::make('Feed Consumed', '-')
                ->color('gray'),
            Stat::make('Target HD%', '-')
                ->color('gray'),
        ];
    }
}

