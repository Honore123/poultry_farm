<x-filament-panels::page>
    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    @php
        $data = $this->getFinancialData();
        $breakeven = $this->getBreakevenData();
    @endphp

    {{-- Breakeven Point Visualization --}}
    <div class="mt-6 mb-8">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-scale class="w-5 h-5 text-primary-500" />
                    Breakeven Analysis (Last 12 Months)
                </div>
            </x-slot>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {{-- Total Income --}}
                <div class="bg-success-50 dark:bg-success-900/30 rounded-lg p-4 border border-success-200 dark:border-success-800">
                    <p class="text-sm font-medium text-success-700 dark:text-success-300">Total Income</p>
                    <p class="text-xl font-bold text-success-600 dark:text-success-400">
                        RWF {{ number_format($breakeven['totalIncome'], 0) }}
                    </p>
                </div>

                {{-- Total Expenses --}}
                <div class="bg-danger-50 dark:bg-danger-900/30 rounded-lg p-4 border border-danger-200 dark:border-danger-800">
                    <p class="text-sm font-medium text-danger-700 dark:text-danger-300">Total Expenses</p>
                    <p class="text-xl font-bold text-danger-600 dark:text-danger-400">
                        RWF {{ number_format($breakeven['totalExpenses'], 0) }}
                    </p>
                </div>

                {{-- Net Profit/Loss --}}
                <div class="{{ $breakeven['isBreakeven'] ? 'bg-success-50 dark:bg-success-900/30 border-success-200 dark:border-success-800' : 'bg-danger-50 dark:bg-danger-900/30 border-danger-200 dark:border-danger-800' }} rounded-lg p-4 border">
                    <p class="text-sm font-medium {{ $breakeven['isBreakeven'] ? 'text-success-700 dark:text-success-300' : 'text-danger-700 dark:text-danger-300' }}">
                        Net {{ $breakeven['isBreakeven'] ? 'Profit' : 'Loss' }}
                    </p>
                    <p class="text-xl font-bold {{ $breakeven['isBreakeven'] ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                        RWF {{ number_format(abs($breakeven['totalProfit']), 0) }}
                    </p>
                    <p class="text-xs {{ $breakeven['isBreakeven'] ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $breakeven['profitMargin'] }}% margin
                    </p>
                </div>

                {{-- Cost Per Egg --}}
                <div class="bg-primary-50 dark:bg-primary-900/30 rounded-lg p-4 border border-primary-200 dark:border-primary-800">
                    <p class="text-sm font-medium text-primary-700 dark:text-primary-300">Cost Per Egg</p>
                    <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                        RWF {{ number_format($breakeven['costPerEgg'], 1) }}
                    </p>
                    <p class="text-xs text-primary-600 dark:text-primary-400">
                        @if($breakeven['costSource'] === 'calculated')
                            Feed + Vet + Salaries
                        @else
                            Default estimate
                        @endif
                    </p>
                </div>
            </div>

            {{-- Breakeven Chart --}}
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Monthly Income vs Expenses</h4>
                
                @php
                    $hasAnyData = false;
                    if (count($breakeven['monthlyData']) > 0) {
                        $incomeValues = array_column($breakeven['monthlyData'], 'income');
                        $expenseValues = array_column($breakeven['monthlyData'], 'expenses');
                        $maxIncome = !empty($incomeValues) ? max($incomeValues) : 0;
                        $maxExpense = !empty($expenseValues) ? max($expenseValues) : 0;
                        $hasAnyData = ($maxIncome > 0 || $maxExpense > 0);
                        
                        // Prepare chart data
                        $monthLabels = array_column($breakeven['monthlyData'], 'label');
                        $monthLabelsFormatted = array_map(fn($d) => \Carbon\Carbon::parse($d)->format('M Y'), $monthLabels);
                        $netProfitValues = array_column($breakeven['monthlyData'], 'netProfit');
                    }
                @endphp
                
                @if(count($breakeven['monthlyData']) > 0 && $hasAnyData)
                    <div class="w-full" style="height: 320px;">
                        <canvas id="monthlyIncomeExpenseChart"></canvas>
                                            </div>
                                            
                    <script>
                        (function() {
                            function waitForChartBreakeven(callback, maxAttempts = 50) {
                                let attempts = 0;
                                const check = function() {
                                    if (typeof Chart !== 'undefined') {
                                        callback();
                                    } else if (attempts < maxAttempts) {
                                        attempts++;
                                        setTimeout(check, 100);
                                    }
                                };
                                check();
                            }
                            
                            function initMonthlyIncomeExpenseChart() {
                                const ctx = document.getElementById('monthlyIncomeExpenseChart');
                                if (!ctx) return;
                                
                                // Destroy existing chart if it exists
                                if (window.monthlyIncomeExpenseChart instanceof Chart) {
                                    window.monthlyIncomeExpenseChart.destroy();
                                }
                                
                                const isDark = document.documentElement.classList.contains('dark');
                                const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
                                const textColor = isDark ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';
                                
                                window.monthlyIncomeExpenseChart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: @json(array_values($monthLabelsFormatted)),
                                        datasets: [
                                            {
                                                label: 'Income',
                                                data: @json(array_values($incomeValues)),
                                                borderColor: '#22c55e',
                                                backgroundColor: 'rgba(34, 197, 94, 0.15)',
                                                borderWidth: 3,
                                                fill: true,
                                                tension: 0.4,
                                                pointRadius: 5,
                                                pointHoverRadius: 8,
                                                pointBackgroundColor: '#22c55e',
                                                pointBorderColor: '#fff',
                                                pointBorderWidth: 2
                                            },
                                            {
                                                label: 'Expenses',
                                                data: @json(array_values($expenseValues)),
                                                borderColor: '#ef4444',
                                                backgroundColor: 'rgba(239, 68, 68, 0.15)',
                                                borderWidth: 3,
                                                fill: true,
                                                tension: 0.4,
                                                pointRadius: 5,
                                                pointHoverRadius: 8,
                                                pointBackgroundColor: '#ef4444',
                                                pointBorderColor: '#fff',
                                                pointBorderWidth: 2
                                            },
                                            {
                                                label: 'Net Profit/Loss',
                                                data: @json(array_values($netProfitValues)),
                                                borderColor: '#8b5cf6',
                                                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                                                borderWidth: 2,
                                                borderDash: [5, 5],
                                                fill: false,
                                                tension: 0.4,
                                                pointRadius: 4,
                                                pointHoverRadius: 6,
                                                pointBackgroundColor: '#8b5cf6',
                                                pointBorderColor: '#fff',
                                                pointBorderWidth: 2
                                            }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        interaction: {
                                            mode: 'index',
                                            intersect: false
                                        },
                                        plugins: {
                                            legend: {
                                                position: 'bottom',
                                                labels: {
                                                    color: textColor,
                                                    usePointStyle: true,
                                                    padding: 20,
                                                    font: {
                                                        size: 12
                                                    }
                                                }
                                            },
                                            tooltip: {
                                                enabled: true,
                                                backgroundColor: isDark ? 'rgba(30, 30, 30, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                                                titleColor: isDark ? '#fff' : '#1f2937',
                                                bodyColor: isDark ? '#d1d5db' : '#4b5563',
                                                borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                                                borderWidth: 1,
                                                padding: 12,
                                                displayColors: true,
                                                callbacks: {
                                                    label: function(context) {
                                                        let label = context.dataset.label || '';
                                                        if (label) {
                                                            label += ': ';
                                                        }
                                                        if (context.parsed.y !== null) {
                                                            const value = context.parsed.y;
                                                            const prefix = context.dataset.label === 'Net Profit/Loss' && value < 0 ? '-' : '';
                                                            label += prefix + 'RWF ' + new Intl.NumberFormat().format(Math.abs(value));
                                                        }
                                                        return label;
                                                    }
                                                }
                                            }
                                        },
                                        scales: {
                                            x: {
                                                grid: {
                                                    color: gridColor,
                                                    drawBorder: false
                                                },
                                                ticks: {
                                                    color: textColor,
                                                    maxRotation: 45,
                                                    minRotation: 0
                                                }
                                            },
                                            y: {
                                                beginAtZero: true,
                                                grid: {
                                                    color: gridColor,
                                                    drawBorder: false
                                                },
                                                ticks: {
                                                    color: textColor,
                                                    callback: function(value) {
                                                        if (Math.abs(value) >= 1000000) {
                                                            return 'RWF ' + (value / 1000000).toFixed(1) + 'M';
                                                        } else if (Math.abs(value) >= 1000) {
                                                            return 'RWF ' + (value / 1000).toFixed(0) + 'K';
                                                        }
                                                        return 'RWF ' + value;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                            
                            // Initialize on page load
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', function() {
                                    waitForChartBreakeven(initMonthlyIncomeExpenseChart);
                                });
                            } else {
                                waitForChartBreakeven(initMonthlyIncomeExpenseChart);
                            }
                            
                            // Reinitialize on Livewire updates
                            document.addEventListener('livewire:navigated', function() {
                                waitForChartBreakeven(initMonthlyIncomeExpenseChart);
                            });
                            
                            // Handle Livewire v3 morphing
                            if (typeof Livewire !== 'undefined') {
                                Livewire.hook('morph.updated', () => {
                                    waitForChartBreakeven(initMonthlyIncomeExpenseChart);
                                });
                            }
                        })();
                    </script>
                @elseif(count($breakeven['monthlyData']) > 0)
                    {{-- Has months but no data --}}
                    <div class="text-center py-8 text-gray-500">
                        <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p class="text-sm">No income or expense data recorded for the last 12 months</p>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>No financial data available</p>
                    </div>
                @endif
            </div>

            {{-- Pricing Recommendations --}}
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                    <x-heroicon-o-currency-dollar class="w-5 h-5 text-primary-500" />
                    Egg Pricing Recommendations
                </h4>

                {{-- Cost Breakdown Info --}}
                <div class="mb-4 p-3 bg-primary-50 dark:bg-primary-900/30 rounded-lg border border-primary-200 dark:border-primary-700">
                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-primary-700 dark:text-primary-300 font-medium">Cost Per Egg:</span>
                            <span class="font-bold text-primary-600 dark:text-primary-400">RWF {{ number_format($breakeven['costPerEgg'], 1) }}</span>
                            @if($breakeven['costSource'] === 'calculated')
                                <span class="text-xs px-2 py-0.5 bg-primary-200 dark:bg-primary-800 text-primary-700 dark:text-primary-300 rounded">
                                    from Feed + Veterinary + Salaries
                                </span>
                            @else
                                <span class="text-xs px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                    default estimate
                                </span>
                            @endif
                        </div>
                        @if($breakeven['costSource'] === 'calculated')
                            <div class="text-xs text-primary-600 dark:text-primary-400">
                                RWF {{ number_format($breakeven['primaryExpenses'], 0) }} ÷ {{ number_format($breakeven['totalEggsProduced'], 0) }} eggs
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($breakeven['recommendedPrices'] as $margin => $price)
                        @php
                            $isBreakevenMargin = $margin == 20;
                            $isHighlighted = $margin == 30 || $margin == 40;
                        @endphp
                        <div class="rounded-lg p-4 text-center {{ $isHighlighted ? 'bg-primary-100 dark:bg-primary-900/50 border-2 border-primary-400 dark:border-primary-600' : 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700' }}">
                            <p class="text-xs font-medium {{ $isHighlighted ? 'text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $margin }}% Margin
                                @if($isHighlighted)
                                    <span class="text-xs text-primary-600 dark:text-primary-400">(Recommended)</span>
                                @endif
                            </p>
                            <p class="text-xl font-bold {{ $isHighlighted ? 'text-primary-600 dark:text-primary-400' : 'text-gray-900 dark:text-white' }}">
                                RWF {{ number_format($price, 0) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                per egg
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-500 dark:text-gray-400">Breakeven Price:</span>
                            <span class="font-bold text-warning-600">RWF {{ number_format($breakeven['breakevenEggPrice'], 1) }}</span>
                        </div>
                        <div class="text-gray-300 dark:text-gray-600">|</div>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-500 dark:text-gray-400">Avg. Selling Price:</span>
                            <span class="font-bold {{ $breakeven['avgPricePerEgg'] > $breakeven['breakevenEggPrice'] ? 'text-success-600' : 'text-danger-600' }}">
                                RWF {{ number_format($breakeven['avgPricePerEgg'], 1) }}
                            </span>
                            @if($breakeven['priceSource'] === 'sales_history')
                                <span class="text-xs px-2 py-0.5 bg-success-100 dark:bg-success-900 text-success-700 dark:text-success-300 rounded">
                                    from sales history
                                </span>
                            @else
                                <span class="text-xs px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                    default
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Key Financial Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        {{-- Total Revenue --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400">
                        RWF {{ number_format($data['totalRevenue'], 0) }}
                    </p>
                    @if($data['pendingRevenue'] > 0)
                        <p class="text-xs text-gray-500 mt-1">
                            + RWF {{ number_format($data['pendingRevenue'], 0) }} pending
                        </p>
                    @endif
                </div>
                <div class="p-3 rounded-full bg-success-100 dark:bg-success-900">
                    <x-heroicon-o-arrow-trending-up class="w-6 h-6 text-success-600 dark:text-success-400" />
                </div>
            </div>
            @if($data['revenueChange'] != 0)
                <div class="mt-2 flex items-center text-sm {{ $data['revenueChange'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                    @if($data['revenueChange'] >= 0)
                        <x-heroicon-m-arrow-up class="w-4 h-4 mr-1" />
                    @else
                        <x-heroicon-m-arrow-down class="w-4 h-4 mr-1" />
                    @endif
                    {{ abs($data['revenueChange']) }}% vs previous period
                </div>
            @endif
        </x-filament::section>

        {{-- Total Expenses --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Expenses</p>
                    <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">
                        RWF {{ number_format($data['totalExpenses'], 0) }}
                    </p>
                </div>
                <div class="p-3 rounded-full bg-danger-100 dark:bg-danger-900">
                    <x-heroicon-o-arrow-trending-down class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                </div>
            </div>
            @if($data['expenseChange'] != 0)
                <div class="mt-2 flex items-center text-sm {{ $data['expenseChange'] <= 0 ? 'text-success-600' : 'text-danger-600' }}">
                    @if($data['expenseChange'] >= 0)
                        <x-heroicon-m-arrow-up class="w-4 h-4 mr-1" />
                    @else
                        <x-heroicon-m-arrow-down class="w-4 h-4 mr-1" />
                    @endif
                    {{ abs($data['expenseChange']) }}% vs previous period
                </div>
            @endif
        </x-filament::section>

        {{-- Net Profit/Loss --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Profit/Loss</p>
                    <p class="text-2xl font-bold {{ $data['netProfit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                        RWF {{ number_format($data['netProfit'], 0) }}
                    </p>
                </div>
                <div class="p-3 rounded-full {{ $data['netProfit'] >= 0 ? 'bg-success-100 dark:bg-success-900' : 'bg-danger-100 dark:bg-danger-900' }}">
                    @if($data['netProfit'] >= 0)
                        <x-heroicon-o-banknotes class="w-6 h-6 text-success-600 dark:text-success-400" />
                    @else
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                    @endif
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-500">
                Margin: {{ $data['totalRevenue'] > 0 ? number_format(($data['netProfit'] / $data['totalRevenue']) * 100, 1) : 0 }}%
            </div>
        </x-filament::section>

        {{-- Orders Summary --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Orders</p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ $data['ordersCount'] }}
                    </p>
                </div>
                <div class="p-3 rounded-full bg-primary-100 dark:bg-primary-900">
                    <x-heroicon-o-shopping-cart class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-500">
                {{ $data['deliveredOrders'] }} delivered
            </div>
        </x-filament::section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Expense Breakdown by Category --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-pie class="w-5 h-5 text-gray-400" />
                    Expenses by Category
                </div>
            </x-slot>

            @if(count($data['expensesByCategory']) > 0)
                <div class="space-y-3">
                    @php
                        $maxExpense = max($data['expensesByCategory']);
                        $categoryLabels = [
                            'feed' => ['label' => 'Feed', 'color' => 'bg-amber-500'],
                            'labor' => ['label' => 'Labor', 'color' => 'bg-blue-500'],
                            'salary' => ['label' => 'Salary', 'color' => 'bg-cyan-500'],
                            'utilities' => ['label' => 'Utilities', 'color' => 'bg-purple-500'],
                            'veterinary' => ['label' => 'Veterinary', 'color' => 'bg-red-500'],
                            'maintenance' => ['label' => 'Maintenance', 'color' => 'bg-gray-500'],
                            'transport' => ['label' => 'Transport', 'color' => 'bg-green-500'],
                            'packaging' => ['label' => 'Packaging', 'color' => 'bg-pink-500'],
                            'other' => ['label' => 'Other', 'color' => 'bg-indigo-500'],
                        ];
                    @endphp
                    @foreach($data['expensesByCategory'] as $category => $amount)
                        @php
                            $catInfo = $categoryLabels[$category] ?? ['label' => ucfirst($category), 'color' => 'bg-gray-500'];
                            $percentage = $data['totalExpenses'] > 0 ? ($amount / $data['totalExpenses']) * 100 : 0;
                            $barWidth = $maxExpense > 0 ? ($amount / $maxExpense) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $catInfo['label'] }}</span>
                                <span class="text-gray-500">RWF {{ number_format($amount, 0) }} ({{ number_format($percentage, 1) }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $catInfo['color'] }} h-2 rounded-full transition-all duration-300" style="width: {{ $barWidth }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <x-heroicon-o-banknotes class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>No expenses recorded for this period</p>
                </div>
            @endif
        </x-filament::section>

        {{-- Top Products --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-trophy class="w-5 h-5 text-gray-400" />
                    Top Products by Revenue
                </div>
            </x-slot>

            @if($data['topProducts']->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="text-left py-2 font-medium text-gray-500">Product</th>
                                <th class="text-right py-2 font-medium text-gray-500">Qty Sold</th>
                                <th class="text-right py-2 font-medium text-gray-500">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['topProducts'] as $product)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="py-2 text-gray-900 dark:text-white">{{ $product->product }}</td>
                                    <td class="py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($product->total_qty) }}</td>
                                    <td class="py-2 text-right font-medium text-success-600">RWF {{ number_format($product->total_revenue, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <x-heroicon-o-shopping-bag class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>No sales recorded for this period</p>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Top Customers --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-users class="w-5 h-5 text-gray-400" />
                Top Customers
            </div>
        </x-slot>

        @if($data['topCustomers']->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($data['topCustomers'] as $index => $customer)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                            <span class="text-lg font-bold text-primary-600 dark:text-primary-400">#{{ $index + 1 }}</span>
                        </div>
                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ $customer['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $customer['orders'] }} orders</p>
                        <p class="text-lg font-bold text-success-600 mt-1">RWF {{ number_format($customer['revenue'], 0) }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-heroicon-o-users class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>No customer data for this period</p>
            </div>
        @endif
    </x-filament::section>

    {{-- Revenue & Expense Trend --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-chart-bar class="w-5 h-5 text-gray-400" />
                Daily Revenue & Expense Trend
            </div>
        </x-slot>

            @php
            // Cast values to float and prepare data
            $revenueTrendData = array_map('floatval', $data['revenueTrend'] ?? []);
            $expenseTrendData = array_map('floatval', $data['expenseTrend'] ?? []);
            $hasData = count($revenueTrendData) > 0 || count($expenseTrendData) > 0;
            
                // Merge dates from both trends
                $allDates = array_unique(array_merge(
                array_keys($revenueTrendData),
                array_keys($expenseTrendData)
                ));
                sort($allDates);
                
            // Prepare chart data
            $chartLabels = array_map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'), $allDates);
            $revenueValues = array_map(fn($d) => $revenueTrendData[$d] ?? 0, $allDates);
            $expenseValues = array_map(fn($d) => $expenseTrendData[$d] ?? 0, $allDates);
            @endphp
            
        @if($hasData)
            <div class="w-full" style="height: 300px;">
                <canvas id="revenueExpenseTrendChart"></canvas>
            </div>
            
            <script>
                (function() {
                    // Wait for Chart.js to be available
                    function waitForChart(callback, maxAttempts = 50) {
                        let attempts = 0;
                        const check = function() {
                            if (typeof Chart !== 'undefined') {
                                callback();
                            } else if (attempts < maxAttempts) {
                                attempts++;
                                setTimeout(check, 100);
                            }
                        };
                        check();
                    }
                    
                    function initRevenueExpenseChart() {
                        const ctx = document.getElementById('revenueExpenseTrendChart');
                        if (!ctx) return;
                        
                        // Destroy existing chart if it exists
                        if (window.revenueExpenseChart instanceof Chart) {
                            window.revenueExpenseChart.destroy();
                        }
                        
                        const isDark = document.documentElement.classList.contains('dark');
                    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
                    const textColor = isDark ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';
                    
                    window.revenueExpenseChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: @json(array_values($chartLabels)),
                            datasets: [
                                {
                                    label: 'Revenue',
                                    data: @json(array_values($revenueValues)),
                                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                                    borderColor: '#16a34a',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                    borderSkipped: false,
                                    hoverBackgroundColor: 'rgba(34, 197, 94, 1)'
                                },
                                {
                                    label: 'Expenses',
                                    data: @json(array_values($expenseValues)),
                                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                                    borderColor: '#dc2626',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                    borderSkipped: false,
                                    hoverBackgroundColor: 'rgba(239, 68, 68, 1)'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: textColor,
                                        usePointStyle: true,
                                        pointStyle: 'rectRounded',
                                        padding: 20,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    enabled: true,
                                    backgroundColor: isDark ? 'rgba(30, 30, 30, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                                    titleColor: isDark ? '#fff' : '#1f2937',
                                    bodyColor: isDark ? '#d1d5db' : '#4b5563',
                                    borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                                    borderWidth: 1,
                                    padding: 12,
                                    displayColors: true,
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += 'RWF ' + new Intl.NumberFormat().format(context.parsed.y);
                                            }
                                            return label;
                                        },
                                        afterBody: function(context) {
                                            const revenueVal = context[0]?.parsed?.y || 0;
                                            const expenseVal = context[1]?.parsed?.y || 0;
                                            const diff = revenueVal - expenseVal;
                                            const prefix = diff >= 0 ? '↑ Profit: ' : '↓ Loss: ';
                                            return '\n' + prefix + 'RWF ' + new Intl.NumberFormat().format(Math.abs(diff));
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: textColor,
                                        maxRotation: 45,
                                        minRotation: 0
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: gridColor,
                                        drawBorder: false
                                    },
                                    ticks: {
                                        color: textColor,
                                        callback: function(value) {
                                            if (value >= 1000000) {
                                                return 'RWF ' + (value / 1000000).toFixed(1) + 'M';
                                            } else if (value >= 1000) {
                                                return 'RWF ' + (value / 1000).toFixed(0) + 'K';
                                            }
                                            return 'RWF ' + value;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
                    
                    // Initialize on page load
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() {
                            waitForChart(initRevenueExpenseChart);
                        });
                    } else {
                        waitForChart(initRevenueExpenseChart);
                    }
                    
                    // Reinitialize on Livewire updates
                    document.addEventListener('livewire:navigated', function() {
                        waitForChart(initRevenueExpenseChart);
                    });
                    
                    // Handle Livewire v3 morphing
                    if (typeof Livewire !== 'undefined') {
                        Livewire.hook('morph.updated', () => {
                            waitForChart(initRevenueExpenseChart);
                        });
                    }
                })();
            </script>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>No trend data available for this period</p>
            </div>
        @endif
    </x-filament::section>

    {{-- Period Summary --}}
    <div class="mt-6 text-center text-sm text-gray-500">
        Showing data from {{ \Carbon\Carbon::parse($data['startDate'])->format('M d, Y') }} 
        to {{ \Carbon\Carbon::parse($data['endDate'])->format('M d, Y') }}
    </div>

    {{-- Monthly Expense Projection Section --}}
    <div class="mt-12 border-t border-gray-200 dark:border-gray-700 pt-10">
        <div class="space-y-10">
            {{-- Section Header --}}
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 mt-6">
                    <x-heroicon-o-calculator class="w-6 h-6 text-primary-500" />
                    Monthly Expense Projection
                </h2>

                {{-- Month Selector and Egg Price Input --}}
                <div class="mt-6 flex flex-wrap items-start gap-4">
                    <div class="w-full max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Month</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="projectionMonth">
                                @php
                                    $start = now()->subMonths(12);
                                    $end = now()->addMonths(6);
                                @endphp
                                @while($start <= $end)
                                    <option value="{{ $start->format('Y-m') }}">{{ $start->format('F Y') }}</option>
                                    @php $start->addMonth(); @endphp
                                @endwhile
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        <div class="h-5"></div>
                    </div>
                    <div class="w-full max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Egg Price (RWF)
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-1">(optional)</span>
                        </label>
                        <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                            <span class="fi-input-wrp-prefix flex items-center whitespace-nowrap px-3 text-gray-500 dark:text-gray-400 text-sm border-e border-gray-200 dark:border-white/10">RWF</span>
                            <input 
                                type="number" 
                                wire:model.live.debounce.500ms="customEggPrice"
                                placeholder="e.g. 150"
                                min="1"
                                step="1"
                                class="fi-input block w-full border-none bg-transparent py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 ps-3 pe-3"
                            />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Custom price per egg for projection</p>
                    </div>
                    <div class="w-full max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Feed Price/kg (RWF)
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-1">(optional)</span>
                        </label>
                        <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                            <span class="fi-input-wrp-prefix flex items-center whitespace-nowrap px-3 text-gray-500 dark:text-gray-400 text-sm border-e border-gray-200 dark:border-white/10">RWF</span>
                            <input 
                                type="number" 
                                wire:model.live.debounce.500ms="customFeedPricePerKg"
                                placeholder="e.g. 710"
                                min="1"
                                step="1"
                                class="fi-input block w-full border-none bg-transparent py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 ps-3 pe-3"
                            />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Custom feed price per kg for projection</p>
                    </div>
                </div>
            </div>

            @php
                $projection = $this->getExpenseProjection();
                $categoryLabels = [
                    'labor' => ['label' => 'Labor', 'color' => 'bg-blue-500'],
                    'utilities' => ['label' => 'Utilities', 'color' => 'bg-purple-500'],
                    'veterinary' => ['label' => 'Veterinary', 'color' => 'bg-red-500'],
                    'maintenance' => ['label' => 'Maintenance', 'color' => 'bg-gray-500'],
                    'transport' => ['label' => 'Transport', 'color' => 'bg-green-500'],
                    'packaging' => ['label' => 'Packaging', 'color' => 'bg-pink-500'],
                    'other' => ['label' => 'Other', 'color' => 'bg-indigo-500'],
                ];
            @endphp

            {{-- Income Projection Card --}}
            <div>
                <div class="h-4"></div>
                <x-filament::section>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Projected Income (Eggs)</p>
                            <p class="text-3xl font-bold text-success-600 dark:text-success-400">
                                RWF {{ number_format($projection['projectedIncome'], 0) }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                @if($projection['isCurrentOrFuture'])
                                    {{ number_format($projection['incomeProjection']['totalProjectedEggs'], 0) }} eggs @ RWF {{ number_format($projection['incomeProjection']['avgPricePerEgg'], 0) }}/egg
                                    @if($projection['incomeProjection']['usingCustomPrice'])
                                        <span class="ml-1 px-1.5 py-0.5 text-xs font-semibold bg-primary-200 dark:bg-primary-800 text-primary-800 dark:text-primary-200 rounded">Custom</span>
                                    @endif
                                @else
                                    Actual revenue: RWF {{ number_format($projection['actualRevenue'], 0) }}
                                @endif
                            </p>
                        </div>
                        <div class="p-4 rounded-full bg-success-100 dark:bg-success-900">
                            <x-heroicon-o-arrow-trending-up class="w-8 h-8 text-success-600 dark:text-success-400" />
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Expense Summary Cards --}}
            <div>
                <div class="h-4"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- Salaries --}}
                    <x-filament::section>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Salaries</p>
                                @if($projection['isCurrentOrFuture'])
                                    <p class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">
                                        RWF {{ number_format($projection['salaryProjection'], 0) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $projection['employeeCount'] }} active employee(s)
                                    </p>
                                @else
                                    <p class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">
                                        RWF {{ number_format($projection['actualSalaryExpense'], 0) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">Actual paid</p>
                                @endif
                            </div>
                            <div class="p-3 rounded-full bg-cyan-100 dark:bg-cyan-900">
                                <x-heroicon-o-user-group class="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
                            </div>
                        </div>
                    </x-filament::section>

                    {{-- Feed Expenses --}}
                    <x-filament::section>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Feed
                                    @if($projection['isCurrentOrFuture'])
                                        <span class="ml-1 px-1.5 py-0.5 text-xs font-semibold bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded">+{{ $projection['feedProjection']['tolerancePercent'] }}%</span>
                                        @if($projection['feedProjection']['usingCustomFeedPrice'])
                                            <span class="ml-1 px-1.5 py-0.5 text-xs font-semibold bg-primary-200 dark:bg-primary-800 text-primary-800 dark:text-primary-200 rounded">Custom</span>
                                        @endif
                                    @endif
                                </p>
                                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                    RWF {{ number_format($projection['feedExpense'], 0) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    @if($projection['isCurrentOrFuture'])
                                        {{ number_format($projection['feedProjection']['totalProjectedKg'], 0) }} kg @ {{ number_format($projection['feedProjection']['pricePerKgWithTolerance'], 0) }}/kg
                                    @else
                                        Actual expenses
                                    @endif
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-amber-100 dark:bg-amber-900">
                                <x-heroicon-o-archive-box class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>
                    </x-filament::section>

                    {{-- Other Expenses --}}
                    <x-filament::section>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Other Expenses</p>
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    RWF {{ number_format($projection['otherExpenses'], 0) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ count($projection['otherExpensesBreakdown']) }} categories
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                                <x-heroicon-o-rectangle-stack class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                    </x-filament::section>

                    {{-- Total --}}
                    <x-filament::section>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Expenses</p>
                                @if($projection['isCurrentOrFuture'])
                                    <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">
                                        RWF {{ number_format($projection['totalProjected'], 0) }}
                                    </p>
                                    <p class="text-xs text-warning-600 mt-1 flex items-center gap-1">
                                        <x-heroicon-m-exclamation-triangle class="w-3 h-3" />
                                        Projected total
                                    </p>
                                @else
                                    <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">
                                        RWF {{ number_format($projection['totalActual'], 0) }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">Actual total</p>
                                @endif
                            </div>
                            <div class="p-3 rounded-full bg-danger-100 dark:bg-danger-900">
                                <x-heroicon-o-calculator class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                            </div>
                        </div>
                    </x-filament::section>
                </div>
            </div>

            {{-- Detailed Breakdown Section --}}
            <div>
                <div class="h-4"></div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Employee Salary Details (for current/future months) --}}
                    @if($projection['isCurrentOrFuture'] && count($projection['employeeDetails']) > 0)
                        <x-filament::section>
                            <x-slot name="heading">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-user-group class="w-5 h-5 text-cyan-500" />
                                    Salary Breakdown - {{ $projection['selectedMonth'] }}
                                </div>
                            </x-slot>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b dark:border-gray-700">
                                            <th class="text-left py-2 font-medium text-gray-500">Employee</th>
                                            <th class="text-left py-2 font-medium text-gray-500">Position</th>
                                            <th class="text-right py-2 font-medium text-gray-500">Salary</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($projection['employeeDetails'] as $employee)
                                            <tr class="border-b dark:border-gray-700">
                                                <td class="py-2 text-gray-900 dark:text-white">{{ $employee['employee_name'] }}</td>
                                                <td class="py-2 text-gray-600 dark:text-gray-400">{{ $employee['position'] ?? '-' }}</td>
                                                <td class="py-2 text-right font-medium text-cyan-600">RWF {{ number_format($employee['salary_amount'], 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-50 dark:bg-gray-800">
                                            <td colspan="2" class="py-2 font-bold text-gray-900 dark:text-white">Total Salaries</td>
                                            <td class="py-2 text-right font-bold text-cyan-600">RWF {{ number_format($projection['salaryProjection'], 0) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </x-filament::section>
                    @endif

                    {{-- Feed Projection Breakdown (for current/future months) --}}
                    @if($projection['isCurrentOrFuture'] && count($projection['feedProjection']['batchDetails']) > 0)
                        <x-filament::section>
                            <x-slot name="heading">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-archive-box class="w-5 h-5 text-amber-500" />
                                    Feed Projection - {{ $projection['selectedMonth'] }}
                                </div>
                            </x-slot>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b dark:border-gray-700">
                                            <th class="text-left py-2 font-medium text-gray-500">Batch</th>
                                            <th class="text-left py-2 font-medium text-gray-500">Status</th>
                                            <th class="text-right py-2 font-medium text-gray-500">Birds</th>
                                            <th class="text-right py-2 font-medium text-gray-500">g/bird/day</th>
                                            <th class="text-right py-2 font-medium text-gray-500">Monthly (kg)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($projection['feedProjection']['batchDetails'] as $batch)
                                            <tr class="border-b dark:border-gray-700">
                                                <td class="py-2 text-gray-900 dark:text-white">
                                                    {{ $batch['batch_code'] }}
                                                    <span class="text-xs text-gray-500 block">
                                                        @if($batch['age_weeks_start'] != $batch['age_weeks'])
                                                            Week {{ $batch['age_weeks_start'] }}-{{ $batch['age_weeks'] }}
                                                        @else
                                                            Week {{ $batch['age_weeks'] }}
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="py-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                                                        @if($batch['status'] === 'laying') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                                        @elseif($batch['status'] === 'growing') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                                        @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                                        @endif">
                                                        {{ ucfirst($batch['status']) }}
                                                    </span>
                                                </td>
                                                <td class="py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($batch['bird_count']) }}</td>
                                                <td class="py-2 text-right text-gray-600 dark:text-gray-400">{{ $batch['daily_feed_g'] }}</td>
                                                <td class="py-2 text-right font-medium text-amber-600">{{ number_format($batch['monthly_feed_kg'], 1) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-50 dark:bg-gray-800">
                                            <td colspan="4" class="py-2 font-bold text-gray-900 dark:text-white">Total Feed Required</td>
                                            <td class="py-2 text-right font-bold text-amber-600">{{ number_format($projection['feedProjection']['totalProjectedKg'], 1) }} kg</td>
                                        </tr>
                                        <tr class="bg-amber-50 dark:bg-amber-900/20">
                                            <td colspan="4" class="py-2 text-gray-700 dark:text-gray-300">
                                                Price per kg
                                                @if($projection['feedProjection']['usingCustomFeedPrice'])
                                                    <span class="ml-1 px-1.5 py-0.5 text-xs font-semibold bg-primary-200 dark:bg-primary-800 text-primary-800 dark:text-primary-200 rounded">Custom</span>
                                                @else
                                                    <span class="text-xs text-gray-500 ml-1">(highest from last 3 months)</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-right font-medium text-gray-700 dark:text-gray-300">RWF {{ number_format($projection['feedProjection']['avgPricePerKg'], 0) }}</td>
                                        </tr>
                                        @if($projection['feedProjection']['usingCustomFeedPrice'])
                                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                                <td colspan="4" class="py-2 text-gray-500 dark:text-gray-400 text-sm">
                                                    Historical highest price
                                                    <span class="text-xs text-gray-400 ml-1">(for reference)</span>
                                                </td>
                                                <td class="py-2 text-right text-sm text-gray-500 dark:text-gray-400">RWF {{ number_format($projection['feedProjection']['historicalHighestPricePerKg'], 0) }}</td>
                                            </tr>
                                        @endif
                                        <tr class="bg-amber-50 dark:bg-amber-900/20">
                                            <td colspan="4" class="py-2 text-gray-700 dark:text-gray-300">
                                                Price per kg (+{{ $projection['feedProjection']['tolerancePercent'] }}% tolerance)
                                            </td>
                                            <td class="py-2 text-right font-medium text-amber-600">RWF {{ number_format($projection['feedProjection']['pricePerKgWithTolerance'], 0) }}</td>
                                        </tr>
                                        <tr class="bg-amber-100 dark:bg-amber-900/40">
                                            <td colspan="4" class="py-2 font-bold text-gray-900 dark:text-white">Projected Feed Cost</td>
                                            <td class="py-2 text-right font-bold text-gray-700 dark:text-gray-300">RWF {{ number_format($projection['feedProjection']['projectedCost'], 0) }}</td>
                                        </tr>
                                        <tr class="bg-amber-200 dark:bg-amber-800/60">
                                            <td colspan="4" class="py-2 font-bold text-gray-900 dark:text-white">
                                                Feed Cost (+{{ $projection['feedProjection']['tolerancePercent'] }}% tolerance)
                                            </td>
                                            <td class="py-2 text-right font-bold text-amber-700 dark:text-amber-400">RWF {{ number_format($projection['feedProjection']['projectedCostWithTolerance'], 0) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                <p>* Feed targets based on Rearing (weeks 1-18) and Production (weeks 18+) standards</p>
                                <p>* Uses highest week in the month for conservative expense estimation</p>
                            </div>
                        </x-filament::section>
                    @endif

                    {{-- Income Projection Breakdown (for current/future months with laying batches) --}}
                    @if($projection['isCurrentOrFuture'] && count($projection['incomeProjection']['batchDetails']) > 0)
                        <x-filament::section>
                            <x-slot name="heading">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-success-500" />
                                    Income Projection - {{ $projection['selectedMonth'] }}
                                </div>
                            </x-slot>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b dark:border-gray-700">
                                            <th class="text-left py-2 font-medium text-gray-500">Batch</th>
                                            <th class="text-right py-2 font-medium text-gray-500">Hens</th>
                                            <th class="text-right py-2 font-medium text-gray-500">Production %</th>
                                            <th class="text-right py-2 font-medium text-gray-500">Monthly Eggs</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($projection['incomeProjection']['batchDetails'] as $batch)
                                            <tr class="border-b dark:border-gray-700">
                                                <td class="py-2 text-gray-900 dark:text-white">
                                                    {{ $batch['batch_code'] }}
                                                    <span class="text-xs text-gray-500 block">Week {{ $batch['age_weeks'] }}</span>
                                                </td>
                                                <td class="py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($batch['hen_count']) }}</td>
                                                <td class="py-2 text-right">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-300">
                                                        {{ $batch['production_pct'] }}%
                                                    </span>
                                                </td>
                                                <td class="py-2 text-right font-medium text-success-600">{{ number_format($batch['monthly_eggs']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-50 dark:bg-gray-800">
                                            <td colspan="3" class="py-2 font-bold text-gray-900 dark:text-white">Total Eggs Expected</td>
                                            <td class="py-2 text-right font-bold text-success-600">{{ number_format($projection['incomeProjection']['totalProjectedEggs']) }}</td>
                                        </tr>
                                        <tr class="bg-success-50 dark:bg-success-900/20">
                                            <td colspan="3" class="py-2 text-gray-700 dark:text-gray-300">
                                                Price per egg
                                                @if($projection['incomeProjection']['usingCustomPrice'])
                                                    <span class="ml-1 px-1.5 py-0.5 text-xs font-semibold bg-primary-200 dark:bg-primary-800 text-primary-800 dark:text-primary-200 rounded">Custom</span>
                                                @else
                                                    <span class="text-xs text-gray-500 ml-1">(based on last 3 months sales)</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-right font-medium text-gray-700 dark:text-gray-300">RWF {{ number_format($projection['incomeProjection']['avgPricePerEgg'], 0) }}</td>
                                        </tr>
                                        @if($projection['incomeProjection']['usingCustomPrice'])
                                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                                <td colspan="3" class="py-2 text-gray-500 dark:text-gray-400 text-sm">
                                                    Historical avg. price
                                                    <span class="text-xs text-gray-400 ml-1">(for reference)</span>
                                                </td>
                                                <td class="py-2 text-right text-sm text-gray-500 dark:text-gray-400">RWF {{ number_format($projection['incomeProjection']['historicalAvgPricePerEgg'], 0) }}</td>
                                            </tr>
                                        @endif
                                        <tr class="bg-success-100 dark:bg-success-900/40">
                                            <td colspan="3" class="py-2 font-bold text-gray-900 dark:text-white">Projected Income</td>
                                            <td class="py-2 text-right font-bold text-success-600">RWF {{ number_format($projection['incomeProjection']['projectedIncome'], 0) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                <p>* Production targets based on ISA Brown laying standards (weeks 18+)</p>
                            </div>
                        </x-filament::section>
                    @endif

                    {{-- Other Expenses Breakdown --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-rectangle-stack class="w-5 h-5 text-purple-500" />
                                Other Expenses Breakdown - {{ $projection['selectedMonth'] }}
                            </div>
                        </x-slot>

                        @if(count($projection['otherExpensesBreakdown']) > 0)
                            <div class="space-y-3">
                                @php
                                    $maxOther = count($projection['otherExpensesBreakdown']) > 0 ? max($projection['otherExpensesBreakdown']) : 1;
                                @endphp
                                @foreach($projection['otherExpensesBreakdown'] as $category => $amount)
                                    @php
                                        $catInfo = $categoryLabels[$category] ?? ['label' => ucfirst($category), 'color' => 'bg-gray-500'];
                                        $percentage = $projection['otherExpenses'] > 0 ? ($amount / $projection['otherExpenses']) * 100 : 0;
                                        $barWidth = $maxOther > 0 ? ($amount / $maxOther) * 100 : 0;
                                    @endphp
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $catInfo['label'] }}</span>
                                            <span class="text-gray-500">RWF {{ number_format($amount, 0) }}</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="{{ $catInfo['color'] }} h-2 rounded-full transition-all duration-300" style="width: {{ $barWidth }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <x-heroicon-o-banknotes class="w-12 h-12 mx-auto mb-2 opacity-50" />
                                <p>No other expenses recorded for this month</p>
                            </div>
                        @endif
                    </x-filament::section>
                </div>
            </div>

            {{-- Monthly Financial Summary --}}
            <div>
                <div class="h-4"></div>
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-document-chart-bar class="w-5 h-5 text-gray-400" />
                            Financial Summary for {{ $projection['selectedMonth'] }}
                        </div>
                    </x-slot>

                    @php
                        $totalExpenses = $projection['isCurrentOrFuture'] ? $projection['totalProjected'] : $projection['totalActual'];
                        $totalIncome = $projection['projectedIncome'];
                        $netProfit = $totalIncome - $totalExpenses;
                        $salaryAmount = $projection['isCurrentOrFuture'] ? $projection['salaryProjection'] : $projection['actualSalaryExpense'];
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            {{-- Income Section --}}
                            <thead>
                                <tr class="border-b-2 border-success-300 dark:border-success-700">
                                    <th colspan="3" class="text-left py-3 font-bold text-success-700 dark:text-success-400 text-lg">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-arrow-trending-up class="w-5 h-5" />
                                            INCOME
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b dark:border-gray-700 bg-success-50 dark:bg-success-900/20">
                                    <td class="py-3 text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-3 h-3 rounded bg-success-500"></div>
                                        Egg Sales (Projected)
                                        @if($projection['incomeProjection']['usingCustomPrice'])
                                            <span class="px-1.5 py-0.5 text-xs font-semibold bg-primary-200 dark:bg-primary-800 text-primary-800 dark:text-primary-200 rounded">@ RWF {{ number_format($projection['incomeProjection']['avgPricePerEgg'], 0) }}/egg</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right font-medium text-success-600">
                                        RWF {{ number_format($totalIncome, 0) }}
                                    </td>
                                    <td class="py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ number_format($projection['incomeProjection']['totalProjectedEggs']) }} eggs
                                    </td>
                                </tr>
                            </tbody>

                            {{-- Expenses Section --}}
                            <thead>
                                <tr class="border-b-2 border-danger-300 dark:border-danger-700">
                                    <th colspan="3" class="text-left py-3 font-bold text-danger-700 dark:text-danger-400 text-lg pt-6">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-arrow-trending-down class="w-5 h-5" />
                                            EXPENSES
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b dark:border-gray-700 bg-cyan-50 dark:bg-cyan-900/20">
                                    <td class="py-3 text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-3 h-3 rounded bg-cyan-500"></div>
                                        Salaries
                                    </td>
                                    <td class="py-3 text-right font-medium text-gray-900 dark:text-white">
                                        RWF {{ number_format($salaryAmount, 0) }}
                                    </td>
                                    <td class="py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ $totalExpenses > 0 ? number_format(($salaryAmount / $totalExpenses) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 bg-amber-50 dark:bg-amber-900/20">
                                    <td class="py-3 text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-3 h-3 rounded bg-amber-500"></div>
                                        Feed
                                        @if($projection['isCurrentOrFuture'])
                                            <span class="px-1.5 py-0.5 text-xs font-semibold bg-amber-200 dark:bg-amber-700 text-amber-800 dark:text-amber-200 rounded">+{{ $projection['feedProjection']['tolerancePercent'] }}%</span>
                                            @if($projection['feedProjection']['usingCustomFeedPrice'])
                                                <span class="px-1.5 py-0.5 text-xs font-semibold bg-primary-200 dark:bg-primary-800 text-primary-800 dark:text-primary-200 rounded">@ RWF {{ number_format($projection['feedProjection']['avgPricePerKg'], 0) }}/kg</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="py-3 text-right font-medium text-gray-900 dark:text-white">
                                        RWF {{ number_format($projection['feedExpense'], 0) }}
                                    </td>
                                    <td class="py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ $totalExpenses > 0 ? number_format(($projection['feedExpense'] / $totalExpenses) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                                <tr class="border-b dark:border-gray-700 bg-purple-50 dark:bg-purple-900/20">
                                    <td class="py-3 text-gray-900 dark:text-white flex items-center gap-2">
                                        <div class="w-3 h-3 rounded bg-purple-500"></div>
                                        Other Expenses
                                    </td>
                                    <td class="py-3 text-right font-medium text-gray-900 dark:text-white">
                                        RWF {{ number_format($projection['otherExpenses'], 0) }}
                                    </td>
                                    <td class="py-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ $totalExpenses > 0 ? number_format(($projection['otherExpenses'] / $totalExpenses) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                                <tr class="bg-gray-100 dark:bg-gray-800 border-b-2 dark:border-gray-600">
                                    <td class="py-3 font-bold text-gray-900 dark:text-white">
                                        Total Expenses
                                    </td>
                                    <td class="py-3 text-right font-bold text-danger-600">
                                        RWF {{ number_format($totalExpenses, 0) }}
                                    </td>
                                    <td class="py-3 text-right font-bold text-gray-600 dark:text-gray-400">
                                        100%
                                    </td>
                                </tr>
                            </tbody>

                            {{-- Net Profit/Loss --}}
                            <tfoot>
                                <tr class="{{ $netProfit >= 0 ? 'bg-success-100 dark:bg-success-900/40' : 'bg-danger-100 dark:bg-danger-900/40' }}">
                                    <td class="py-4 font-bold text-gray-900 dark:text-white text-lg">
                                        NET {{ $netProfit >= 0 ? 'PROFIT' : 'LOSS' }}
                                        @if($projection['isCurrentOrFuture'])
                                            <span class="text-xs font-normal text-warning-600 ml-2">(Projected)</span>
                                        @endif
                                    </td>
                                    <td class="py-4 text-right font-bold text-xl {{ $netProfit >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                        RWF {{ number_format(abs($netProfit), 0) }}
                                    </td>
                                    <td class="py-4 text-right font-bold {{ $netProfit >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                        {{ $totalIncome > 0 ? number_format(($netProfit / $totalIncome) * 100, 1) : 0 }}% margin
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($projection['isCurrentOrFuture'])
                        <div class="mt-6 p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg border border-warning-200 dark:border-warning-800">
                            <div class="flex items-start gap-3">
                                <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-warning-600 dark:text-warning-400 flex-shrink-0 mt-0.5" />
                                <div class="text-sm text-warning-800 dark:text-warning-200">
                                    <p class="font-medium">Projection Notice</p>
                                    <p class="mt-1">
                                        @if($projection['incomeProjection']['usingCustomPrice'])
                                            Income is projected based on production targets and <strong>custom egg price of RWF {{ number_format($projection['incomeProjection']['avgPricePerEgg'], 0) }}/egg</strong>.
                                        @else
                                            Income is projected based on production targets and historical egg prices (falls back to 150 RWF/egg).
                                        @endif
                                        @if($projection['feedProjection']['usingCustomFeedPrice'])
                                            Feed costs are calculated using <strong>custom feed price of RWF {{ number_format($projection['feedProjection']['avgPricePerKg'], 0) }}/kg</strong>.
                                        @else
                                            Feed costs are based on rearing/production targets and historical highest price.
                                        @endif
                                        Actual results may vary.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </x-filament::section>
            </div>
        </div>
    </div>

    {{-- Bottom spacing --}}
    <div class="h-8"></div>
</x-filament-panels::page>

