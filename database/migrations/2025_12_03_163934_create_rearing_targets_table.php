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
        Schema::create('rearing_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('week')->unique(); // 1–18
            $table->unsignedInteger('age_days_from')->nullable();
            $table->unsignedInteger('age_days_to')->nullable();
            $table->unsignedInteger('daily_feed_min_g')->nullable();
            $table->unsignedInteger('daily_feed_max_g')->nullable();
            $table->unsignedInteger('cumulative_feed_min_g')->nullable();
            $table->unsignedInteger('cumulative_feed_max_g')->nullable();
            $table->unsignedInteger('body_weight_min_g')->nullable();
            $table->unsignedInteger('body_weight_max_g')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rearing_targets');
    }
};
