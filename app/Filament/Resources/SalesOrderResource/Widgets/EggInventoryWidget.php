<?php

namespace App\Filament\Resources\SalesOrderResource\Widgets;

use App\Models\DailyProduction;
use App\Models\EggStockAdjustment;
use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EggInventoryWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Get breakdown of available eggs
        $breakdown = SalesOrder::getAvailableEggsBreakdown();
        $sellableEggs = $breakdown['sellable'];
        $soldEggs = $breakdown['sold'];
        $adjustment = $breakdown['adjustment'];
        $availableEggs = $breakdown['available'];

        // Calculate percentages
        $soldPercentage = $sellableEggs > 0 ? round(($soldEggs / $sellableEggs) * 100, 1) : 0;

        // Get today's sales
        $todaySoldEggs = $this->getTodaySoldEggs();

        // Get this week's sales
        $weekSoldEggs = $this->getWeekSoldEggs();

        // Build description for available eggs including adjustment info
        $availableDescription = number_format($availableEggs / 30, 0) . ' trays';
        if ($adjustment !== 0) {
            $adjustmentSign = $adjustment > 0 ? '+' : '';
            $availableDescription .= ' (' . $adjustmentSign . number_format($adjustment) . ' adjustment)';
        }

        return [
            Stat::make('Available Eggs', number_format($availableEggs))
                ->description($availableDescription)
                ->descriptionIcon('heroicon-m-cube')
                ->color($availableEggs > 0 ? 'success' : 'danger')
                ->chart($this->getAvailableEggsTrend()),

            Stat::make('Eggs Sold', number_format($soldEggs))
                ->description($soldPercentage . '% of sellable production')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info')
                ->chart($this->getSoldEggsTrend()),

            Stat::make('Today\'s Sales', number_format($todaySoldEggs) . ' eggs')
                ->description('This week: ' . number_format($weekSoldEggs) . ' eggs')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
        ];
    }

    /**
     * Get eggs sold today
     */
    protected function getTodaySoldEggs(): int
    {
        $orders = SalesOrder::whereIn('status', ['confirmed', 'delivered'])
            ->whereDate('order_date', today())
            ->with('items')
            ->get();

        return $orders->sum(fn ($order) => $order->total_eggs);
    }

    /**
     * Get eggs sold this week
     */
    protected function getWeekSoldEggs(): int
    {
        $orders = SalesOrder::whereIn('status', ['confirmed', 'delivered'])
            ->whereBetween('order_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->with('items')
            ->get();

        return $orders->sum(fn ($order) => $order->total_eggs);
    }

    /**
     * Get available eggs trend for the last 7 days
     */
    protected function getAvailableEggsTrend(): array
    {
        $trend = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            // Sellable eggs up to this date
            $sellable = DailyProduction::query()
                ->whereDate('date', '<=', $date)
                ->select([
                    DB::raw('SUM(eggs_total) - SUM(COALESCE(eggs_soft, 0)) - SUM(COALESCE(eggs_cracked, 0)) as sellable'),
                ])
                ->value('sellable') ?? 0;

            // Sold eggs up to this date
            $sold = SalesOrder::whereIn('status', ['confirmed', 'delivered'])
                ->whereDate('order_date', '<=', $date)
                ->with('items')
                ->get()
                ->sum(fn ($order) => $order->total_eggs);

            // Stock adjustments up to this date
            $adjustment = EggStockAdjustment::getNetAdjustmentUntil($date);

            $trend[] = max(0, (int) $sellable - $sold + $adjustment);
        }

        return $trend;
    }

    /**
     * Get sold eggs trend for the last 7 days
     */
    protected function getSoldEggsTrend(): array
    {
        $trend = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $dailySold = SalesOrder::whereIn('status', ['confirmed', 'delivered'])
                ->whereDate('order_date', $date)
                ->with('items')
                ->get()
                ->sum(fn ($order) => $order->total_eggs);

            $trend[] = (int) $dailySold;
        }

        return $trend;
    }
}

