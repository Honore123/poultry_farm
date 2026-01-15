<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add payment schedule option to employee_salaries (if not exists)
        if (!Schema::hasColumn('employee_salaries', 'payment_schedule')) {
            Schema::table('employee_salaries', function (Blueprint $table) {
                $table->enum('payment_schedule', ['full', 'split'])->default('full')->after('payment_day');
                $table->unsignedTinyInteger('first_half_payment_day')->nullable()->after('payment_schedule');
            });
        }

        // Add payment_type to salary_payments (if not exists)
        if (!Schema::hasColumn('salary_payments', 'payment_type')) {
            Schema::table('salary_payments', function (Blueprint $table) {
                $table->enum('payment_type', ['full', 'first_half', 'second_half'])->default('full')->after('payment_period');
            });
        }

        // Update the unique constraint to include payment_type
        // Check if the old unique constraint exists and the new one doesn't
        $indexExists = DB::select("SHOW INDEX FROM salary_payments WHERE Key_name = 'salary_payments_employee_salary_id_payment_period_unique'");
        $newIndexExists = DB::select("SHOW INDEX FROM salary_payments WHERE Key_name = 'salary_payments_unique'");
        
        if (!empty($indexExists) && empty($newIndexExists)) {
            // MySQL: use raw SQL to drop and recreate the index
            DB::statement('ALTER TABLE salary_payments DROP INDEX salary_payments_employee_salary_id_payment_period_unique, ADD UNIQUE INDEX salary_payments_unique (employee_salary_id, payment_period, payment_type)');
        }
    }

    public function down(): void
    {
        // Check if the new unique constraint exists
        $newIndexExists = DB::select("SHOW INDEX FROM salary_payments WHERE Key_name = 'salary_payments_unique'");
        
        if (!empty($newIndexExists)) {
            // Restore original unique constraint
            DB::statement('ALTER TABLE salary_payments DROP INDEX salary_payments_unique, ADD UNIQUE INDEX salary_payments_employee_salary_id_payment_period_unique (employee_salary_id, payment_period)');
        }

        // Remove the payment_type column
        if (Schema::hasColumn('salary_payments', 'payment_type')) {
            Schema::table('salary_payments', function (Blueprint $table) {
                $table->dropColumn('payment_type');
            });
        }

        // Remove the new columns from employee_salaries
        if (Schema::hasColumn('employee_salaries', 'payment_schedule')) {
            Schema::table('employee_salaries', function (Blueprint $table) {
                $table->dropColumn(['payment_schedule', 'first_half_payment_day']);
            });
        }
    }
};
