<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $getPrimaryColumns = function (string $table): array {
            $rows = DB::select(
                "SELECT COLUMN_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = 'PRIMARY'
                 ORDER BY ORDINAL_POSITION",
                [$table]
            );

            return array_map(fn ($row) => $row->COLUMN_NAME, $rows);
        };

        $ensurePrimaryKey = function (string $table, array $columns, string $name) use ($getPrimaryColumns): void {
            $current = $getPrimaryColumns($table);

            if ($current === $columns) {
                return;
            }

            if (!empty($current)) {
                DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY");
            }

            Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                $table->primary($columns, $name);
            });
        };

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

        $ensureIndex = function (string $table, array $columns, string $indexName) use ($hasIndex): void {
            if ($hasIndex($table, $indexName)) {
                return;
            }

            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        };

        if (!Schema::hasColumn('roles', 'tenant_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        if (!Schema::hasColumn('model_has_roles', 'tenant_id')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        if (!Schema::hasColumn('model_has_permissions', 'tenant_id')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        $defaultTenantId = DB::table('tenants')->where('name', 'Kabajogo Farm')->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($defaultTenantId) {
            DB::table('roles')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('model_has_roles')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('model_has_permissions')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique(['name', 'guard_name']);
            });
        } catch (\Throwable $e) {
            // Index may already be adjusted.
        }

        if (!$hasIndex('roles', 'roles_tenant_name_guard_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_tenant_name_guard_unique');
            });
        }

        $ensureIndex('model_has_roles', ['role_id'], 'model_has_roles_role_id_index');
        $ensureIndex('model_has_permissions', ['permission_id'], 'model_has_permissions_permission_id_index');

        $ensurePrimaryKey('model_has_roles', ['tenant_id', 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        $ensurePrimaryKey('model_has_permissions', ['tenant_id', 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE model_has_roles DROP PRIMARY KEY');
        } catch (\Throwable $e) {
            //
        }

        try {
            DB::statement('ALTER TABLE model_has_permissions DROP PRIMARY KEY');
        } catch (\Throwable $e) {
            //
        }

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_tenant_name_guard_unique');
            $table->unique(['name', 'guard_name']);
            $table->dropColumn('tenant_id');
        });
    }
};
