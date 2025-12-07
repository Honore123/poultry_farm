<x-filament-panels::page>
    @php 
        $stats = $this->getStats(); 
        $targets = $stats['targets'];
    @endphp

    {{-- Phase & Age Header --}}
    <div class="mb-6 p-4 rounded-lg bg-gradient-to-r {{ $targets['phase'] === 'Rearing' ? 'from-blue-500/10 to-cyan-500/10' : 'from-emerald-500/10 to-teal-500/10' }}">
        <div class="flex items-center justify-between">
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $targets['phase'] === 'Rearing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' }}">
                    {{ $targets['phase'] }} Phase
                </span>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">Week {{ $stats['age_weeks'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['age_days'] }} days old</p>
            </div>
        </div>
    </div>

    {{-- Main Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Birds Alive</p>
                <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $stats['birds_alive'] }}</p>
                <p class="text-xs text-gray-400">of {{ $stats['birds_placed'] }} placed</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Livability</p>
                <p class="text-2xl font-bold {{ (float) rtrim($stats['livability'], '%') >= 95 ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}">{{ $stats['livability'] }}</p>
                @if($targets['livability_target_pct'])
                    <p class="text-xs {{ (float) rtrim($stats['livability'], '%') >= $targets['livability_target_pct'] ? 'text-success-500' : 'text-danger-500' }}">
                        Target: {{ number_format($targets['livability_target_pct'], 1) }}%
                    </p>
                @else
                    <p class="text-xs text-gray-400">{{ $stats['mortality_count'] }} deaths</p>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Laying Rate (7d)</p>
                <p class="text-2xl font-bold {{ $stats['avg_laying_rate_raw'] >= 80 ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}">{{ $stats['avg_laying_rate'] }}</p>
                @if($targets['production_target_pct'])
                    <p class="text-xs {{ $stats['avg_laying_rate_raw'] >= $targets['production_target_pct'] ? 'text-success-500' : 'text-danger-500' }}">
                        Target: {{ number_format($targets['production_target_pct'], 1) }}%
                    </p>
                @else
                    <p class="text-xs text-gray-400">{{ $stats['total_eggs'] }} total eggs</p>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Feed/Egg (7d)</p>
                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['feed_per_egg'] }}</p>
                <p class="text-xs text-gray-400">{{ $stats['total_feed'] }} total</p>
            </div>
        </x-filament::section>
    </div>

    {{-- Target Comparison Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Feed Target --}}
        <x-filament::section>
            <x-slot name="heading">
                🌾 Feed per Bird/Day
            </x-slot>
            <div class="space-y-3">
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Actual (7d avg)</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['feed_per_bird_per_day'] }}</p>
                    </div>
                    @if($targets['feed_target_min_g'])
                        <div class="text-right">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Target</p>
                            <p class="text-lg font-semibold text-gray-600 dark:text-gray-300">
                                @if($targets['feed_target_min_g'] != $targets['feed_target_max_g'])
                                    {{ number_format($targets['feed_target_min_g']) }}-{{ number_format($targets['feed_target_max_g']) }}g
                                @else
                                    {{ number_format($targets['feed_target_min_g']) }}g
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
                @if($targets['feed_target_min_g'])
                    @php
                        $feedActual = $stats['feed_per_bird_per_day_raw'];
                        $feedMid = ($targets['feed_target_min_g'] + $targets['feed_target_max_g']) / 2;
                        $feedWithinRange = $feedActual >= $targets['feed_target_min_g'] && $feedActual <= $targets['feed_target_max_g'];
                        $feedPercent = $feedMid > 0 ? min(100, max(0, ($feedActual / $feedMid) * 50)) : 0;
                    @endphp
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $feedWithinRange ? 'bg-success-500' : 'bg-warning-500' }}" style="width: {{ $feedPercent }}%"></div>
                    </div>
                    <p class="text-xs text-center {{ $feedWithinRange ? 'text-success-600' : 'text-warning-600' }}">
                        {{ $feedWithinRange ? '✓ Within target range' : ($feedActual < $targets['feed_target_min_g'] ? '⚠ Below target' : '⚠ Above target') }}
                    </p>
                @endif
            </div>
        </x-filament::section>

        {{-- Weight Target --}}
        <x-filament::section>
            <x-slot name="heading">
                ⚖️ Body Weight
            </x-slot>
            <div class="space-y-3">
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Latest Sample</p>
                        @if($stats['latest_weight'])
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['latest_weight']['avg_weight_g']) }}g</p>
                            <p class="text-xs text-gray-400">{{ $stats['latest_weight']['date'] }}</p>
                        @else
                            <p class="text-2xl font-bold text-gray-400">No data</p>
                        @endif
                    </div>
                    @if($targets['weight_target_min_g'])
                        <div class="text-right">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Target</p>
                            <p class="text-lg font-semibold text-gray-600 dark:text-gray-300">
                                @if($targets['weight_target_min_g'] != $targets['weight_target_max_g'])
                                    {{ number_format($targets['weight_target_min_g']) }}-{{ number_format($targets['weight_target_max_g']) }}g
                                @else
                                    {{ number_format($targets['weight_target_min_g']) }}g
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
                @if($stats['latest_weight'] && $targets['weight_target_min_g'])
                    @php
                        $weightActual = $stats['latest_weight']['avg_weight_g'];
                        $weightMid = ($targets['weight_target_min_g'] + $targets['weight_target_max_g']) / 2;
                        $weightWithinRange = $weightActual >= $targets['weight_target_min_g'] && $weightActual <= $targets['weight_target_max_g'];
                        $weightPercent = $weightMid > 0 ? min(100, max(0, ($weightActual / $weightMid) * 50)) : 0;
                    @endphp
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $weightWithinRange ? 'bg-success-500' : 'bg-warning-500' }}" style="width: {{ $weightPercent }}%"></div>
                    </div>
                    <p class="text-xs text-center {{ $weightWithinRange ? 'text-success-600' : 'text-warning-600' }}">
                        {{ $weightWithinRange ? '✓ Within target range' : ($weightActual < $targets['weight_target_min_g'] ? '⚠ Underweight' : '⚠ Overweight') }}
                    </p>
                @endif
                @if($stats['latest_weight'] && $stats['latest_weight']['sample_size'])
                    <p class="text-xs text-gray-500 text-center">Sample size: {{ $stats['latest_weight']['sample_size'] }} birds</p>
                @endif
            </div>
        </x-filament::section>

        {{-- Production Target (only in production phase) --}}
        @if($targets['phase'] === 'Production')
        <x-filament::section>
            <x-slot name="heading">
                🥚 Production
            </x-slot>
            <div class="space-y-3">
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Actual (7d avg)</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['avg_laying_rate'] }}</p>
                    </div>
                    @if($targets['production_target_pct'])
                        <div class="text-right">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Target</p>
                            <p class="text-lg font-semibold text-gray-600 dark:text-gray-300">{{ number_format($targets['production_target_pct'], 1) }}%</p>
                        </div>
                    @endif
                </div>
                @if($targets['production_target_pct'])
                    @php
                        $prodActual = $stats['avg_laying_rate_raw'];
                        $prodTarget = $targets['production_target_pct'];
                        $prodPercent = min(100, max(0, ($prodActual / 100) * 100));
                        $prodOnTarget = $prodActual >= ($prodTarget * 0.95); // Within 5% is good
                    @endphp
                    <div class="relative w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $prodOnTarget ? 'bg-success-500' : 'bg-warning-500' }}" style="width: {{ $prodPercent }}%"></div>
                        <div class="absolute top-0 h-2 w-0.5 bg-gray-800 dark:bg-gray-200" style="left: {{ $prodTarget }}%"></div>
                    </div>
                    <p class="text-xs text-center {{ $prodOnTarget ? 'text-success-600' : 'text-warning-600' }}">
                        @if($prodActual >= $prodTarget)
                            ✓ Meeting target (+{{ number_format($prodActual - $prodTarget, 1) }}%)
                        @else
                            ⚠ Below target (-{{ number_format($prodTarget - $prodActual, 1) }}%)
                        @endif
                    </p>
                @endif
                @if($targets['egg_weight_target_g'])
                    <p class="text-xs text-gray-500 text-center">Egg weight target: {{ number_format($targets['egg_weight_target_g'], 1) }}g</p>
                @endif
            </div>
        </x-filament::section>
        @else
        {{-- Rearing Phase Info --}}
        <x-filament::section>
            <x-slot name="heading">
                📈 Rearing Progress
            </x-slot>
            <div class="space-y-3">
                <div class="text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Weeks until Production</p>
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ max(0, 18 - $stats['age_weeks']) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Production starts ~Week 18</p>
                </div>
                @php $rearingProgress = min(100, ($stats['age_weeks'] / 18) * 100); @endphp
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="h-3 rounded-full bg-gradient-to-r from-blue-500 to-emerald-500" style="width: {{ $rearingProgress }}%"></div>
                </div>
                <p class="text-xs text-center text-gray-500">{{ number_format($rearingProgress, 0) }}% through rearing</p>
            </div>
        </x-filament::section>
        @endif
    </div>

    {{-- Charts Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Laying Rate Chart with Target --}}
        <x-filament::section>
            <x-slot name="heading">
                📈 Laying Rate % vs Target
            </x-slot>
            <div style="height: 280px;">
                <canvas id="layingRateChart"></canvas>
            </div>
        </x-filament::section>

        {{-- Feed Consumption Chart with Target --}}
        <x-filament::section>
            <x-slot name="heading">
                🌾 Daily Feed (kg) vs Target
            </x-slot>
            <div style="height: 280px;">
                <canvas id="feedConsumptionChart"></canvas>
            </div>
        </x-filament::section>

        {{-- Egg Production Chart with Target --}}
        <x-filament::section>
            <x-slot name="heading">
                🥚 Daily Egg Production vs Target
            </x-slot>
            <div style="height: 280px;">
                <canvas id="eggProductionChart"></canvas>
            </div>
        </x-filament::section>

        {{-- Feed per Egg Chart --}}
        <x-filament::section>
            <x-slot name="heading">
                ⚡ Feed Efficiency (grams/egg)
            </x-slot>
            <div style="height: 280px;">
                <canvas id="feedPerEggChart"></canvas>
            </div>
        </x-filament::section>

        {{-- Mortality Chart --}}
        <x-filament::section class="lg:col-span-2">
            <x-slot name="heading">
                💀 Mortality Trend
            </x-slot>
            <div style="height: 280px;">
                <canvas id="mortalityChart"></canvas>
            </div>
        </x-filament::section>
    </div>

    {{-- Prepare chart data in PHP --}}
    @php
        // Safely get all chart data with error handling
        try {
            $layingData = $this->getLayingRateData();
        } catch (\Exception $e) {
            $layingData = ['labels' => [], 'data' => [], 'target' => []];
        }
        
        try {
            $feedData = $this->getFeedConsumptionData();
        } catch (\Exception $e) {
            $feedData = ['labels' => [], 'data' => [], 'target' => []];
        }
        
        try {
            $eggData = $this->getEggProductionData();
        } catch (\Exception $e) {
            $eggData = ['labels' => [], 'total' => [], 'good' => [], 'cracked' => [], 'dirty' => [], 'soft' => [], 'target' => []];
        }
        
        try {
            $feedEggData = $this->getFeedPerEggData();
        } catch (\Exception $e) {
            $feedEggData = ['labels' => [], 'data' => []];
        }
        
        try {
            $mortalityData = $this->getMortalityData();
        } catch (\Exception $e) {
            $mortalityData = ['labels' => [], 'daily' => [], 'cumulative' => [], 'expected_mortality' => []];
        }
        
        // Prepare JSON-safe data
        $chartData = [
            'laying' => [
                'labels' => $layingData['labels'] ?? [],
                'data' => $layingData['data'] ?? [],
                'target' => $layingData['target'] ?? [],
            ],
            'feed' => [
                'labels' => $feedData['labels'] ?? [],
                'data' => $feedData['data'] ?? [],
                'target' => $feedData['target'] ?? [],
            ],
            'eggs' => [
                'labels' => $eggData['labels'] ?? [],
                'good' => $eggData['good'] ?? [],
                'cracked' => $eggData['cracked'] ?? [],
                'dirty' => $eggData['dirty'] ?? [],
                'soft' => $eggData['soft'] ?? [],
                'target' => $eggData['target'] ?? [],
            ],
            'feedPerEgg' => [
                'labels' => $feedEggData['labels'] ?? [],
                'data' => $feedEggData['data'] ?? [],
            ],
            'mortality' => [
                'labels' => $mortalityData['labels'] ?? [],
                'daily' => $mortalityData['daily'] ?? [],
                'cumulative' => $mortalityData['cumulative'] ?? [],
            ],
        ];
    @endphp

    {{-- Chart.js Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            // Chart data from PHP
            var chartData = {!! json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
            
            // Store chart instances to destroy them before recreating
            var chartInstances = {};

            function destroyExistingCharts() {
                Object.keys(chartInstances).forEach(function(key) {
                    if (chartInstances[key]) {
                        chartInstances[key].destroy();
                    }
                });
                chartInstances = {};
            }

            function initCharts() {
                try {
                    destroyExistingCharts();

                    var isDark = document.documentElement.classList.contains('dark');
                    var textColor = isDark ? '#9CA3AF' : '#6B7280';
                    var gridColor = isDark ? '#374151' : '#E5E7EB';

                    // Laying Rate Chart
                    if (chartData.laying.labels.length > 0 && document.getElementById('layingRateChart')) {
                        chartInstances.layingRate = new Chart(document.getElementById('layingRateChart'), {
                            type: 'line',
                            data: {
                                labels: chartData.laying.labels,
                                datasets: [
                                    {
                                        label: 'Actual Laying Rate %',
                                        data: chartData.laying.data,
                                        borderColor: '#10B981',
                                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                        fill: true,
                                        tension: 0.3,
                                        pointRadius: 3
                                    },
                                    {
                                        label: 'Target %',
                                        data: chartData.laying.target,
                                        borderColor: '#6366F1',
                                        borderDash: [5, 5],
                                        fill: false,
                                        tension: 0,
                                        pointRadius: 0
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { intersect: false, mode: 'index' },
                                scales: {
                                    y: { min: 0, max: 100, ticks: { color: textColor }, grid: { color: gridColor } },
                                    x: { ticks: { color: textColor }, grid: { color: gridColor } }
                                },
                                plugins: { 
                                    legend: { labels: { color: textColor }, position: 'bottom' },
                                    tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + (ctx.raw !== null ? ctx.raw.toFixed(1) + '%' : 'N/A'); } } }
                                }
                            }
                        });
                    }

                    // Feed Consumption Chart
                    if (chartData.feed.labels.length > 0 && document.getElementById('feedConsumptionChart')) {
                        chartInstances.feedConsumption = new Chart(document.getElementById('feedConsumptionChart'), {
                            type: 'bar',
                            data: {
                                labels: chartData.feed.labels,
                                datasets: [
                                    {
                                        label: 'Actual Feed (kg)',
                                        data: chartData.feed.data,
                                        backgroundColor: '#F59E0B',
                                        borderRadius: 4
                                    },
                                    {
                                        label: 'Target Feed (kg)',
                                        data: chartData.feed.target,
                                        type: 'line',
                                        borderColor: '#6366F1',
                                        borderDash: [5, 5],
                                        fill: false,
                                        tension: 0,
                                        pointRadius: 0
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { intersect: false, mode: 'index' },
                                scales: {
                                    y: { min: 0, ticks: { color: textColor }, grid: { color: gridColor } },
                                    x: { ticks: { color: textColor }, grid: { color: gridColor } }
                                },
                                plugins: { 
                                    legend: { labels: { color: textColor }, position: 'bottom' },
                                    tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + (ctx.raw !== null ? ctx.raw.toFixed(1) + ' kg' : 'N/A'); } } }
                                }
                            }
                        });
                    }

                    // Egg Production Chart
                    if (chartData.eggs.labels.length > 0 && document.getElementById('eggProductionChart')) {
                        chartInstances.eggProduction = new Chart(document.getElementById('eggProductionChart'), {
                            type: 'bar',
                            data: {
                                labels: chartData.eggs.labels,
                                datasets: [
                                    { label: 'Good', data: chartData.eggs.good, backgroundColor: '#10B981', stack: 'stack', borderRadius: 2 },
                                    { label: 'Cracked', data: chartData.eggs.cracked, backgroundColor: '#EF4444', stack: 'stack' },
                                    { label: 'Dirty', data: chartData.eggs.dirty, backgroundColor: '#F59E0B', stack: 'stack' },
                                    { label: 'Soft', data: chartData.eggs.soft, backgroundColor: '#8B5CF6', stack: 'stack' },
                                    {
                                        label: 'Target',
                                        data: chartData.eggs.target,
                                        type: 'line',
                                        borderColor: '#6366F1',
                                        borderDash: [5, 5],
                                        fill: false,
                                        tension: 0,
                                        pointRadius: 0
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { intersect: false, mode: 'index' },
                                scales: {
                                    y: { stacked: true, ticks: { color: textColor }, grid: { color: gridColor } },
                                    x: { stacked: true, ticks: { color: textColor }, grid: { color: gridColor } }
                                },
                                plugins: { legend: { labels: { color: textColor }, position: 'bottom' } }
                            }
                        });
                    }

                    // Feed per Egg Chart
                    if (chartData.feedPerEgg.labels.length > 0 && document.getElementById('feedPerEggChart')) {
                        chartInstances.feedPerEgg = new Chart(document.getElementById('feedPerEggChart'), {
                            type: 'line',
                            data: {
                                labels: chartData.feedPerEgg.labels,
                                datasets: [{
                                    label: 'Grams per Egg',
                                    data: chartData.feedPerEgg.data,
                                    borderColor: '#3B82F6',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    fill: true,
                                    tension: 0.3,
                                    pointRadius: 3
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: { min: 0, ticks: { color: textColor }, grid: { color: gridColor } },
                                    x: { ticks: { color: textColor }, grid: { color: gridColor } }
                                },
                                plugins: { 
                                    legend: { labels: { color: textColor } },
                                    tooltip: { callbacks: { label: function(ctx) { return 'Feed/Egg: ' + ctx.raw + 'g'; } } }
                                }
                            }
                        });
                    }

                    // Mortality Chart
                    if (chartData.mortality.labels.length > 0 && document.getElementById('mortalityChart')) {
                        chartInstances.mortality = new Chart(document.getElementById('mortalityChart'), {
                            type: 'bar',
                            data: {
                                labels: chartData.mortality.labels,
                                datasets: [
                                    { 
                                        label: 'Daily Deaths', 
                                        data: chartData.mortality.daily, 
                                        backgroundColor: '#EF4444', 
                                        yAxisID: 'y',
                                        borderRadius: 4
                                    },
                                    { 
                                        label: 'Cumulative', 
                                        data: chartData.mortality.cumulative, 
                                        type: 'line', 
                                        borderColor: '#6B7280', 
                                        backgroundColor: 'transparent', 
                                        yAxisID: 'y1',
                                        tension: 0.3
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { intersect: false, mode: 'index' },
                                scales: {
                                    y: { position: 'left', ticks: { color: textColor }, grid: { color: gridColor }, title: { display: true, text: 'Daily', color: textColor } },
                                    y1: { position: 'right', ticks: { color: textColor }, grid: { display: false }, title: { display: true, text: 'Cumulative', color: textColor } },
                                    x: { ticks: { color: textColor }, grid: { color: gridColor } }
                                },
                                plugins: { legend: { labels: { color: textColor }, position: 'bottom' } }
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error initializing charts:', e);
                }
            }

            function waitForChartJs(callback) {
                if (typeof Chart !== 'undefined') {
                    callback();
                } else {
                    setTimeout(function() { waitForChartJs(callback); }, 50);
                }
            }

            // Initialize charts when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    waitForChartJs(initCharts);
                });
            } else {
                setTimeout(function() {
                    waitForChartJs(initCharts);
                }, 100);
            }

            // Re-init on Livewire navigation
            document.addEventListener('livewire:navigated', function() {
                setTimeout(function() {
                    waitForChartJs(initCharts);
                }, 100);
            });
        })();
    </script>
</x-filament-panels::page>
