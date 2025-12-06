<?php

namespace Database\Seeders;

use App\Models\RearingTarget;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RearingTargetsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            // week, age_days_from, age_days_to, daily_feed_min_g, daily_feed_max_g,
            // cumulative_feed_min_g, cumulative_feed_max_g, body_weight_min_g, body_weight_max_g

            [1, 0, 7,   9, 11,   63,  77,    59,  62],
            [2, 8, 14,  17, 19,  182, 210,   117, 123],
            [3, 15, 21, 24, 26,  350, 392,   171, 179],
            [4, 22, 28, 30, 32,  560, 616,   239, 251],
            [5, 29, 35, 36, 38,  812, 882,   332, 349],
            [6, 36, 42, 41, 43,  1099, 1183, 429, 451],
            [7, 43, 49, 46, 48,  1421, 1519, 527, 554],
            [8, 50, 56, 50, 52,  1771, 1883, 614, 646],
            [9, 57, 63, 53, 55,  2142, 2268, 702, 738],
            [10, 64, 70, 57, 59, 2541, 2681, 790, 830],
            [11, 71, 77, 60, 62, 2961, 3115, 878, 923],
            [12, 78, 84, 63, 65, 3402, 3570, 975, 1025],
            [13, 85, 91, 66, 68, 3864, 4046, 1068, 1122],
            [14, 92, 98, 69, 71, 4347, 4543, 1151, 1210],
            [15, 99, 105, 73, 75, 4858, 5068, 1233, 1297],
            [16, 106, 112, 78, 80, 5404, 5628, 1316, 1384],
            [17, 113, 119, 84, 86, 5992, 6230, 1389, 1461],
            [18, 120, 126, 93, 95, 6643, 6895, 1438, 1512],
        ];

        foreach ($rows as $row) {
            [
                $week,
                $ageFrom,
                $ageTo,
                $dailyMin,
                $dailyMax,
                $cumMin,
                $cumMax,
                $bwMin,
                $bwMax,
            ] = $row;

            RearingTarget::updateOrCreate(
                ['week' => $week],
                [
                    'age_days_from'          => $ageFrom,
                    'age_days_to'            => $ageTo,
                    'daily_feed_min_g'       => $dailyMin,
                    'daily_feed_max_g'       => $dailyMax,
                    'cumulative_feed_min_g'  => $cumMin,
                    'cumulative_feed_max_g'  => $cumMax,
                    'body_weight_min_g'      => $bwMin,
                    'body_weight_max_g'      => $bwMax,
                ],
            );
        }
    }
}
