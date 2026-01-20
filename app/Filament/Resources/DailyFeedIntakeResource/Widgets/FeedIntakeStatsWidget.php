<?php

namespace App\Filament\Resources\DailyFeedIntakeResource\Widgets;

use App\Models\DailyFeedIntake;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class FeedIntakeStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    public ?string $fromDate = null;
    public ?string $untilDate = null;

    #[On('updateFeedIntakeWidgetFilters')]
    public function updateFilters(?string $fromDate = null, ?string $untilDate = null): void
    {
        $this->fromDate = $fromDate;
        $this->untilDate = $untilDate;
    }

    protected function getStats(): array
    {
        // Build the base query with date filters
        $query = DailyFeedIntake::query();
        
        if ($this->fromDate) {
            $query->whereDate('date', '>=', $this->fromDate);
        }
        if ($this->untilDate) {
            $query->whereDate('date', '<=', $this->untilDate);
        }

        // Get totals for filtered period
        $totals = (clone $query)
            ->select([
                DB::raw('SUM(kg_given) as total_feed'),
                DB::raw('COUNT(*) as total_records'),
                DB::raw('AVG(kg_given) as avg_feed_per_record'),
                DB::raw('COUNT(DISTINCT batch_id) as batches_fed'),
            ])
            ->first();

        $totalFeed = (float) ($totals->total_feed ?? 0);
        $totalRecords = (int) ($totals->total_records ?? 0);
        $avgFeedPerRecord = $totals->avg_feed_per_record ? round($totals->avg_feed_per_record, 2) : 0;
        $batchesFed = (int) ($totals->batches_fed ?? 0);

        // Get feed breakdown by type
        $feedBreakdown = (clone $query)
            ->with('feedItem')
            ->select('feed_item_id', DB::raw('SUM(kg_given) as total_kg'))
            ->groupBy('feed_item_id')
            ->orderByDesc('total_kg')
            ->limit(3)
            ->get();

        $feedBreakdownDesc = $feedBreakdown->map(fn ($f) => 
            ($f->feedItem->name ?? 'Unknown') . ': ' . number_format($f->total_kg, 1) . 'kg'
        )->join(' | ');

        // Build period description
        $periodDesc = 'All time';
        if ($this->fromDate && $this->untilDate) {
            $periodDesc = \Carbon\Carbon::parse($this->fromDate)->format('M d') . ' - ' . \Carbon\Carbon::parse($this->untilDate)->format('M d, Y');
        } elseif ($this->fromDate) {
            $periodDesc = 'From ' . \Carbon\Carbon::parse($this->fromDate)->format('M d, Y');
        } elseif ($this->untilDate) {
            $periodDesc = 'Until ' . \Carbon\Carbon::parse($this->untilDate)->format('M d, Y');
        }

        // Calculate bags (assuming 50kg per bag)
        $bagsUsed = number_format($totalFeed / 50, 1);

        return [
            Stat::make('Total Feed Used', number_format($totalFeed, 1) . ' kg')
                ->description($periodDesc . ' (~' . $bagsUsed . ' bags)')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->chart($this->getFeedUsageTrend()),

            Stat::make('Feeding Records', number_format($totalRecords))
                ->description($batchesFed . ' batches fed')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Avg Per Feeding', number_format($avgFeedPerRecord, 2) . ' kg')
                ->description('Average per feeding record')
                ->descriptionIcon('heroicon-m-scale')
                ->color('info'),

            Stat::make('Feed Breakdown', $feedBreakdown->count() > 0 ? $feedBreakdown->first()?->feedItem?->name ?? 'N/A' : 'No data')
                ->description($feedBreakdownDesc ?: 'No feed data')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('warning'),
        ];
    }

    /**
     * Get feed usage trend for the filtered period
     */
    protected function getFeedUsageTrend(): array
    {
        $query = DailyFeedIntake::query();
        
        if ($this->fromDate && $this->untilDate) {
            $query->whereBetween('date', [$this->fromDate, $this->untilDate]);
        } else {
            $query->whereBetween('date', [now()->subDays(7), now()]);
        }

        return $query
            ->orderBy('date')
            ->groupBy('date')
            ->pluck(DB::raw('SUM(kg_given)'))
            ->map(fn ($value) => (float) $value)
            ->toArray();
    }
}

