<?php

namespace App\Filament\Widgets;

use App\Models\Batch;
use App\Models\MortalityLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BatchOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->can('view_dashboard') ?? false;
    }

    protected function getStats(): array
    {
        // Batch counts by status
        $activeBatches = Batch::whereIn('status', ['brooding', 'growing', 'laying'])->count();
        $layingBatches = Batch::where('status', 'laying')->count();
        $broodingBatches = Batch::where('status', 'brooding')->count();

        // Total birds alive (placed - mortality)
        $batches = Batch::whereIn('status', ['brooding', 'growing', 'laying'])->get();
        $totalPlaced = $batches->sum('placement_qty');
        $totalMortality = MortalityLog::whereIn('batch_id', $batches->pluck('id'))->sum('count');
        $birdsAlive = $totalPlaced - $totalMortality;

        // Recent batch
        $latestBatch = Batch::latest('placement_date')->first();
        $latestBatchInfo = $latestBatch 
            ? "{$latestBatch->code} ({$latestBatch->placement_date->format('M d')})"
            : 'None';

        return [
            Stat::make('Active Batches', $activeBatches)
                ->description("Laying: {$layingBatches} | Brooding: {$broodingBatches}")
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make('Birds Alive', number_format($birdsAlive))
                ->description('From ' . number_format($totalPlaced) . ' placed')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),

            Stat::make('Latest Batch', $latestBatchInfo)
                ->description($latestBatch ? $latestBatch->status : '')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}

