<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    @php
        $data = $this->getFinancialData();
    @endphp

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

        @if(count($data['revenueTrend']) > 0 || count($data['expenseTrend']) > 0)
            @php
                // Merge dates from both trends
                $allDates = array_unique(array_merge(
                    array_keys($data['revenueTrend']),
                    array_keys($data['expenseTrend'])
                ));
                sort($allDates);
                
                $maxValue = max(
                    count($data['revenueTrend']) > 0 ? max($data['revenueTrend']) : 0,
                    count($data['expenseTrend']) > 0 ? max($data['expenseTrend']) : 0
                );
            @endphp
            
            <div class="overflow-x-auto">
                <div class="min-w-full" style="min-width: {{ count($allDates) * 60 }}px">
                    <div class="flex items-end gap-2 h-48">
                        @foreach($allDates as $date)
                            @php
                                $revenue = $data['revenueTrend'][$date] ?? 0;
                                $expense = $data['expenseTrend'][$date] ?? 0;
                                $revenueHeight = $maxValue > 0 ? ($revenue / $maxValue) * 100 : 0;
                                $expenseHeight = $maxValue > 0 ? ($expense / $maxValue) * 100 : 0;
                            @endphp
                            <div class="flex-1 flex flex-col items-center">
                                <div class="flex gap-1 items-end h-40 w-full justify-center">
                                    <div 
                                        class="w-4 bg-success-500 rounded-t transition-all duration-300" 
                                        style="height: {{ $revenueHeight }}%"
                                        title="Revenue: RWF {{ number_format($revenue, 0) }}"
                                    ></div>
                                    <div 
                                        class="w-4 bg-danger-500 rounded-t transition-all duration-300" 
                                        style="height: {{ $expenseHeight }}%"
                                        title="Expense: RWF {{ number_format($expense, 0) }}"
                                    ></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-top-left">
                                    {{ \Carbon\Carbon::parse($date)->format('M d') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center gap-6 mt-8">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-success-500 rounded"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Revenue</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-danger-500 rounded"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Expenses</span>
                </div>
            </div>
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
</x-filament-panels::page>

