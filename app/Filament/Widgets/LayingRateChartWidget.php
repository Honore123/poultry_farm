<?php

namespace App\Filament\Widgets;

use App\Tenancy\TenantContext;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class LayingRateChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Laying Rate Trend (Last 14 Days)';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->can('view_dashboard') ?? false;
    }

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $tenantId = app(TenantContext::class)->currentTenantId();
        $data = DB::table('v_daily_laying_rate')
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereBetween('date', [now()->subDays(14), now()])
            ->orderBy('date')
            ->selectRaw('date, AVG(laying_rate_pct) as avg_rate, SUM(eggs_total) as total_eggs')
            ->groupBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Laying Rate %',
                    'data' => $data->pluck('avg_rate')->map(fn($v) => round($v, 1))->toArray(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'min' => 0,
                    'max' => 100,
                    'ticks' => [
                        'callback' => "function(value) { return value + '%'; }",
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}
