<?php

namespace App\Filament\Pages;

use App\Models\Batch;
use App\Models\DailyFeedIntake;
use App\Models\DailyProduction;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\MortalityLog;
use App\Models\ProductionTarget;
use App\Models\RearingTarget;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalaryPayment;
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
use Illuminate\Support\Facades\Auth;
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
    
    // Expense projection properties
    public ?string $projectionMonth = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->projectionMonth = now()->format('Y-m');
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

    protected function getProjectionFormSchema(): array
    {
        // Generate month options for the last 12 months and next 6 months
        $months = [];
        $start = now()->subMonths(12);
        $end = now()->addMonths(6);
        
        while ($start <= $end) {
            $months[$start->format('Y-m')] = $start->format('F Y');
            $start->addMonth();
        }

        return [
            Select::make('projectionMonth')
                ->label('Select Month')
                ->options($months)
                ->default(now()->format('Y-m'))
                ->live()
                ->afterStateUpdated(fn () => $this->dispatch('$refresh')),
        ];
    }

    public function getExpenseProjection(): array
    {
        $selectedMonth = Carbon::parse($this->projectionMonth . '-01');
        $startOfMonth = $selectedMonth->copy()->startOfMonth();
        $endOfMonth = $selectedMonth->copy()->endOfMonth();
        $isCurrentOrFuture = $selectedMonth->format('Y-m') >= now()->format('Y-m');

        // Get actual expenses for the selected month
        $actualExpenses = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        // Calculate salary projections for future months
        $salaryProjection = 0;
        $actualSalaryExpense = $actualExpenses->get('salary')?->total ?? 0;

        if ($isCurrentOrFuture) {
            // For current/future months, project based on active employee salaries
            $activeEmployees = EmployeeSalary::where('status', 'active')
                ->where('start_date', '<=', $endOfMonth)
                ->where(function ($query) use ($startOfMonth) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $startOfMonth);
                })
                ->get();

            $salaryProjection = $activeEmployees->sum('salary_amount');
        }

        // Actual paid salaries for this month
        $paidSalaries = SalaryPayment::whereHas('employeeSalary')
            ->where('status', 'paid')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('net_amount');

        // Get actual feed expenses
        $actualFeedExpense = $actualExpenses->get('feed')?->total ?? 0;

        // Calculate projected feed expense based on targets
        $feedProjection = $this->calculateFeedProjection($startOfMonth, $endOfMonth);
        
        // Use projected feed with tolerance for current/future months, actual for past
        $feedExpense = $isCurrentOrFuture ? $feedProjection['projectedCostWithTolerance'] : $actualFeedExpense;

        // Get other expenses (excluding salary and feed)
        $otherExpenses = $actualExpenses->filter(function ($item, $category) {
            return !in_array($category, ['salary', 'feed']);
        })->sum('total');

        // Calculate totals
        $totalActual = $actualExpenses->sum('total');
        $totalProjected = $salaryProjection + $feedExpense + $otherExpenses;

        // Get breakdown of other expenses
        $otherExpensesBreakdown = $actualExpenses->filter(function ($item, $category) {
            return !in_array($category, ['salary', 'feed']);
        })->map(fn ($item) => $item->total)->toArray();

        // Get active employees for projection details
        $employeeDetails = [];
        if ($isCurrentOrFuture) {
            $employeeDetails = EmployeeSalary::where('status', 'active')
                ->where('start_date', '<=', $endOfMonth)
                ->where(function ($query) use ($startOfMonth) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $startOfMonth);
                })
                ->select('employee_name', 'position', 'salary_amount')
                ->get()
                ->toArray();
        }

        // Calculate income projection based on production targets
        $incomeProjection = $this->calculateIncomeProjection($startOfMonth, $endOfMonth);
        
        // Get actual revenue for the month
        $actualRevenue = SalesOrderItem::whereHas('salesOrder', function ($q) use ($startOfMonth, $endOfMonth) {
            $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
              ->where('status', 'delivered');
        })->selectRaw('SUM(qty * unit_price) as total')->value('total') ?? 0;

        // Use projected income for current/future months, actual for past
        $projectedIncome = $isCurrentOrFuture ? $incomeProjection['projectedIncome'] : $actualRevenue;

        return [
            'selectedMonth' => $selectedMonth->format('F Y'),
            'isCurrentOrFuture' => $isCurrentOrFuture,
            'salaryProjection' => $salaryProjection,
            'actualSalaryExpense' => $actualSalaryExpense,
            'paidSalaries' => $paidSalaries,
            'feedExpense' => $feedExpense,
            'actualFeedExpense' => $actualFeedExpense,
            'feedProjection' => $feedProjection,
            'incomeProjection' => $incomeProjection,
            'actualRevenue' => $actualRevenue,
            'projectedIncome' => $projectedIncome,
            'otherExpenses' => $otherExpenses,
            'otherExpensesBreakdown' => $otherExpensesBreakdown,
            'totalActual' => $totalActual,
            'totalProjected' => $totalProjected,
            'employeeCount' => count($employeeDetails),
            'employeeDetails' => $employeeDetails,
        ];
    }

    /**
     * Calculate projected feed expense based on rearing and production targets
     */
    protected function calculateFeedProjection(Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $daysInMonth = $startOfMonth->daysInMonth;
        $totalProjectedKg = 0;
        $batchDetails = [];

        // Get batches that were active (not culled/closed) at the selected month
        // Include batches placed before or during the selected month
        $activeBatches = Batch::whereNotIn('status', ['culled', 'closed'])
            ->where('placement_date', '<=', $endOfMonth)
            ->get();

        foreach ($activeBatches as $batch) {
            // Skip if batch was placed after the selected month
            if ($batch->placement_date > $endOfMonth) {
                continue;
            }

            // Calculate batch age at start of selected month (in weeks)
            $ageAtStartDays = max(0, $batch->placement_date->diffInDays($startOfMonth));
            $ageAtStartWeeks = max(1, ceil($ageAtStartDays / 7));
            
            // Determine the projected status based on age at the selected month
            // Typically: Week 1-4 = brooding, Week 5-17 = growing, Week 18+ = laying
            $projectedStatus = $this->getProjectedBatchStatus($ageAtStartWeeks);
            
            // Get total mortality up to start of selected month
            $mortalityBeforeMonth = MortalityLog::where('batch_id', $batch->id)
                ->where('date', '<', $startOfMonth)
                ->sum('count');
            
            // For future months, estimate mortality based on average rates
            // For past months, use actual data
            $mortalityDuringMonth = MortalityLog::where('batch_id', $batch->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->sum('count');
            
            // Current bird count at start of selected month
            $currentBirds = max(0, $batch->placement_qty - $mortalityBeforeMonth);
            
            // Average birds for the month (accounting for mortality)
            $avgBirds = max(0, $currentBirds - ($mortalityDuringMonth / 2));
            
            // Get feed target based on PROJECTED status for the selected month
            $dailyFeedPerBirdG = 0;
            $targetSource = '';
            
            if (in_array($projectedStatus, ['brooding', 'growing'])) {
                // Use rearing targets
                $rearingTarget = RearingTarget::where('week', '<=', $ageAtStartWeeks)
                    ->orderBy('week', 'desc')
                    ->first();
                
                if ($rearingTarget) {
                    $dailyFeedPerBirdG = ($rearingTarget->daily_feed_min_g + $rearingTarget->daily_feed_max_g) / 2;
                    $targetSource = 'Rearing (Week ' . $rearingTarget->week . ')';
                }
            } else {
                // Use production targets for laying batches
                $productionTarget = ProductionTarget::where('week', '<=', $ageAtStartWeeks)
                    ->orderBy('week', 'desc')
                    ->first();
                
                if ($productionTarget) {
                    $dailyFeedPerBirdG = $productionTarget->feed_intake_per_day_g;
                    $targetSource = 'Production (Week ' . $productionTarget->week . ')';
                }
            }
            
            // Calculate monthly feed in kg
            $monthlyFeedKg = ($dailyFeedPerBirdG * $avgBirds * $daysInMonth) / 1000;
            $totalProjectedKg += $monthlyFeedKg;
            
            $batchDetails[] = [
                'batch_code' => $batch->code,
                'status' => $projectedStatus,
                'age_weeks' => $ageAtStartWeeks,
                'bird_count' => round($avgBirds),
                'daily_feed_g' => round($dailyFeedPerBirdG, 1),
                'monthly_feed_kg' => round($monthlyFeedKg, 2),
                'target_source' => $targetSource,
            ];
        }

        // Calculate highest feed price per kg from feed expenses (last 3 months)
        // Only consider expenses that have total_kgs recorded
        $threeMonthsAgo = now()->subMonths(3);
        $feedExpenses = Expense::where('category', 'feed')
            ->where('date', '>=', $threeMonthsAgo)
            ->whereNotNull('total_kgs')
            ->where('total_kgs', '>', 0)
            ->get();
        
        // Calculate price per kg for each expense and find the highest
        $highestPricePerKg = 0;
        $feedPriceDetails = [];
        
        foreach ($feedExpenses as $expense) {
            $pricePerKg = $expense->amount / $expense->total_kgs;
            $feedPriceDetails[] = [
                'date' => $expense->date->format('Y-m-d'),
                'amount' => $expense->amount,
                'total_kgs' => $expense->total_kgs,
                'price_per_kg' => round($pricePerKg, 2),
            ];
            
            if ($pricePerKg > $highestPricePerKg) {
                $highestPricePerKg = $pricePerKg;
            }
        }
        
        // Default price per kg if no historical data with kgs recorded (710 RWF per kg)
        $basePricePerKg = $highestPricePerKg > 0 
            ? $highestPricePerKg 
            : 710; // Default 710 RWF per kg if no data
        
        // Apply 3% tolerance to price per kg
        $tolerancePercent = 3;
        $pricePerKgWithTolerance = $basePricePerKg * (1 + ($tolerancePercent / 100));
        
        // Calculate projected costs (base and with tolerance)
        $projectedCost = $totalProjectedKg * $basePricePerKg;
        $projectedCostWithTolerance = $totalProjectedKg * $pricePerKgWithTolerance;

        return [
            'totalProjectedKg' => round($totalProjectedKg, 2),
            'avgPricePerKg' => round($basePricePerKg, 2),
            'pricePerKgWithTolerance' => round($pricePerKgWithTolerance, 2),
            'tolerancePercent' => $tolerancePercent,
            'projectedCost' => round($projectedCost, 2),
            'projectedCostWithTolerance' => round($projectedCostWithTolerance, 2),
            'batchCount' => count($batchDetails),
            'batchDetails' => $batchDetails,
            'feedPriceDetails' => $feedPriceDetails,
            'feedExpenseCount' => count($feedExpenses),
        ];
    }

    /**
     * Determine the projected batch status based on age in weeks
     */
    protected function getProjectedBatchStatus(int $ageWeeks): string
    {
        if ($ageWeeks <= 4) {
            return 'brooding';
        } elseif ($ageWeeks <= 17) {
            return 'growing';
        } else {
            return 'laying';
        }
    }

    /**
     * Calculate projected income based on production targets (egg production)
     */
    protected function calculateIncomeProjection(Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $daysInMonth = $startOfMonth->daysInMonth;
        $totalProjectedEggs = 0;
        $batchDetails = [];

        // Get all active batches (not culled/closed) that were placed before selected month
        $activeBatches = Batch::whereNotIn('status', ['culled', 'closed'])
            ->where('placement_date', '<=', $endOfMonth)
            ->get();

        foreach ($activeBatches as $batch) {
            // Calculate batch age at start of selected month (in weeks)
            $ageAtStartDays = max(0, $batch->placement_date->diffInDays($startOfMonth));
            $ageAtStartWeeks = max(1, ceil($ageAtStartDays / 7));
            
            // Determine the projected status based on age at the selected month
            $projectedStatus = $this->getProjectedBatchStatus($ageAtStartWeeks);
            
            // Only include batches that will be laying at the selected month
            if ($projectedStatus !== 'laying') {
                continue;
            }
            
            // Get total mortality up to start of selected month
            $mortalityBeforeMonth = MortalityLog::where('batch_id', $batch->id)
                ->where('date', '<', $startOfMonth)
                ->sum('count');
            
            // Get mortality during the month
            $mortalityDuringMonth = MortalityLog::where('batch_id', $batch->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->sum('count');
            
            // Current bird count (hens) at start of selected month
            $currentHens = max(0, $batch->placement_qty - $mortalityBeforeMonth);
            
            // Average hens for the month (accounting for mortality)
            $avgHens = max(0, $currentHens - ($mortalityDuringMonth / 2));
            
            // Get production target based on batch age at selected month
            $productionTarget = ProductionTarget::where('week', '<=', $ageAtStartWeeks)
                ->orderBy('week', 'desc')
                ->first();
            
            $henDayPct = 0;
            $targetSource = '';
            
            if ($productionTarget) {
                $henDayPct = $productionTarget->hen_day_production_pct;
                $targetSource = 'Week ' . $productionTarget->week . ' (' . $henDayPct . '%)';
            }
            
            // Calculate monthly egg production
            // eggs = hens × days × (production % / 100)
            $monthlyEggs = round($avgHens * $daysInMonth * ($henDayPct / 100));
            $totalProjectedEggs += $monthlyEggs;
            
            $batchDetails[] = [
                'batch_code' => $batch->code,
                'age_weeks' => $ageAtStartWeeks,
                'hen_count' => round($avgHens),
                'production_pct' => $henDayPct,
                'monthly_eggs' => $monthlyEggs,
                'target_source' => $targetSource,
            ];
        }

        // Calculate average egg price from historical sales data (last 3 months)
        $threeMonthsAgo = now()->subMonths(3);
        
        // Get egg-related sales (products containing 'egg' in name)
        $historicalEggSales = SalesOrderItem::whereHas('salesOrder', function ($q) use ($threeMonthsAgo) {
            $q->where('order_date', '>=', $threeMonthsAgo)
              ->where('status', 'delivered');
        })
            ->where('product', 'like', '%egg%')
            ->selectRaw('SUM(qty * unit_price) as total_revenue, SUM(qty) as total_qty')
            ->first();
        
        $historicalRevenue = $historicalEggSales->total_revenue ?? 0;
        $historicalQty = $historicalEggSales->total_qty ?? 0;
        
        // Also check actual egg production for reference
        $historicalEggProduction = DailyProduction::where('date', '>=', $threeMonthsAgo)
            ->sum('eggs_total');
        
        // Default price per egg if no historical data (150 RWF per egg)
        $avgPricePerEgg = $historicalQty > 0 
            ? $historicalRevenue / $historicalQty 
            : 150; // Default 150 RWF per egg if no data
        
        $projectedIncome = $totalProjectedEggs * $avgPricePerEgg;

        return [
            'totalProjectedEggs' => $totalProjectedEggs,
            'avgPricePerEgg' => round($avgPricePerEgg, 2),
            'projectedIncome' => round($projectedIncome, 2),
            'batchCount' => count($batchDetails),
            'batchDetails' => $batchDetails,
            'historicalEggProduction' => $historicalEggProduction,
            'historicalRevenue' => round($historicalRevenue, 2),
            'historicalQty' => $historicalQty,
        ];
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

    /**
     * Get breakeven analysis data for the last 12 months
     */
    public function getBreakevenData(): array
    {
        $months = [];
        $monthlyData = [];
        $cumulativeIncome = 0;
        $cumulativeExpenses = 0;
        
        // Get last 12 months of data
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $monthLabel = $monthStart->format('M Y');
            $monthKey = $monthStart->format('Y-m');
            
            // Get monthly income (from delivered sales)
            $monthlyIncome = SalesOrderItem::whereHas('salesOrder', function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('order_date', [$monthStart, $monthEnd])
                  ->where('status', 'delivered');
            })->selectRaw('SUM(qty * unit_price) as total')->value('total') ?? 0;
            
            // Get monthly expenses
            $monthlyExpense = Expense::whereBetween('date', [$monthStart, $monthEnd])->sum('amount');
            
            // Calculate net profit/loss
            $netProfit = $monthlyIncome - $monthlyExpense;
            
            // Update cumulative totals
            $cumulativeIncome += $monthlyIncome;
            $cumulativeExpenses += $monthlyExpense;
            
            $months[] = $monthLabel;
            $monthlyData[$monthKey] = [
                'label' => $monthLabel,
                'income' => (float) $monthlyIncome,
                'expenses' => (float) $monthlyExpense,
                'netProfit' => (float) $netProfit,
                'cumulativeIncome' => (float) $cumulativeIncome,
                'cumulativeExpenses' => (float) $cumulativeExpenses,
                'cumulativeProfit' => (float) ($cumulativeIncome - $cumulativeExpenses),
            ];
        }
        
        // Calculate overall metrics
        $totalIncome = $cumulativeIncome;
        $totalExpenses = $cumulativeExpenses;
        $totalProfit = $totalIncome - $totalExpenses;
        
        // Calculate total eggs produced
        $twelveMonthsAgo = now()->subMonths(12)->startOfMonth();
        $totalEggsProduced = DailyProduction::where('date', '>=', $twelveMonthsAgo)
            ->sum('eggs_total');
        
        // Calculate cost per egg using primary cost categories (feed, veterinary, salaries)
        // These are the main operational costs for egg production
        $primaryExpenses = Expense::where('date', '>=', $twelveMonthsAgo)
            ->whereIn('category', ['feed', 'veterinary', 'salary'])
            ->sum('amount');
        
        // Cost per egg calculation hierarchy:
        // 1. If eggs produced, use primary expenses (feed + veterinary + salaries) / eggs
        // 2. If no eggs but has expenses, fall back to 150 RWF
        $costPerEgg = 150; // Default fallback
        $costSource = 'default';
        
        if ($totalEggsProduced > 0 && $primaryExpenses > 0) {
            $costPerEgg = $primaryExpenses / $totalEggsProduced;
            $costSource = 'calculated';
        }
        
        // Average monthly income and expenses
        $avgMonthlyIncome = $totalIncome / 12;
        $avgMonthlyExpenses = $totalExpenses / 12;
        
        // Profit margin
        $profitMargin = $totalIncome > 0 ? ($totalProfit / $totalIncome) * 100 : 0;
        
        // Find the breakeven point (month where cumulative income crosses cumulative expenses)
        $breakevenMonth = null;
        $prevProfit = null;
        foreach ($monthlyData as $key => $data) {
            if ($prevProfit !== null && $prevProfit < 0 && $data['cumulativeProfit'] >= 0) {
                $breakevenMonth = $data['label'];
                break;
            }
            $prevProfit = $data['cumulativeProfit'];
        }
        
        // Get average selling price from historical egg sales data (last 3 months)
        $threeMonthsAgo = now()->subMonths(3);
        $historicalEggSales = SalesOrderItem::whereHas('salesOrder', function ($q) use ($threeMonthsAgo) {
            $q->where('order_date', '>=', $threeMonthsAgo)
              ->where('status', 'delivered');
        })
            ->where('product', 'like', '%egg%')
            ->selectRaw('SUM(qty * unit_price) as total_revenue, SUM(qty) as total_qty')
            ->first();
        
        $historicalRevenue = $historicalEggSales->total_revenue ?? 0;
        $historicalQty = $historicalEggSales->total_qty ?? 0;
        
        // Average price per egg from sales data, fallback to 150 RWF if no sales
        $avgPricePerEgg = $historicalQty > 0 
            ? $historicalRevenue / $historicalQty 
            : 150; // Default 150 RWF if no sales data
        
        $priceSource = $historicalQty > 0 ? 'sales_history' : 'default';
        
        // Breakeven price = cost per egg (selling at cost means no profit)
        $breakevenEggPrice = $costPerEgg;
        
        // Recommended prices at various margins
        $recommendedPrices = [];
        $margins = [20, 30, 40, 50];
        foreach ($margins as $margin) {
            $recommendedPrices[$margin] = $costPerEgg > 0 
                ? round($costPerEgg / (1 - ($margin / 100)), 2) 
                : 0;
        }
        
        return [
            'months' => $months,
            'monthlyData' => array_values($monthlyData),
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'totalProfit' => $totalProfit,
            'profitMargin' => round($profitMargin, 1),
            'avgMonthlyIncome' => round($avgMonthlyIncome, 0),
            'avgMonthlyExpenses' => round($avgMonthlyExpenses, 0),
            'totalEggsProduced' => $totalEggsProduced,
            'primaryExpenses' => $primaryExpenses,
            'costPerEgg' => round($costPerEgg, 2),
            'costSource' => $costSource,
            'avgPricePerEgg' => round($avgPricePerEgg, 2),
            'priceSource' => $priceSource,
            'breakevenEggPrice' => round($breakevenEggPrice, 2),
            'breakevenMonth' => $breakevenMonth,
            'recommendedPrices' => $recommendedPrices,
            'isBreakeven' => $totalProfit >= 0,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'data' => $this->getFinancialData(),
            'projection' => $this->getExpenseProjection(),
            'projectionFormSchema' => $this->getProjectionFormSchema(),
            'breakeven' => $this->getBreakevenData(),
        ];
    }
}

