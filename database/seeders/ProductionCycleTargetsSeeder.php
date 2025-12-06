<?php

namespace Database\Seeders;

use App\Models\ProductionCycleTarget;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionCycleTargetsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Summary table: 80 / 90 / 100 weeks.

        $rows = [
            // cycle_end_week, livability_pct, eggs_hh, egg_mass_kg,
            // avg_feed_intake_g_day, cum_fcr_kg_per_kg, body_weight_g

            [80, 94, 364, 22.7, 123, 2.30, 1940],
            [90, 93, 414, 26.0, 122, 2.33, 1955],
            [100, 92, 460, 29.0, 121, 2.37, 1975],
        ];

        foreach ($rows as $row) {
            [
                $endWeek,
                $livability,
                $eggsHh,
                $eggMassKg,
                $avgFeed,
                $cumFcr,
                $bodyWeight,
            ] = $row;

            ProductionCycleTarget::updateOrCreate(
                ['cycle_end_week' => $endWeek],
                [
                    'livability_pct'        => $livability,
                    'eggs_hh'               => $eggsHh,
                    'egg_mass_kg'           => $eggMassKg,
                    'avg_feed_intake_g_day' => $avgFeed,
                    'cum_fcr_kg_per_kg'     => $cumFcr,
                    'body_weight_g'         => $bodyWeight,
                ],
            );
        }
    }
}
