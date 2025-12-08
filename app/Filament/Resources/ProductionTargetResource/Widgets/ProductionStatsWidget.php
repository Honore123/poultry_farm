<?php

namespace App\Filament\Resources\ProductionTargetResource\Widgets;

use App\Models\Batch;
use App\Models\MortalityLog;
use App\Models\ProductionTarget;
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
                Stat::make('kg/week (Flock)', '-')
                    ->description('Flock total')
                    ->icon('heroicon-o-scale')
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

        // Get target for current week
        $target = ProductionTarget::where('week', $currentWeek)->first();

        if (!$target || $currentWeek < 18) {
            return [
                Stat::make('Current Week', "Week {$currentWeek}")
                    ->description($batch->code . ' - ' . number_format($birdsAlive) . ' birds alive')
                    ->icon('heroicon-o-calendar')
                    ->color($currentWeek < 18 ? 'warning' : 'success'),
                Stat::make('kg/week (Flock)', $currentWeek < 18 ? 'N/A (Week < 18)' : 'No target set')
                    ->description($currentWeek < 18 ? 'Use Rearing Targets' : 'Add production target for this week')
                    ->icon('heroicon-o-scale')
                    ->color('warning'),
                Stat::make('Target HD%', $currentWeek < 18 ? 'N/A' : 'No target set')
                    ->description($currentWeek < 18 ? 'Not in production yet' : 'Add production target')
                    ->icon('heroicon-o-chart-bar')
                    ->color('warning'),
            ];
        }

        // Calculate flock total
        $kgWeek = $target->feed_intake_per_day_g * 7 * $birdsAlive / 1000;

        return [
            Stat::make('Current Week', "Week {$currentWeek}")
                ->description($batch->code . ' - ' . number_format($birdsAlive) . ' birds alive')
                ->icon('heroicon-o-calendar')
                ->color('success'),
            Stat::make('kg/week (Flock)', number_format($kgWeek, 1) . ' kg')
                ->description("Target: {$target->feed_intake_per_day_g}g/bird/day × 7 days")
                ->icon('heroicon-o-scale')
                ->color('info'),
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
            Stat::make('kg/week (Flock)', '-')
                ->color('gray'),
            Stat::make('Target HD%', '-')
                ->color('gray'),
        ];
    }
}

