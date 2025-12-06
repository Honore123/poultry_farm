<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class FinancialDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Financial Reports';

    protected static ?string $navigationGroup = 'Sales & Finance';

    protected static ?int $navigationSort = 25;

    protected static string $view = 'filament.pages.financial-dashboard';

    public ?string $period = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Period')
                    ->schema([
                        Select::make('period')
                            ->label('Quick Period')
                            ->options([
                                'today' => 'Today',
                                'week' => 'This Week',
                                'month' => 'This Month',
                                'quarter' => 'This Quarter',
                                'year' => 'This Year',
                                'custom' => 'Custom Range',
                            ])
                            ->default('month')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $this->updateDateRange($state);
                                }
                            }),
                        DatePicker::make('startDate')
                            ->label('From')
                            ->visible(fn () => $this->period === 'custom')
                            ->live(),
                        DatePicker::make('endDate')
                            ->label('To')
                            ->visible(fn () => $this->period === 'custom')
                            ->live(),
                    ])
                    ->columns(3),
            ]);
    }

    public function updateDateRange(string $period): void
    {
        $this->period = $period;
        
        switch ($period) {
            case 'today':
                $this->startDate = now()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'quarter':
                $this->startDate = now()->startOfQuarter()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'year':
                $this->startDate = now()->startOfYear()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
        }
    }

    public function getFinancialData(): array
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        // Total Revenue (from delivered orders)
        $totalRevenue = SalesOrderItem::whereHas('salesOrder', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [$startDate, $endDate])
              ->where('status', 'delivered');
        })->selectRaw('SUM(qty * unit_price) as total')->value('total') ?? 0;

        // Pending Revenue (confirmed but not delivered)
        $pendingRevenue = SalesOrderItem::whereHas('salesOrder', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [$startDate, $endDate])
              ->where('status', 'confirmed');
        })->selectRaw('SUM(qty * unit_price) as total')->value('total') ?? 0;

        // Total Expenses
        $totalExpenses = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');

        // Net Profit/Loss
        $netProfit = $totalRevenue - $totalExpenses;

        // Orders count
        $ordersCount = SalesOrder::whereBetween('order_date', [$startDate, $endDate])->count();
        $deliveredOrders = SalesOrder::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', 'delivered')->count();

        // Expense breakdown by category
        $expensesByCategory = Expense::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->category => $item->total])
            ->toArray();

        // Daily revenue trend
        $revenueTrend = SalesOrderItem::whereHas('salesOrder', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [$startDate, $endDate])
              ->where('status', 'delivered');
        })
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->selectRaw('DATE(sales_orders.order_date) as date, SUM(sales_order_items.qty * sales_order_items.unit_price) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        // Daily expense trend
        $expenseTrend = Expense::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        // Top products by revenue
        $topProducts = SalesOrderItem::whereHas('salesOrder', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [$startDate, $endDate])
              ->where('status', 'delivered');
        })
            ->selectRaw('product, SUM(qty) as total_qty, SUM(qty * unit_price) as total_revenue')
            ->groupBy('product')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // Top customers by revenue
        $topCustomers = SalesOrder::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->with('customer', 'items')
            ->get()
            ->groupBy('customer_id')
            ->map(function ($orders) {
                $customer = $orders->first()->customer;
                $totalRevenue = $orders->sum(fn ($order) => $order->items->sum(fn ($item) => $item->qty * $item->unit_price));
                return [
                    'name' => $customer?->name ?? 'Walk-in',
                    'orders' => $orders->count(),
                    'revenue' => $totalRevenue,
                ];
            })
            ->sortByDesc('revenue')
            ->take(5)
            ->values();

        // Previous period comparison
        $periodDays = $startDate->diffInDays($endDate) + 1;
        $prevStartDate = $startDate->copy()->subDays($periodDays);
        $prevEndDate = $startDate->copy()->subDay();

        $prevRevenue = SalesOrderItem::whereHas('salesOrder', function ($q) use ($prevStartDate, $prevEndDate) {
            $q->whereBetween('order_date', [$prevStartDate, $prevEndDate])
              ->where('status', 'delivered');
        })->selectRaw('SUM(qty * unit_price) as total')->value('total') ?? 0;

        $prevExpenses = Expense::whereBetween('date', [$prevStartDate, $prevEndDate])->sum('amount');

        $revenueChange = $prevRevenue > 0 
            ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) 
            : ($totalRevenue > 0 ? 100 : 0);
        
        $expenseChange = $prevExpenses > 0 
            ? round((($totalExpenses - $prevExpenses) / $prevExpenses) * 100, 1) 
            : ($totalExpenses > 0 ? 100 : 0);

        return [
            'totalRevenue' => $totalRevenue,
            'pendingRevenue' => $pendingRevenue,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netProfit,
            'ordersCount' => $ordersCount,
            'deliveredOrders' => $deliveredOrders,
            'expensesByCategory' => $expensesByCategory,
            'revenueTrend' => $revenueTrend,
            'expenseTrend' => $expenseTrend,
            'topProducts' => $topProducts,
            'topCustomers' => $topCustomers,
            'revenueChange' => $revenueChange,
            'expenseChange' => $expenseChange,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'data' => $this->getFinancialData(),
        ];
    }
}

