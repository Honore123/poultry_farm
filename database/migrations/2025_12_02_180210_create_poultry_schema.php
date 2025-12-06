<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /**
         * ========== CORE ENTITIES ==========
         */
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('capacity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();          // e.g. BATCH-2025-01
            $table->string('breed')->nullable();
            $table->string('source')->nullable();
            $table->date('placement_date');
            $table->integer('placement_qty');
            $table->enum('status', [
                'brooding', 'growing', 'laying', 'culled', 'closed',
            ])->default('brooding');
            $table->timestamps();
        });

        /**
         * ========== DAILY OPERATIONS ==========
         */
        Schema::create('daily_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('eggs_total')->default(0);
            $table->integer('eggs_soft')->default(0);
            $table->integer('eggs_cracked')->default(0);
            $table->integer('eggs_dirty')->default(0);
            $table->decimal('egg_weight_avg_g', 6, 2)->nullable();
            $table->decimal('lighting_hours', 4, 1)->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'date']);
        });

        Schema::create('daily_feed_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('feed_item_id')->nullable(); // later FK to inventory_items if you want
            $table->decimal('kg_given', 10, 2);
            $table->timestamps();

            $table->unique(['batch_id', 'date']);
        });

        Schema::create('daily_water_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('liters_used', 10, 2);
            $table->timestamps();

            $table->unique(['batch_id', 'date']);
        });

        Schema::create('weight_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('sample_size');
            $table->decimal('avg_weight_g', 6, 2);
            $table->timestamps();

            $table->unique(['batch_id', 'date']);
        });

        Schema::create('mortality_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('count');     // validate >=0 in app logic
            $table->string('cause')->nullable();
            $table->timestamps();
        });

        Schema::create('vaccination_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('vaccine');       // Marek’s, NDV Lasota, etc.
            $table->string('method')->nullable();   // eye drop, water, etc.
            $table->string('administered_by')->nullable();
            $table->timestamps();
        });

        Schema::create('health_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('product');          // e.g. antibiotic, vitamins
            $table->text('reason')->nullable();
            $table->decimal('dosage_per_liter_ml', 8, 2)->nullable();
            $table->integer('duration_days')->nullable();
            $table->timestamps();
        });

        /**
         * ========== INVENTORY ==========
         */
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable()->unique();
            $table->string('name');            // e.g. Layer Mash 17% CP
            $table->enum('category', ['feed', 'drug', 'packaging', 'equipment', 'other']);
            $table->string('uom');             // kg, bag, piece, ml, etc.
            $table->timestamps();
        });

        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lot_code')->nullable();
            $table->date('expiry')->nullable();
            $table->decimal('qty_on_hand', 12, 3)->default(0);
            $table->string('uom');
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('inventory_lots')->cascadeOnDelete();
            $table->timestamp('ts')->useCurrent();
            $table->enum('direction', ['in', 'out']);
            $table->decimal('qty', 12, 3);
            $table->string('reference')->nullable();    // PO#, SO#, etc.
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        /**
         * ========== CUSTOMERS & SALES ==========
         */
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('order_date');
            $table->enum('status', ['draft', 'confirmed', 'delivered', 'cancelled'])
                  ->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->string('product');             // e.g. "Tray of 30 eggs"
            $table->integer('qty');
            $table->string('uom');                 // tray, piece, kg, etc.
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });

        /**
         * ========== EXPENSES ==========
         */
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('category')->nullable();      // feed, labor, utilities, etc.
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('house_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        /**
         * ========== USERS & ROLES ==========
         * (If using Laravel's default users table, adapt accordingly)
         */
        // Schema::create('users', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('email')->unique();
        //     $table->timestamp('email_verified_at')->nullable();
        //     $table->string('password');
        //     $table->rememberToken();
        //     $table->timestamps();
        // });

        // Schema::create('roles', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name')->unique();          // admin, manager, staff
        //     $table->timestamps();
        // });

        // Schema::create('user_roles', function (Blueprint $table) {
        //     $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        //     $table->foreignId('role_id')->constrained()->cascadeOnDelete();
        //     $table->primary(['user_id', 'role_id']);
        // });

        /**
         * ========== ATTACHMENTS ==========
         * Polymorphic-style ref: entity_type + entity_id
         */
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');       // 'batch','sales_order','expense', etc.
            $table->unsignedBigInteger('entity_id');
            $table->string('file_url');          // storage path or URL
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
        });

        /**
         * ========== FEED INTAKE TARGETS ==========
         * For recommended g/bird/day by age range.
         */
        Schema::create('feed_intake_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('min_week');
            $table->unsignedInteger('max_week');
            $table->string('stage');                            // chick, grower, pre-lay, etc.
            $table->unsignedInteger('grams_per_bird_per_day_min');
            $table->unsignedInteger('grams_per_bird_per_day_max');
            $table->timestamps();
        });



    }

    public function down(): void
    {

        // Drop tables in reverse FK order
        Schema::dropIfExists('feed_intake_targets');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_lots');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('health_treatments');
        Schema::dropIfExists('vaccination_events');
        Schema::dropIfExists('mortality_logs');
        Schema::dropIfExists('weight_samples');
        Schema::dropIfExists('daily_water_usages');
        Schema::dropIfExists('daily_feed_intakes');
        Schema::dropIfExists('daily_productions');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('houses');
        Schema::dropIfExists('farms');
    }
};
