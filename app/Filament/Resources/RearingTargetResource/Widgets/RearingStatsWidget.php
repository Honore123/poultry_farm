<?php

namespace App\Filament\Resources\RearingTargetResource\Widgets;

use App\Models\Batch;
use App\Models\MortalityLog;
use App\Models\RearingTarget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class RearingStatsWidget extends BaseWidget
{
    public ?int $selectedBatchId = null;

    #[On('rearing-batch-selected')]
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
                Stat::make('Min kg/week', '-')
                    ->description('Flock total')
                    ->icon('heroicon-o-arrow-trending-down')
                    ->color('gray'),
                Stat::make('Max kg/week', '-')
                    ->description('Flock total')
                    ->icon('heroicon-o-arrow-trending-up')
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

        // Get target for current week
        $target = RearingTarget::where('week', $currentWeek)->first();

        if (!$target || $currentWeek > 18) {
            return [
                Stat::make('Current Week', "Week {$currentWeek}")
                    ->description($batch->code . ' - ' . number_format($birdsAlive) . ' birds alive')
                    ->icon('heroicon-o-calendar')
                    ->color($currentWeek > 18 ? 'warning' : 'primary'),
                Stat::make('Min kg/week', $currentWeek > 18 ? 'N/A (Week > 18)' : 'No target set')
                    ->description($currentWeek > 18 ? 'Use Production Targets' : 'Add rearing target for this week')
                    ->icon('heroicon-o-arrow-trending-down')
                    ->color('warning'),
                Stat::make('Max kg/week', $currentWeek > 18 ? 'N/A (Week > 18)' : 'No target set')
                    ->description($currentWeek > 18 ? 'Use Production Targets' : 'Add rearing target for this week')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('warning'),
            ];
        }

        // Calculate flock totals
        $minKgWeek = $target->daily_feed_min_g * 7 * $birdsAlive / 1000;
        $maxKgWeek = $target->daily_feed_max_g * 7 * $birdsAlive / 1000;

        return [
            Stat::make('Current Week', "Week {$currentWeek}")
                ->description($batch->code . ' - ' . number_format($birdsAlive) . ' birds alive')
                ->icon('heroicon-o-calendar')
                ->color('primary'),
            Stat::make('Min kg/week', number_format($minKgWeek, 1) . ' kg')
                ->description("Target: {$target->daily_feed_min_g}g/bird/day × 7 days")
                ->icon('heroicon-o-arrow-trending-down')
                ->color('info'),
            Stat::make('Max kg/week', number_format($maxKgWeek, 1) . ' kg')
                ->description("Target: {$target->daily_feed_max_g}g/bird/day × 7 days")
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),
        ];
    }

    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Current Week', 'N/A')
                ->color('gray'),
            Stat::make('Min kg/week', '-')
                ->color('gray'),
            Stat::make('Max kg/week', '-')
                ->color('gray'),
        ];
    }
}

