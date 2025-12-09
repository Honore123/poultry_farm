<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employee salary configuration
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_name');
            $table->string('employee_phone')->nullable();
            $table->string('position')->nullable();
            $table->decimal('salary_amount', 12, 2);
            $table->unsignedTinyInteger('payment_day')->default(1); // Day of month (1-28)
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Null means still active
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Salary payments (history)
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_salary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payment_date');
            $table->string('payment_period'); // e.g., "December 2025"
            $table->decimal('base_salary', 12, 2);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable(); // cash, bank transfer, mobile money
            $table->string('reference')->nullable(); // Transaction reference
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_salary_id', 'payment_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('employee_salaries');
    }
};

