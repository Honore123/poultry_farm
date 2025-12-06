<?php

namespace Database\Seeders;

use App\Models\ProductionTarget;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionTargetsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ⚠️ NOTE:
        // Only a subset of weeks shown as example.
        // Extend this array with all weeks (18–100) from ISA Brown production tables.

        $rows = [
            // week, hen_day_production_pct, avg_egg_weight_g, egg_mass_per_day_g,
            // feed_intake_per_day_g, fcr_week, cum_eggs_hh, cum_egg_mass_kg,
            // cum_feed_kg, cum_fcr, livability_pct, body_weight_g

            [18,  3.0, 42.4,  1.3,  94,  7.71,   1,  0.06,   0.66, 11.00, 99.5, 1438],
            [19, 11.0, 46.1,  5.1, 101,  4.03,   9,  0.36,   1.37,  3.81, 99.5, 1486],
            [20, 31.1, 49.2, 15.3, 105,  2.35,  31,  1.53,   2.58,  1.69, 99.5, 1535],
            [21, 49.2, 51.8, 25.5, 108,  2.01,  66,  3.90,   4.34,  1.12, 99.4, 1583],
            [22, 66.8, 54.0, 36.1, 110,  1.86, 113,  7.00,   6.45,  0.92, 99.4, 1632],
            [23, 82.2, 55.8, 45.9, 112,  1.82, 170, 10.89,   8.93,  0.82, 99.3, 1680],
            [24, 90.3, 57.2, 51.6, 114,  1.82, 233, 14.84,  11.69,  0.79, 99.3, 1729],
            [25, 93.0, 58.4, 54.3, 116,  1.82, 298, 18.97,  14.71,  0.78, 99.2, 1778],
            [26, 94.0, 59.4, 55.8, 118,  1.82, 365, 23.31,  17.99,  0.77, 99.2, 1826],
            // ...
            // Add more weeks from production table 1 & 2 up to week 100
        ];

        foreach ($rows as $row) {
            [
                $week,
                $hdPct,
                $eggWeight,
                $eggMassDay,
                $feedDay,
                $fcrWeek,
                $cumEggs,
                $cumMass,
                $cumFeed,
                $cumFcr,
                $livability,
                $bodyWeight,
            ] = $row;

            ProductionTarget::updateOrCreate(
                ['week' => $week],
                [
                    'hen_day_production_pct' => $hdPct,
                    'avg_egg_weight_g'       => $eggWeight,
                    'egg_mass_per_day_g'     => $eggMassDay,
                    'feed_intake_per_day_g'  => $feedDay,
                    'fcr_week'               => $fcrWeek,
                    'cum_eggs_hh'            => $cumEggs,
                    'cum_egg_mass_kg'        => $cumMass,
                    'cum_feed_kg'            => $cumFeed,
                    'cum_fcr'                => $cumFcr,
                    'livability_pct'         => $livability,
                    'body_weight_g'          => $bodyWeight,
                ],
            );
        }
    
    }
}
