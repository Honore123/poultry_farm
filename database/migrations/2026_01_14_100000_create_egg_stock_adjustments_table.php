<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egg_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('adjustment_type', ['increase', 'decrease']);
            $table->integer('quantity'); // Number of eggs to adjust
            $table->integer('physical_count')->nullable(); // Physical count that was taken
            $table->integer('system_count')->nullable(); // What system showed at time of adjustment
            $table->string('reason'); // e.g., "Physical count variance", "Breakage", "Theft", "Expired"
            $table->text('notes')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_stock_adjustments');
    }
};

