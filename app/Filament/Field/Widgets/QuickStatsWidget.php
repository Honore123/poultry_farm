<?php

namespace App\Filament\Field\Widgets;

use App\Models\Batch;
use App\Models\DailyFeedIntake;
use App\Models\DailyProduction;
use App\Models\MortalityLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuickStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = today();

        // Today's entries
        $todayEggs = DailyProduction::whereDate('date', $today)->sum('eggs_total');
        $todayFeed = DailyFeedIntake::whereDate('date', $today)->sum('kg_given');
        $todayMortality = MortalityLog::whereDate('date', $today)->sum('count');

        // Active batches
        $activeBatches = Batch::whereIn('status', ['brooding', 'growing', 'laying'])->count();

        // Batches missing data today
        $batchesWithEggs = DailyProduction::whereDate('date', $today)
            ->distinct('batch_id')
            ->count('batch_id');
        $layingBatches = Batch::where('status', 'laying')->count();
        $missingEggs = max(0, $layingBatches - $batchesWithEggs);

        $batchesWithFeed = DailyFeedIntake::whereDate('date', $today)
            ->distinct('batch_id')
            ->count('batch_id');
        $missingFeed = max(0, $activeBatches - $batchesWithFeed);

        return [
            Stat::make('Today\'s Eggs', number_format($todayEggs))
                ->description($missingEggs > 0 ? "⚠️ {$missingEggs} batches pending" : '✅ All recorded')
                ->descriptionIcon($missingEggs > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($missingEggs > 0 ? 'warning' : 'success'),

            Stat::make('Today\'s Feed', number_format($todayFeed, 1) . ' kg')
                ->description($missingFeed > 0 ? "⚠️ {$missingFeed} batches pending" : '✅ All recorded')
                ->descriptionIcon($missingFeed > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($missingFeed > 0 ? 'warning' : 'success'),

            Stat::make('Today\'s Mortality', $todayMortality)
                ->description($todayMortality > 0 ? '⚠️ Deaths recorded' : '✅ No deaths')
                ->descriptionIcon($todayMortality > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-heart')
                ->color($todayMortality > 0 ? 'danger' : 'success'),

            Stat::make('Active Batches', $activeBatches)
                ->description($layingBatches . ' laying')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('info'),
        ];
    }
}

