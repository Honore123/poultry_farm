<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_feed_intakes', function (Blueprint $table) {
            $table->foreignId('inventory_lot_id')
                ->nullable()
                ->after('feed_item_id')
                ->constrained('inventory_lots')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_feed_intakes', function (Blueprint $table) {
            $table->dropForeign(['inventory_lot_id']);
            $table->dropColumn('inventory_lot_id');
        });
    }
};
