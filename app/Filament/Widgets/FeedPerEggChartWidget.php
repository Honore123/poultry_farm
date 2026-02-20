<?php

namespace App\Filament\Widgets;

use App\Tenancy\TenantContext;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class FeedPerEggChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Feed Efficiency (g/egg) - Last 14 Days';

    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return auth()->user()?->can('view_dashboard') ?? false;
    }

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $tenantId = app(TenantContext::class)->currentTenantId();
        $data = DB::table('v_feed_per_egg')
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereBetween('date', [now()->subDays(14), now()])
            ->whereNotNull('kg_per_egg')
            ->orderBy('date')
            ->selectRaw('date, AVG(kg_per_egg) * 1000 as grams_per_egg') // Convert to grams
            ->groupBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Grams per Egg',
                    'data' => $data->pluck('grams_per_egg')->map(fn($v) => round($v, 1))->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
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
                    'ticks' => [
                        'callback' => "function(value) { return value + 'g'; }",
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
