<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'feed_intake_targets' => null,
            'production_targets' => 'week',
            'rearing_targets' => 'week',
            'egg_grading_targets' => 'week',
            'production_cycle_targets' => 'cycle_end_week',
        ];

        foreach ($tables as $table => $uniqueColumn) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                });
            }
        }

        $hasIndex = function (string $table, string $indexName): bool {
            $rows = DB::select(
                "SELECT 1
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND INDEX_NAME = ?
                 LIMIT 1",
                [$table, $indexName]
            );

            return !empty($rows);
        };

        $dropIndex = function (string $table, string $indexName) use ($hasIndex): void {
            if ($hasIndex($table, $indexName)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
            }
        };

        $ensureUnique = function (string $table, array $columns, string $indexName) use ($hasIndex): void {
            if ($hasIndex($table, $indexName)) {
                return;
            }

            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->unique($columns, $indexName);
            });
        };

        $dropIndex('production_targets', 'production_targets_week_unique');
        $dropIndex('rearing_targets', 'rearing_targets_week_unique');
        $dropIndex('egg_grading_targets', 'egg_grading_targets_week_unique');
        $dropIndex('production_cycle_targets', 'production_cycle_targets_cycle_end_week_unique');

        $ensureUnique('production_targets', ['tenant_id', 'week'], 'production_targets_tenant_week_unique');
        $ensureUnique('rearing_targets', ['tenant_id', 'week'], 'rearing_targets_tenant_week_unique');
        $ensureUnique('egg_grading_targets', ['tenant_id', 'week'], 'egg_grading_targets_tenant_week_unique');
        $ensureUnique('production_cycle_targets', ['tenant_id', 'cycle_end_week'], 'production_cycle_targets_tenant_cycle_end_week_unique');

        $tenants = DB::table('tenants')->pluck('id');
        if ($tenants->isEmpty()) {
            return;
        }

        foreach ($tables as $table => $uniqueColumn) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $columns = array_values(array_diff($columns, ['id', 'tenant_id']));

            $templates = DB::table($table)
                ->whereNull('tenant_id')
                ->get($columns);

            if ($templates->isEmpty()) {
                continue;
            }

            foreach ($tenants as $tenantId) {
                foreach ($templates as $template) {
                    $data = (array) $template;
                    $data['tenant_id'] = $tenantId;

                    if ($uniqueColumn && isset($data[$uniqueColumn])) {
                        $exists = DB::table($table)
                            ->where('tenant_id', $tenantId)
                            ->where($uniqueColumn, $data[$uniqueColumn])
                            ->exists();

                        if ($exists) {
                            continue;
                        }
                    }

                    DB::table($table)->insert($data);
                }
            }
        }
    }

    public function down(): void
    {
        $hasIndex = function (string $table, string $indexName): bool {
            $rows = DB::select(
                "SELECT 1
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND INDEX_NAME = ?
                 LIMIT 1",
                [$table, $indexName]
            );

            return !empty($rows);
        };

        $dropIndex = function (string $table, string $indexName) use ($hasIndex): void {
            if ($hasIndex($table, $indexName)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
            }
        };

        $ensureUnique = function (string $table, array $columns, string $indexName) use ($hasIndex): void {
            if ($hasIndex($table, $indexName)) {
                return;
            }

            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->unique($columns, $indexName);
            });
        };

        $dropIndex('production_targets', 'production_targets_tenant_week_unique');
        $dropIndex('rearing_targets', 'rearing_targets_tenant_week_unique');
        $dropIndex('egg_grading_targets', 'egg_grading_targets_tenant_week_unique');
        $dropIndex('production_cycle_targets', 'production_cycle_targets_tenant_cycle_end_week_unique');

        $ensureUnique('production_targets', ['week'], 'production_targets_week_unique');
        $ensureUnique('rearing_targets', ['week'], 'rearing_targets_week_unique');
        $ensureUnique('egg_grading_targets', ['week'], 'egg_grading_targets_week_unique');
        $ensureUnique('production_cycle_targets', ['cycle_end_week'], 'production_cycle_targets_cycle_end_week_unique');

        $tables = [
            'feed_intake_targets',
            'production_targets',
            'rearing_targets',
            'egg_grading_targets',
            'production_cycle_targets',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('tenant_id');
                });
            }
        }
    }
};
