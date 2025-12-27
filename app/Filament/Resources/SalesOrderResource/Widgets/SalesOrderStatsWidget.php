<?php

namespace App\Filament\Resources\SalesOrderResource\Widgets;

use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class SalesOrderStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    public ?string $fromDate = null;
    public ?string $untilDate = null;

    #[On('updateSalesOrderWidgetFilters')]
    public function updateFilters(?string $fromDate = null, ?string $untilDate = null): void
    {
        $this->fromDate = $fromDate;
        $this->untilDate = $untilDate;
    }

    protected function getStats(): array
    {
        $query = SalesOrder::query()->with('items');

        // Apply date range filter
        if ($this->fromDate) {
            $query->whereDate('order_date', '>=', $this->fromDate);
        }
        if ($this->untilDate) {
            $query->whereDate('order_date', '<=', $this->untilDate);
        }

        // Get all orders matching date filters
        $allOrders = (clone $query)->get();
        
        // Get delivered orders
        $deliveredOrders = (clone $query)->where('status', 'delivered')->get();
        
        // Get confirmed orders (separate query to avoid status filter interference)
        $confirmedQuery = SalesOrder::query()->with('items')->where('status', 'confirmed');
        if ($this->fromDate) {
            $confirmedQuery->whereDate('order_date', '>=', $this->fromDate);
        }
        if ($this->untilDate) {
            $confirmedQuery->whereDate('order_date', '<=', $this->untilDate);
        }
        $confirmedOrders = $confirmedQuery->get();

        // Calculate totals
        $deliveredCount = $deliveredOrders->count();
        $confirmedCount = $confirmedOrders->count();

        // Calculate total revenue from delivered orders
        $totalRevenue = $deliveredOrders->sum(function ($order) {
            return $order->items->sum(fn ($item) => $item->qty * $item->unit_price);
        });

        // Calculate pending revenue from confirmed orders
        $pendingRevenue = $confirmedOrders->sum(function ($order) {
            return $order->items->sum(fn ($item) => $item->qty * $item->unit_price);
        });

        // Calculate total eggs sold (delivered orders)
        $totalEggsSold = $deliveredOrders->sum(fn ($order) => $order->total_eggs);

        // Calculate pending eggs (confirmed orders)
        $pendingEggs = $confirmedOrders->sum(fn ($order) => $order->total_eggs);

        // Build period description
        $periodDesc = 'All time';
        if ($this->fromDate && $this->untilDate) {
            $periodDesc = \Carbon\Carbon::parse($this->fromDate)->format('M d') . ' - ' . \Carbon\Carbon::parse($this->untilDate)->format('M d, Y');
        } elseif ($this->fromDate) {
            $periodDesc = 'From ' . \Carbon\Carbon::parse($this->fromDate)->format('M d, Y');
        } elseif ($this->untilDate) {
            $periodDesc = 'Until ' . \Carbon\Carbon::parse($this->untilDate)->format('M d, Y');
        }

        return [
            Stat::make('Total Revenue (Delivered)', 'RWF ' . number_format($totalRevenue, 0))
                ->description($periodDesc)
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Pending Revenue', 'RWF ' . number_format($pendingRevenue, 0))
                ->description($confirmedCount . ' confirmed orders awaiting delivery')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Eggs Sold', number_format($totalEggsSold))
                ->description(number_format($totalEggsSold / 30, 0) . ' trays delivered')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('Pending Eggs', number_format($pendingEggs))
                ->description(number_format($pendingEggs / 30, 0) . ' trays in confirmed orders')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),
        ];
    }
}

