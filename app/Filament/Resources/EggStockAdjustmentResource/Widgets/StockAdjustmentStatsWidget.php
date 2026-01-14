<?php

namespace App\Filament\Resources\EggStockAdjustmentResource\Widgets;

use App\Models\EggStockAdjustment;
use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockAdjustmentStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = EggStockAdjustment::getAdjustmentStats();
        $availableEggs = SalesOrder::getAvailableEggs();

        return [
            Stat::make('Current Stock', number_format($availableEggs) . ' eggs')
                ->description(number_format($availableEggs / 30, 0) . ' trays')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Net Adjustment', number_format($stats['net_adjustment']) . ' eggs')
                ->description($stats['adjustment_count'] . ' adjustments made')
                ->descriptionIcon($stats['net_adjustment'] >= 0 
                    ? 'heroicon-m-arrow-trending-up' 
                    : 'heroicon-m-arrow-trending-down')
                ->color($stats['net_adjustment'] >= 0 ? 'success' : 'danger'),

            Stat::make('Total Increases', '+' . number_format($stats['total_increases']) . ' eggs')
                ->description('Stock added via adjustments')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('success'),

            Stat::make('Total Decreases', '-' . number_format($stats['total_decreases']) . ' eggs')
                ->description('Stock removed via adjustments')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('danger'),
        ];
    }
}

