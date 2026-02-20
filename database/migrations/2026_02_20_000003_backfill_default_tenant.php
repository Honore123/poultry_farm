<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $tenantId = DB::table('tenants')->where('name', 'Kabajogo Farm')->value('id');

        if (!$tenantId) {
            $tenantId = DB::table('tenants')->insertGetId([
                'name' => 'Kabajogo Farm',
                'slug' => Str::slug('Kabajogo Farm'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tables = [
            'farms',
            'houses',
            'batches',
            'daily_productions',
            'daily_feed_intakes',
            'daily_water_usages',
            'weight_samples',
            'mortality_logs',
            'vaccination_events',
            'health_treatments',
            'suppliers',
            'inventory_items',
            'inventory_lots',
            'inventory_movements',
            'customers',
            'sales_orders',
            'sales_order_items',
            'expenses',
            'attachments',
            'employee_salaries',
            'salary_payments',
            'egg_stock_adjustments',
            'sales_order_payments',
        ];

        foreach ($tables as $table) {
            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        }

        DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
    }

    public function down(): void
    {
        // No-op: data backfill should not be reversed.
    }
};
