<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_feed_per_egg');
        DB::statement('DROP VIEW IF EXISTS v_daily_laying_rate');

        DB::statement("
            CREATE VIEW v_daily_laying_rate AS
            SELECT
                b.tenant_id,
                dp.batch_id,
                dp.date,
                dp.eggs_total,
                b.placement_qty
                    - COALESCE(
                        SUM(
                            CASE
                                WHEN m.date <= dp.date THEN m.count
                                ELSE 0
                            END
                        ),
                        0
                    ) AS hens_alive,
                ROUND(
                    (CAST(dp.eggs_total AS DECIMAL(10,2)) /
                    NULLIF(
                        b.placement_qty
                            - COALESCE(
                                SUM(
                                    CASE
                                        WHEN m.date <= dp.date THEN m.count
                                        ELSE 0
                                    END
                                ),
                                0
                            ),
                        0
                    )
                    ) * 100,
                    2
                ) AS laying_rate_pct
            FROM daily_productions dp
            JOIN batches b ON b.id = dp.batch_id
            LEFT JOIN mortality_logs m ON m.batch_id = b.id
            GROUP BY
                b.tenant_id,
                dp.batch_id,
                dp.date,
                dp.eggs_total,
                b.placement_qty;
        ");

        DB::statement("
            CREATE VIEW v_feed_per_egg AS
            SELECT
                b.tenant_id,
                dfi.batch_id,
                dfi.date,
                dfi.kg_given,
                dp.eggs_total,
                ROUND(
                    dfi.kg_given / NULLIF(dp.eggs_total, 0),
                    4
                ) AS kg_per_egg
            FROM daily_feed_intakes dfi
            JOIN batches b ON b.id = dfi.batch_id
            LEFT JOIN daily_productions dp
                ON dp.batch_id = dfi.batch_id
                AND dp.date = dfi.date;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_feed_per_egg');
        DB::statement('DROP VIEW IF EXISTS v_daily_laying_rate');
    }
};
