<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to handle the unique constraint change
        // First, add an index on batch_id alone (for the foreign key)
        DB::statement('CREATE INDEX daily_feed_intakes_batch_id_index ON daily_feed_intakes (batch_id)');
        
        // Now we can safely drop the unique constraint
        DB::statement('ALTER TABLE daily_feed_intakes DROP INDEX daily_feed_intakes_batch_id_date_unique');
        
        // Add the new unique constraint including feed_item_id
        // This allows multiple feed types per batch per day
        DB::statement('ALTER TABLE daily_feed_intakes ADD UNIQUE daily_feed_intakes_batch_id_date_feed_item_id_unique (batch_id, date, feed_item_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new constraint
        DB::statement('ALTER TABLE daily_feed_intakes DROP INDEX daily_feed_intakes_batch_id_date_feed_item_id_unique');
        
        // Restore the old constraint
        DB::statement('ALTER TABLE daily_feed_intakes ADD UNIQUE daily_feed_intakes_batch_id_date_unique (batch_id, date)');
        
        // Drop the batch_id index we created
        DB::statement('DROP INDEX daily_feed_intakes_batch_id_index ON daily_feed_intakes');
    }
};
