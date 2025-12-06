<?php

namespace App\Filament\Widgets;

use App\Models\DailyProduction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EggProductionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Daily Egg Production (Last 14 Days)';

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user()?->can('view_dashboard') ?? false;
    }

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = DailyProduction::query()
            ->whereBetween('date', [now()->subDays(14), now()])
            ->orderBy('date')
            ->selectRaw('date, SUM(eggs_total) as total, SUM(eggs_cracked) as cracked, SUM(eggs_dirty) as dirty, SUM(eggs_soft) as soft')
            ->groupBy('date')
            ->get();

        $goodEggs = $data->map(function ($row) {
            return $row->total - $row->cracked - $row->dirty - $row->soft;
        });

        return [
            'datasets' => [
                [
                    'label' => 'Good Eggs',
                    'data' => $goodEggs->toArray(),
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'Cracked',
                    'data' => $data->pluck('cracked')->toArray(),
                    'backgroundColor' => '#ef4444',
                ],
                [
                    'label' => 'Dirty',
                    'data' => $data->pluck('dirty')->toArray(),
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => 'Soft Shell',
                    'data' => $data->pluck('soft')->toArray(),
                    'backgroundColor' => '#8b5cf6',
                ],
            ],
            'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}

