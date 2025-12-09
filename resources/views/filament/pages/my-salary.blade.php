<x-filament-panels::page>
    @if($employeeSalary)
        {{-- Salary Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Current Salary --}}
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Salary</p>
                        <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                            RWF {{ number_format($stats['current_salary'], 0) }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-primary-100 dark:bg-primary-900">
                        <x-heroicon-o-currency-dollar class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <div class="mt-2 text-sm text-gray-500">
                    Paid on day {{ $employeeSalary->payment_day }} monthly
                </div>
            </x-filament::section>

            {{-- Total Earned --}}
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Earned</p>
                        <p class="text-2xl font-bold text-success-600 dark:text-success-400">
                            RWF {{ number_format($stats['total_earned'], 0) }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-success-100 dark:bg-success-900">
                        <x-heroicon-o-banknotes class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                </div>
                <div class="mt-2 text-sm text-gray-500">
                    {{ $stats['total_payments'] }} payments received
                </div>
            </x-filament::section>

            {{-- This Year --}}
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">This Year ({{ date('Y') }})</p>
                        <p class="text-2xl font-bold text-info-600 dark:text-info-400">
                            RWF {{ number_format($stats['this_year'], 0) }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-info-100 dark:bg-info-900">
                        <x-heroicon-o-calendar class="w-6 h-6 text-info-600 dark:text-info-400" />
                    </div>
                </div>
            </x-filament::section>

            {{-- Last Payment --}}
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Payment</p>
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">
                            {{ $stats['last_payment_date'] ?? 'N/A' }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-800">
                        <x-heroicon-o-clock class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Employee Info --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user class="w-5 h-5 text-gray-400" />
                    Employment Details
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Position</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $employeeSalary->position ?? 'Not specified' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Start Date</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $employeeSalary->start_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($employeeSalary->status === 'active') bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-300
                        @elseif($employeeSalary->status === 'inactive') bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-300
                        @else bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-300
                        @endif">
                        {{ ucfirst($employeeSalary->status) }}
                    </span>
                </div>
            </div>
        </x-filament::section>

        {{-- Payment History --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-5 h-5 text-gray-400" />
                    Payment History
                </div>
            </x-slot>

            @if(count($paymentHistory) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="text-left py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="text-left py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Period</th>
                                <th class="text-right py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Base Salary</th>
                                <th class="text-right py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Bonus</th>
                                <th class="text-right py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Deductions</th>
                                <th class="text-right py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Net Pay</th>
                                <th class="text-center py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="text-left py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentHistory as $payment)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="py-3 px-2 text-gray-900 dark:text-white">{{ $payment['payment_date'] }}</td>
                                    <td class="py-3 px-2 text-gray-600 dark:text-gray-400">{{ $payment['payment_period'] }}</td>
                                    <td class="py-3 px-2 text-right text-gray-600 dark:text-gray-400">{{ $payment['base_salary'] }}</td>
                                    <td class="py-3 px-2 text-right text-success-600 dark:text-success-400">
                                        @if($payment['bonus'] !== '0 RWF')
                                            +{{ $payment['bonus'] }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-right text-danger-600 dark:text-danger-400">
                                        @if($payment['deductions'] !== '0 RWF')
                                            -{{ $payment['deductions'] }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-right font-bold text-gray-900 dark:text-white">{{ $payment['net_amount'] }}</td>
                                    <td class="py-3 px-2 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($payment['status'] === 'paid') bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-300
                                            @elseif($payment['status'] === 'pending') bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-300
                                            @else bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-300
                                            @endif">
                                            {{ ucfirst($payment['status']) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 text-gray-600 dark:text-gray-400">
                                        @if($payment['payment_method'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                                {{ ucwords(str_replace('_', ' ', $payment['payment_method'])) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <x-heroicon-o-banknotes class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p>No payment history available yet</p>
                </div>
            @endif
        </x-filament::section>
    @else
        {{-- No salary record found --}}
        <x-filament::section>
            <div class="text-center py-12">
                <x-heroicon-o-exclamation-circle class="w-16 h-16 mx-auto mb-4 text-warning-500" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Salary Record Found</h3>
                <p class="text-gray-500 dark:text-gray-400">
                    Your salary record has not been set up yet. Please contact your administrator.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

