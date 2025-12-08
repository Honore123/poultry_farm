<?php

namespace App\Filament\Resources\BatchResource\Pages;

use App\Filament\Resources\BatchResource;
use App\Models\Batch;
use App\Models\DailyFeedIntake;
use App\Models\DailyProduction;
use App\Models\MortalityLog;
use App\Models\ProductionTarget;
use App\Models\RearingTarget;
use App\Models\WeightSample;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class BatchDashboard extends Page
{
    use InteractsWithRecord;

    protected static string $resource = BatchResource::class;

    protected static string $view = 'filament.resources.batch-resource.pages.batch-dashboard';

    // Cache for targets
    protected ?RearingTarget $rearingTarget = null;
    protected ?ProductionTarget $productionTarget = null;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string | Htmlable
    {
        return "Dashboard: {$this->record->code}";
    }

    public function getSubheading(): string | Htmlable | null
    {
        return "{$this->record->breed} • {$this->record->farm?->name} / {$this->record->house?->name}";
    }

    /**
     * Get batch age in weeks from placement date
     */
    public function getAgeInWeeks(): int
    {
        return (int) $this->record->placement_date->diffInWeeks(now());
    }

    /**
     * Get batch age in days from placement date
     */
    public function getAgeInDays(): int
    {
        return $this->record->placement_date->diffInDays(now());
    }

    /**
     * Determine if batch is in rearing phase (typically < 18 weeks) or production phase
     */
    public function isRearingPhase(): bool
    {
        return $this->getAgeInWeeks() < 18;
    }

    /**
     * Get the appropriate target for current batch age
     */
    public function getCurrentTargets(): array
    {
        $week = $this->getAgeInWeeks();
        $ageDays = $this->getAgeInDays();

        if ($this->isRearingPhase()) {
            // Rearing phase - use RearingTarget
            $target = RearingTarget::where('week', $week)
                ->orWhere(function ($q) use ($ageDays) {
                    $q->where('age_days_from', '<=', $ageDays)
                      ->where('age_days_to', '>=', $ageDays);
                })
                ->first();

            if ($target) {
                return [
                    'phase' => 'Rearing',
                    'week' => $week,
                    'feed_target_min_g' => $target->daily_feed_min_g,
                    'feed_target_max_g' => $target->daily_feed_max_g,
                    'weight_target_min_g' => $target->body_weight_min_g,
                    'weight_target_max_g' => $target->body_weight_max_g,
                    'production_target_pct' => null, // No production during rearing
                    'livability_target_pct' => null,
                    'egg_weight_target_g' => null,
                    'fcr_target' => null,
                ];
            }
        } else {
            // Production phase - use ProductionTarget
            $target = ProductionTarget::where('week', $week)->first();

            if ($target) {
                return [
                    'phase' => 'Production',
                    'week' => $week,
                    'feed_target_min_g' => $target->feed_intake_per_day_g,
                    'feed_target_max_g' => $target->feed_intake_per_day_g,
                    'weight_target_min_g' => $target->body_weight_g,
                    'weight_target_max_g' => $target->body_weight_g,
                    'production_target_pct' => $target->hen_day_production_pct,
                    'livability_target_pct' => $target->livability_pct,
                    'egg_weight_target_g' => $target->avg_egg_weight_g,
                    'fcr_target' => $target->fcr_week,
                ];
            }
        }

        return [
            'phase' => $this->isRearingPhase() ? 'Rearing' : 'Production',
            'week' => $week,
            'feed_target_min_g' => null,
            'feed_target_max_g' => null,
            'weight_target_min_g' => null,
            'weight_target_max_g' => null,
            'production_target_pct' => null,
            'livability_target_pct' => null,
            'egg_weight_target_g' => null,
            'fcr_target' => null,
        ];
    }

    /**
     * Get targets for the last 30 days (for chart lines)
     */
    public function getTargetsTimeSeries(): array
    {
        $startDate = now()->subDays(30);
        $targets = [];

        for ($i = 0; $i <= 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $week = (int) $this->record->placement_date->diffInWeeks($date);
            $ageDays = $this->record->placement_date->diffInDays($date);

            $feedTarget = null;
            $productionTarget = null;
            $weightTarget = null;

            if ($week < 18) {
                // Rearing phase
                $target = RearingTarget::where('week', $week)->first();
                if ($target) {
                    $feedTarget = ($target->daily_feed_min_g + $target->daily_feed_max_g) / 2;
                    $weightTarget = ($target->body_weight_min_g + $target->body_weight_max_g) / 2;
                }
            } else {
                // Production phase
                $target = ProductionTarget::where('week', $week)->first();
                if ($target) {
                    $feedTarget = $target->feed_intake_per_day_g;
                    $productionTarget = $target->hen_day_production_pct;
                    $weightTarget = $target->body_weight_g;
                }
            }

            $targets[] = [
                'date' => $date->format('M d'),
                'week' => $week,
                'feed_target_g' => $feedTarget,
                'production_target_pct' => $productionTarget,
                'weight_target_g' => $weightTarget,
            ];
        }

        return $targets;
    }

    public function getLayingRateData(): array
    {
        try {
            $data = DB::table('v_daily_laying_rate')
                ->where('batch_id', $this->record->id)
                ->orderBy('date')
                ->limit(30)
                ->get();

            // Get production targets for each date
            $targetData = [];
            foreach ($data as $row) {
                $week = (int) $this->record->placement_date->diffInWeeks(\Carbon\Carbon::parse($row->date));
                $target = ProductionTarget::where('week', $week)->first();
                $targetData[] = $target?->hen_day_production_pct ?? null;
            }

            return [
                'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
                'data' => $data->pluck('laying_rate_pct')->map(fn($v) => round((float) $v, 1))->toArray(),
                'target' => $targetData,
            ];
        } catch (\Exception $e) {
            return ['labels' => [], 'data' => [], 'target' => []];
        }
    }

    public function getFeedPerEggData(): array
    {
        try {
            $data = DB::table('v_feed_per_egg')
                ->where('batch_id', $this->record->id)
                ->whereNotNull('kg_per_egg')
                ->orderBy('date')
                ->limit(30)
                ->get();

            return [
                'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
                'data' => $data->pluck('kg_per_egg')->map(fn($v) => round((float) $v * 1000, 0))->toArray(),
            ];
        } catch (\Exception $e) {
            return ['labels' => [], 'data' => []];
        }
    }

    public function getMortalityData(): array
    {
        try {
            $data = MortalityLog::where('batch_id', $this->record->id)
                ->orderBy('date')
                ->selectRaw('date, SUM(count) as total')
                ->groupBy('date')
                ->limit(30)
                ->get();

            $cumulative = 0;
            $cumulativeData = $data->map(function ($item) use (&$cumulative) {
                $cumulative += $item->total;
                return $cumulative;
            })->toArray();

            // Calculate expected livability targets
            $livabilityTargets = [];
            foreach ($data as $row) {
                $week = (int) $this->record->placement_date->diffInWeeks(\Carbon\Carbon::parse($row->date));
                $target = ProductionTarget::where('week', $week)->first();
                // Convert livability % to expected mortality count
                if ($target && $target->livability_pct) {
                    $expectedMortality = $this->record->placement_qty * (100 - $target->livability_pct) / 100;
                    $livabilityTargets[] = round($expectedMortality);
                } else {
                    $livabilityTargets[] = null;
                }
            }

            return [
                'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
                'daily' => $data->pluck('total')->toArray(),
                'cumulative' => $cumulativeData,
                'expected_mortality' => $livabilityTargets,
            ];
        } catch (\Exception $e) {
            return ['labels' => [], 'daily' => [], 'cumulative' => [], 'expected_mortality' => []];
        }
    }

    public function getEggProductionData(): array
    {
        try {
            $data = DailyProduction::where('batch_id', $this->record->id)
                ->orderBy('date')
                ->limit(30)
                ->get();

            // Calculate expected egg production based on target
            $birdsAlive = $this->getBirdsAlive();
            $targetData = [];
            foreach ($data as $row) {
                $week = (int) $this->record->placement_date->diffInWeeks(\Carbon\Carbon::parse($row->date));
                $target = ProductionTarget::where('week', $week)->first();
                if ($target && $target->hen_day_production_pct) {
                    $expectedEggs = round($birdsAlive * $target->hen_day_production_pct / 100);
                    $targetData[] = $expectedEggs;
                } else {
                    $targetData[] = null;
                }
            }

            return [
                'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
                'total' => $data->pluck('eggs_total')->toArray(),
                'good' => $data->map(fn($r) => $r->eggs_total - $r->eggs_cracked - $r->eggs_dirty - $r->eggs_soft)->toArray(),
                'cracked' => $data->pluck('eggs_cracked')->toArray(),
                'dirty' => $data->pluck('eggs_dirty')->toArray(),
                'soft' => $data->pluck('eggs_soft')->toArray(),
                'target' => $targetData,
            ];
        } catch (\Exception $e) {
            return ['labels' => [], 'total' => [], 'good' => [], 'cracked' => [], 'dirty' => [], 'soft' => [], 'target' => []];
        }
    }

    public function getFeedConsumptionData(): array
    {
        try {
            $data = DailyFeedIntake::where('batch_id', $this->record->id)
                ->orderBy('date')
                ->selectRaw('date, SUM(kg_given) as total_kg')
                ->groupBy('date')
                ->limit(30)
                ->get();

            // Calculate target feed per day (in kg for the flock)
            $birdsAlive = $this->getBirdsAlive();
            $targetData = [];
            foreach ($data as $row) {
                $week = (int) $this->record->placement_date->diffInWeeks(\Carbon\Carbon::parse($row->date));
                
                if ($week < 18) {
                    $target = RearingTarget::where('week', $week)->first();
                    if ($target) {
                        $avgFeedG = ($target->daily_feed_min_g + $target->daily_feed_max_g) / 2;
                        $targetData[] = round($birdsAlive * $avgFeedG / 1000, 1); // Convert to kg
                    } else {
                        $targetData[] = null;
                    }
                } else {
                    $target = ProductionTarget::where('week', $week)->first();
                    if ($target && $target->feed_intake_per_day_g) {
                        $targetData[] = round($birdsAlive * $target->feed_intake_per_day_g / 1000, 1);
                    } else {
                        $targetData[] = null;
                    }
                }
            }

            return [
                'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
                'data' => $data->pluck('total_kg')->map(fn($v) => round((float) $v, 1))->toArray(),
                'target' => $targetData,
            ];
        } catch (\Exception $e) {
            return ['labels' => [], 'data' => [], 'target' => []];
        }
    }

    /**
     * Get current birds alive
     */
    public function getBirdsAlive(): int
    {
        $totalMortality = MortalityLog::where('batch_id', $this->record->id)->sum('count');
        return $this->record->placement_qty - $totalMortality;
    }

    /**
     * Get latest weight sample data
     */
    public function getLatestWeightSample(): ?array
    {
        $sample = WeightSample::where('batch_id', $this->record->id)
            ->orderBy('date', 'desc')
            ->first();

        if (!$sample) {
            return null;
        }

        return [
            'date' => $sample->date->format('M d, Y'),
            'avg_weight_g' => round($sample->avg_weight_g, 0),
            'sample_size' => $sample->sample_size,
        ];
    }

    public function getStats(): array
    {
        try {
            $totalMortality = MortalityLog::where('batch_id', $this->record->id)->sum('count');
            $birdsAlive = $this->record->placement_qty - $totalMortality;
            $mortalityRate = $this->record->placement_qty > 0 
                ? round(($totalMortality / $this->record->placement_qty) * 100, 2) 
                : 0;

            $ageInWeeks = $this->getAgeInWeeks();
            $ageInDays = $this->getAgeInDays();

            $last7DaysProduction = DailyProduction::where('batch_id', $this->record->id)
                ->whereBetween('date', [now()->subDays(7), now()])
                ->get();
            
            $avgEggs = $last7DaysProduction->avg('eggs_total') ?? 0;
            $avgLayingRate = $birdsAlive > 0 ? round(($avgEggs / $birdsAlive) * 100, 1) : 0;

            $last7DaysFeed = DailyFeedIntake::where('batch_id', $this->record->id)
                ->whereBetween('date', [now()->subDays(7), now()])
                ->sum('kg_given');
            
            $totalEggs7Days = $last7DaysProduction->sum('eggs_total');
            $feedPerEgg = $totalEggs7Days > 0 ? round(($last7DaysFeed / $totalEggs7Days) * 1000, 0) : 0;

            // Calculate feed per bird per day (in grams)
            $daysWithFeed = DailyFeedIntake::where('batch_id', $this->record->id)
                ->whereBetween('date', [now()->subDays(7), now()])
                ->distinct('date')
                ->count('date');
            $feedPerBirdPerDay = ($birdsAlive > 0 && $daysWithFeed > 0) 
                ? round(($last7DaysFeed * 1000) / ($birdsAlive * $daysWithFeed), 1) 
                : 0;

            $totalEggsAllTime = DailyProduction::where('batch_id', $this->record->id)->sum('eggs_total');
            $totalFeedAllTime = DailyFeedIntake::where('batch_id', $this->record->id)->sum('kg_given');

            // Get current targets
            $targets = $this->getCurrentTargets();

            // Get latest weight sample
            $latestWeight = $this->getLatestWeightSample();

            // Calculate livability (% of birds still alive)
            $livability = $this->record->placement_qty > 0 
                ? round(($birdsAlive / $this->record->placement_qty) * 100, 2) 
                : 0;

            return [
                'birds_placed' => number_format($this->record->placement_qty),
                'birds_alive' => number_format($birdsAlive),
                'birds_alive_raw' => $birdsAlive,
                'mortality_count' => number_format($totalMortality),
                'mortality_rate' => $mortalityRate . '%',
                'mortality_rate_raw' => $mortalityRate,
                'livability' => $livability . '%',
                'age_weeks' => $ageInWeeks,
                'age_days' => $ageInDays,
                'avg_laying_rate' => $avgLayingRate . '%',
                'avg_laying_rate_raw' => $avgLayingRate,
                'feed_per_egg' => $feedPerEgg . 'g',
                'feed_per_egg_raw' => $feedPerEgg,
                'feed_per_bird_per_day' => $feedPerBirdPerDay . 'g',
                'feed_per_bird_per_day_raw' => $feedPerBirdPerDay,
                'total_eggs' => number_format($totalEggsAllTime),
                'total_feed' => number_format($totalFeedAllTime, 0) . ' kg',
                // Targets
                'targets' => $targets,
                'latest_weight' => $latestWeight,
            ];
        } catch (\Exception $e) {
            // Return default values if there's an error
            return [
                'birds_placed' => '0',
                'birds_alive' => '0',
                'birds_alive_raw' => 0,
                'mortality_count' => '0',
                'mortality_rate' => '0%',
                'mortality_rate_raw' => 0,
                'livability' => '100%',
                'age_weeks' => 0,
                'age_days' => 0,
                'avg_laying_rate' => '0%',
                'avg_laying_rate_raw' => 0,
                'feed_per_egg' => '0g',
                'feed_per_egg_raw' => 0,
                'feed_per_bird_per_day' => '0g',
                'feed_per_bird_per_day_raw' => 0,
                'total_eggs' => '0',
                'total_feed' => '0 kg',
                'targets' => [
                    'phase' => 'Rearing',
                    'week' => 0,
                    'feed_target_min_g' => null,
                    'feed_target_max_g' => null,
                    'weight_target_min_g' => null,
                    'weight_target_max_g' => null,
                    'production_target_pct' => null,
                    'livability_target_pct' => null,
                    'egg_weight_target_g' => null,
                    'fcr_target' => null,
                ],
                'latest_weight' => null,
            ];
        }
    }
}

