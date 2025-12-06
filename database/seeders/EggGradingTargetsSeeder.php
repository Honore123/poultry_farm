<?php

namespace Database\Seeders;

use App\Models\EggGradingTarget;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EggGradingTargetsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ⚠️ NOTE:
        // Use the egg grading tables (size distribution by week) from the guide.
        // Values below are illustrative. Replace with exact percentages.

        $rows = [
            // week, avg_egg_weight_g, pct_small, pct_medium, pct_large, pct_xl

            [18, 42.4, 80, 20, 0,  0],
            [20, 49.2, 60, 40, 0,  0],
            [24, 57.2, 20, 70, 10, 0],
            [30, 61.8,  5, 60, 30, 5],
            [40, 63.1,  2, 50, 42, 6],
            [60, 63.7,  2, 45, 47, 6],
            [80, 64.3,  1, 40, 52, 7],
            [100, 65.0, 1, 34, 59, 6],
        ];

        foreach ($rows as $row) {
            [
                $week,
                $avgWeight,
                $pctSmall,
                $pctMedium,
                $pctLarge,
                $pctXl,
            ] = $row;

            EggGradingTarget::updateOrCreate(
                ['week' => $week],
                [
                    'avg_egg_weight_g' => $avgWeight,
                    'pct_small'        => $pctSmall,
                    'pct_medium'       => $pctMedium,
                    'pct_large'        => $pctLarge,
                    'pct_xl'           => $pctXl,
                ],
            );
        }
    }
}
