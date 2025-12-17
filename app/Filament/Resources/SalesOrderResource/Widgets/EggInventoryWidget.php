<?php

namespace App\Filament\Resources\SalesOrderResource\Widgets;

use App\Models\DailyProduction;
use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EggInventoryWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Get total sellable eggs from production
        $sellableEggs = SalesOrder::getTotalSellableEggs();
        
        // Get total eggs sold (confirmed + delivered orders)
        $soldEggs = SalesOrder::getTotalEggsSold();
        
        // Available eggs
        $availableEggs = $sellableEggs - $soldEggs;

        // Calculate percentages
        $soldPercentage = $sellableEggs > 0 ? round(($soldEggs / $sellableEggs) * 100, 1) : 0;
        $availablePercentage = $sellableEggs > 0 ? round(($availableEggs / $sellableEggs) * 100, 1) : 0;

        // Get today's sales
        $todaySoldEggs = $this->getTodaySoldEggs();

        // Get this week's sales
        $weekSoldEggs = $this->getWeekSoldEggs();

        return [
            Stat::make('Available Eggs', number_format($availableEggs))
                ->description($availablePercentage . '% of sellable production')
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

            $trend[] = max(0, (int) $sellable - $sold);
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

