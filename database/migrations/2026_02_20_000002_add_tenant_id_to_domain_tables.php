<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
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

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
