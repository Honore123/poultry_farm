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

            [18,  3.0, 42.4,  1.3,  94, 73.84,   0,  0.0,  0.7, 73.84, 99.9, 1475],
            [19, 11.0, 46.1,  5.1, 101, 19.95,   1,  0.0,  1.4, 30.79, 99.8, 1535],
            [20, 31.1, 49.2, 15.3, 105,  6.86,   3,  0.2,  2.1, 13.87, 99.7, 1605],
            [21, 49.2, 51.8, 25.5, 108,  4.24,   7,  0.3,  2.8,  8.66, 99.6, 1660],
            [22, 66.8, 54.0, 36.1, 110,  3.05,  11,  0.6,  3.6,  6.23, 99.5, 1715],
            [23, 82.2, 55.8, 45.9, 112,  2.44,  17,  0.9,  4.4,  4.89, 99.4, 1745],
            [24, 90.3, 57.2, 51.7, 114,  2.20,  23,  1.3,  5.2,  4.12, 99.3, 1765],
            [25, 93.0, 58.4, 54.3, 116,  2.14,  30,  1.6,  6.0,  3.66, 99.2, 1780],
            [26, 94.0, 59.4, 55.8, 118,  2.11,  36,  2.0,  6.8,  3.37, 99.1, 1795],
            [27, 94.8, 60.1, 57.0, 120,  2.11,  43,  2.4,  7.6,  3.16, 99.0, 1805],
            [28, 95.4, 60.7, 57.9, 122,  2.11,  49,  2.8,  8.5,  3.01, 98.9, 1815],
            [29, 95.7, 61.1, 58.5, 123,  2.10,  56,  3.2,  9.3,  2.90, 98.8, 1825],
            [30, 96.0, 61.5, 59.0, 124,  2.10,  63,  3.6, 10.2,  2.81, 98.8, 1835],
             // 👉 PEAK / MID LAY (fill remaining weekly rows from tables)
            // Example a few more rows to illustrate:
            [31, 96.0, 61.7, 59.3, 125, 2.10,  69,  4.0, 11.1, 2.74, 98.7, 1845],
            [32, 96.0, 62.0, 59.5, 125, 2.10,  76,  4.5, 11.9, 2.68, 98.6, 1850],
            [33, 95.9, 62.1, 59.6, 125, 2.10,  83,  4.9, 12.8, 2.63, 98.5, 1858],
            [34, 95.8, 62.3, 59.7, 125, 2.09,  89,  5.3, 13.6, 2.59, 98.4, 1860],
            [35, 95.6, 62.5, 59.7, 125, 2.09,  96,  5.7, 14.5, 2.55, 98.3, 1863],
            [36, 95.5, 62.6, 59.8, 125, 2.09, 102,  6.1, 15.4, 2.52, 98.2, 1870],
            [37, 95.2, 62.8, 59.8, 125, 2.09, 109,  6.5, 16.2, 2.49, 98.1, 1870],
            [38, 95.0, 62.9, 59.8, 125, 2.09, 115,  6.9, 17.1, 2.47, 98.0, 1873],

            
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
