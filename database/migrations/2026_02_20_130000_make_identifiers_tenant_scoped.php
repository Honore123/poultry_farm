<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

        $defaultTenantId = DB::table('tenants')->where('name', 'Kabajogo Farm')->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($defaultTenantId) {
            DB::table('batches')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('inventory_items')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }

        $dropIndex('batches', 'batches_code_unique');
        $dropIndex('inventory_items', 'inventory_items_sku_unique');

        $ensureUnique('batches', ['tenant_id', 'code'], 'batches_tenant_code_unique');
        $ensureUnique('inventory_items', ['tenant_id', 'sku'], 'inventory_items_tenant_sku_unique');
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

        $dropIndex('batches', 'batches_tenant_code_unique');
        $dropIndex('inventory_items', 'inventory_items_tenant_sku_unique');

        $ensureUnique('batches', ['code'], 'batches_code_unique');
        $ensureUnique('inventory_items', ['sku'], 'inventory_items_sku_unique');
    }
};
