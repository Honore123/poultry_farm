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
        Schema::create('production_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('week')->unique(); // 18–100
            $table->decimal('hen_day_production_pct', 5, 2)->nullable();
            $table->decimal('avg_egg_weight_g', 5, 2)->nullable();
            $table->decimal('egg_mass_per_day_g', 6, 2)->nullable();
            $table->unsignedInteger('feed_intake_per_day_g')->nullable();
            $table->decimal('fcr_week', 5, 2)->nullable();
            $table->unsignedInteger('cum_eggs_hh')->nullable();
            $table->decimal('cum_egg_mass_kg', 6, 2)->nullable();
            $table->decimal('cum_feed_kg', 6, 2)->nullable();
            $table->decimal('cum_fcr', 5, 2)->nullable();
            $table->decimal('livability_pct', 5, 2)->nullable();
            $table->unsignedInteger('body_weight_g')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_targets');
    }
};
