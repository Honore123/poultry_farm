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
        Schema::create('egg_grading_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('week')->unique();
            $table->decimal('avg_egg_weight_g', 5, 2)->nullable();
            $table->decimal('pct_small', 5, 2)->nullable();
            $table->decimal('pct_medium', 5, 2)->nullable();
            $table->decimal('pct_large', 5, 2)->nullable();
            $table->decimal('pct_xl', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egg_grading_targets');
    }
};
