<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_cycle_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cycle_end_week')->unique(); // 80, 90, 100
            $table->decimal('livability_pct', 5, 2)->nullable();
            $table->unsignedInteger('eggs_hh')->nullable();
            $table->decimal('egg_mass_kg', 6, 2)->nullable();
            $table->unsignedInteger('avg_feed_intake_g_day')->nullable();
            $table->decimal('cum_fcr_kg_per_kg', 5, 2)->nullable();
            $table->unsignedInteger('body_weight_g')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cycle_targets');
    }
};
