<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\Farm;
use App\Models\FeedIntakeTarget;
use App\Models\House;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // =========================================================
        // ESSENTIAL SEEDERS - Keep these for production
        // These contain reference data needed for the app to work
        // =========================================================
        $this->call([
            TenantSeeder::class,               // Required: Tenant registry
            RolesAndPermissionsSeeder::class,    // Required: User roles and permissions
            RearingTargetsSeeder::class,          // Required: Rearing phase targets
            ProductionTargetsSeeder::class,       // Required: Production phase targets
            EggGradingTargetsSeeder::class,       // Required: Egg grading targets
            ProductionCycleTargetsSeeder::class,  // Required: Production cycle targets
        ]);

        // Seed feed intake targets (useful reference data)
        $this->seedFeedIntakeTargets();

        $this->command->info('✅ Essential reference data seeded successfully!');

        // =========================================================
        // DEMO/TEST DATA - Comment out for production
        // Uncomment these for development/testing only
        // =========================================================
        
        // $this->createTestUsers();      // Demo users with weak passwords
        // $this->seedFarms();            // Demo farm and houses
        // $this->seedInventoryItems();   // Demo inventory items
        // $this->seedSuppliers();        // Demo suppliers
        // $this->seedCustomers();        // Demo customers
        // $this->seedTestBatch();        // Demo batches

        // $this->command->info('✅ Demo data seeded successfully!');
    }

    /**
     * Create test users for development
     * DO NOT USE IN PRODUCTION - weak passwords!
     */
    protected function createTestUsers(): void
    {
        $tenant = Tenant::where('name', 'Kabajogo Farm')->first();

        if ($tenant) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        }

        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@farm.com'],
            [
                'name' => 'Admin User',
                'tenant_id' => $tenant?->id,
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        // Manager user
        $manager = User::firstOrCreate(
            ['email' => 'manager@farm.com'],
            [
                'name' => 'Manager User',
                'tenant_id' => $tenant?->id,
                'password' => Hash::make('password'),
            ]
        );
        $manager->assignRole('manager');

        // Staff user
        $staff = User::firstOrCreate(
            ['email' => 'staff@farm.com'],
            [
                'name' => 'Staff User',
                'tenant_id' => $tenant?->id,
                'password' => Hash::make('password'),
            ]
        );
        $staff->assignRole('staff');

        $this->command->info('Users created:');
        $this->command->table(
            ['Email', 'Password', 'Role'],
            [
                ['admin@farm.com', 'password', 'admin'],
                ['manager@farm.com', 'password', 'manager'],
                ['staff@farm.com', 'password', 'staff'],
            ]
        );
    }

    protected function seedFarms(): void
    {
        // Create main farm
        $farm = Farm::firstOrCreate(
            ['name' => 'Main Farm'],
            ['location' => 'Kigali, Rwanda']
        );

        // Create houses
        $houses = [
            ['name' => 'House A', 'capacity' => 5000, 'notes' => 'Layer house - East wing'],
            ['name' => 'House B', 'capacity' => 5000, 'notes' => 'Layer house - West wing'],
            ['name' => 'House C', 'capacity' => 3000, 'notes' => 'Brooder house'],
            ['name' => 'House D', 'capacity' => 2000, 'notes' => 'Grower house'],
        ];

        foreach ($houses as $houseData) {
            House::firstOrCreate(
                ['farm_id' => $farm->id, 'name' => $houseData['name']],
                ['capacity' => $houseData['capacity'], 'notes' => $houseData['notes']]
            );
        }

        $this->command->info("Farm '{$farm->name}' with " . count($houses) . " houses created.");
    }

    protected function seedInventoryItems(): void
    {
        $items = [
            // Feeds
            ['name' => 'Chick Starter Mash 21%', 'category' => 'feed', 'sku' => 'FEED-CS21', 'uom' => 'kg'],
            ['name' => 'Grower Mash 18%', 'category' => 'feed', 'sku' => 'FEED-GR18', 'uom' => 'kg'],
            ['name' => 'Layer Mash 17%', 'category' => 'feed', 'sku' => 'FEED-LY17', 'uom' => 'kg'],
            ['name' => 'Layer Concentrate 35%', 'category' => 'feed', 'sku' => 'FEED-LC35', 'uom' => 'kg'],
            ['name' => 'Pre-Lay Mash 16%', 'category' => 'feed', 'sku' => 'FEED-PL16', 'uom' => 'kg'],
            ['name' => 'Oyster Shell', 'category' => 'feed', 'sku' => 'FEED-OSH', 'uom' => 'kg'],
            ['name' => 'Grit', 'category' => 'feed', 'sku' => 'FEED-GRIT', 'uom' => 'kg'],

            // Drugs & Vaccines
            ['name' => 'Newcastle Disease Vaccine (Lasota)', 'category' => 'drug', 'sku' => 'VAC-NDV', 'uom' => 'dose'],
            ['name' => 'Gumboro Vaccine (IBD)', 'category' => 'drug', 'sku' => 'VAC-IBD', 'uom' => 'dose'],
            ['name' => 'Fowl Pox Vaccine', 'category' => 'drug', 'sku' => 'VAC-FPX', 'uom' => 'dose'],
            ['name' => 'Marek\'s Disease Vaccine', 'category' => 'drug', 'sku' => 'VAC-MDV', 'uom' => 'dose'],
            ['name' => 'Amoxicillin Powder', 'category' => 'drug', 'sku' => 'DRG-AMOX', 'uom' => 'g'],
            ['name' => 'Enrofloxacin 10%', 'category' => 'drug', 'sku' => 'DRG-ENRO', 'uom' => 'ml'],
            ['name' => 'Vitamins AD3E', 'category' => 'drug', 'sku' => 'DRG-AD3E', 'uom' => 'ml'],
            ['name' => 'Electrolytes', 'category' => 'drug', 'sku' => 'DRG-ELEC', 'uom' => 'g'],
            ['name' => 'Amprolium (Anticoccidial)', 'category' => 'drug', 'sku' => 'DRG-AMPR', 'uom' => 'g'],
            ['name' => 'Piperazine (Dewormer)', 'category' => 'drug', 'sku' => 'DRG-PIPE', 'uom' => 'ml'],

            // Packaging
            ['name' => 'Egg Tray (30 eggs)', 'category' => 'packaging', 'sku' => 'PKG-TRAY30', 'uom' => 'piece'],
            ['name' => 'Egg Carton (6 eggs)', 'category' => 'packaging', 'sku' => 'PKG-CTN6', 'uom' => 'piece'],
            ['name' => 'Egg Crate (360 eggs)', 'category' => 'packaging', 'sku' => 'PKG-CRT360', 'uom' => 'piece'],

            // Equipment
            ['name' => 'Drinker (5L)', 'category' => 'equipment', 'sku' => 'EQP-DRK5', 'uom' => 'piece'],
            ['name' => 'Feeder (15kg)', 'category' => 'equipment', 'sku' => 'EQP-FDR15', 'uom' => 'piece'],
            ['name' => 'Heat Lamp', 'category' => 'equipment', 'sku' => 'EQP-HLMP', 'uom' => 'piece'],
        ];

        foreach ($items as $item) {
            InventoryItem::firstOrCreate(
                ['sku' => $item['sku']],
                ['name' => $item['name'], 'category' => $item['category'], 'uom' => $item['uom']]
            );
        }

        $this->command->info(count($items) . ' inventory items created.');
    }

    protected function seedSuppliers(): void
    {
        $suppliers = [
            ['name' => 'Uzima Feeds Ltd', 'phone' => '+250 788 123 456', 'email' => 'sales@uzimafeeds.rw'],
            ['name' => 'Vet Pharma Rwanda', 'phone' => '+250 788 234 567', 'email' => 'orders@vetpharma.rw'],
            ['name' => 'Agro Supplies Co', 'phone' => '+250 788 345 678', 'email' => 'info@agrosupplies.rw'],
            ['name' => 'Premium Hatchery', 'phone' => '+250 788 456 789', 'email' => 'chicks@premiumhatch.rw'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['email' => $supplier['email']],
                ['name' => $supplier['name'], 'phone' => $supplier['phone']]
            );
        }

        $this->command->info(count($suppliers) . ' suppliers created.');
    }

    protected function seedCustomers(): void
    {
        $customers = [
            ['name' => 'Nakumatt Supermarket', 'phone' => '+250 788 111 111', 'email' => 'procurement@nakumatt.rw'],
            ['name' => 'Simba Supermarket', 'phone' => '+250 788 222 222', 'email' => 'orders@simba.rw'],
            ['name' => 'Hotel Mille Collines', 'phone' => '+250 788 333 333', 'email' => 'kitchen@millecollines.rw'],
            ['name' => 'Local Market - Kimironko', 'phone' => '+250 788 444 444', 'email' => null],
            ['name' => 'Restaurant La Palisse', 'phone' => '+250 788 555 555', 'email' => 'chef@lapalisse.rw'],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(
                ['name' => $customer['name']],
                ['phone' => $customer['phone'], 'email' => $customer['email']]
            );
        }

        $this->command->info(count($customers) . ' customers created.');
    }

    protected function seedFeedIntakeTargets(): void
    {
        $targets = [
            // Chick stage (Week 0-4)
            ['min_week' => 0, 'max_week' => 1, 'stage' => 'Chick', 'grams_per_bird_per_day_min' => 10, 'grams_per_bird_per_day_max' => 15],
            ['min_week' => 1, 'max_week' => 2, 'stage' => 'Chick', 'grams_per_bird_per_day_min' => 15, 'grams_per_bird_per_day_max' => 22],
            ['min_week' => 2, 'max_week' => 3, 'stage' => 'Chick', 'grams_per_bird_per_day_min' => 22, 'grams_per_bird_per_day_max' => 30],
            ['min_week' => 3, 'max_week' => 4, 'stage' => 'Chick', 'grams_per_bird_per_day_min' => 30, 'grams_per_bird_per_day_max' => 38],

            // Grower stage (Week 5-8)
            ['min_week' => 5, 'max_week' => 6, 'stage' => 'Grower', 'grams_per_bird_per_day_min' => 38, 'grams_per_bird_per_day_max' => 48],
            ['min_week' => 6, 'max_week' => 7, 'stage' => 'Grower', 'grams_per_bird_per_day_min' => 48, 'grams_per_bird_per_day_max' => 55],
            ['min_week' => 7, 'max_week' => 8, 'stage' => 'Grower', 'grams_per_bird_per_day_min' => 55, 'grams_per_bird_per_day_max' => 62],

            // Developer stage (Week 9-16)
            ['min_week' => 9, 'max_week' => 12, 'stage' => 'Developer', 'grams_per_bird_per_day_min' => 62, 'grams_per_bird_per_day_max' => 75],
            ['min_week' => 13, 'max_week' => 16, 'stage' => 'Developer', 'grams_per_bird_per_day_min' => 75, 'grams_per_bird_per_day_max' => 85],

            // Pre-Lay stage (Week 17-18)
            ['min_week' => 17, 'max_week' => 18, 'stage' => 'Pre-Lay', 'grams_per_bird_per_day_min' => 85, 'grams_per_bird_per_day_max' => 95],

            // Layer stage (Week 19+)
            ['min_week' => 19, 'max_week' => 25, 'stage' => 'Layer (Peak)', 'grams_per_bird_per_day_min' => 100, 'grams_per_bird_per_day_max' => 120],
            ['min_week' => 26, 'max_week' => 45, 'stage' => 'Layer (Mid)', 'grams_per_bird_per_day_min' => 110, 'grams_per_bird_per_day_max' => 125],
            ['min_week' => 46, 'max_week' => 72, 'stage' => 'Layer (Late)', 'grams_per_bird_per_day_min' => 105, 'grams_per_bird_per_day_max' => 118],
        ];

        foreach ($targets as $target) {
            FeedIntakeTarget::firstOrCreate(
                ['min_week' => $target['min_week'], 'max_week' => $target['max_week']],
                [
                    'stage' => $target['stage'],
                    'grams_per_bird_per_day_min' => $target['grams_per_bird_per_day_min'],
                    'grams_per_bird_per_day_max' => $target['grams_per_bird_per_day_max'],
                ]
            );
        }

        $this->command->info(count($targets) . ' feed intake targets created.');
    }

    protected function seedTestBatch(): void
    {
        $farm = Farm::where('name', 'Main Farm')->first();
        $house = House::where('farm_id', $farm->id)->where('name', 'House A')->first();

        if (!$farm || !$house) {
            $this->command->warn('Cannot create test batch: Farm or House not found.');
            return;
        }

        // Create a test batch that's been laying for a few weeks
        $batch = Batch::firstOrCreate(
            ['code' => 'BATCH-2025-001'],
            [
                'farm_id' => $farm->id,
                'house_id' => $house->id,
                'breed' => 'Lohmann Brown Classic',
                'source' => 'Premium Hatchery',
                'placement_date' => now()->subWeeks(24), // 24 weeks old (laying)
                'placement_qty' => 4500,
                'status' => 'laying',
            ]
        );

        // Create a second batch in grower stage
        $house2 = House::where('farm_id', $farm->id)->where('name', 'House D')->first();
        if ($house2) {
            Batch::firstOrCreate(
                ['code' => 'BATCH-2025-002'],
                [
                    'farm_id' => $farm->id,
                    'house_id' => $house2->id,
                    'breed' => 'Hy-Line Brown',
                    'source' => 'Premium Hatchery',
                    'placement_date' => now()->subWeeks(8), // 8 weeks old (growing)
                    'placement_qty' => 1800,
                    'status' => 'growing',
                ]
            );
        }

        // Create a third batch in brooding
        $house3 = House::where('farm_id', $farm->id)->where('name', 'House C')->first();
        if ($house3) {
            Batch::firstOrCreate(
                ['code' => 'BATCH-2025-003'],
                [
                    'farm_id' => $farm->id,
                    'house_id' => $house3->id,
                    'breed' => 'ISA Brown',
                    'source' => 'Premium Hatchery',
                    'placement_date' => now()->subWeeks(2), // 2 weeks old (brooding)
                    'placement_qty' => 2500,
                    'status' => 'brooding',
                ]
            );
        }

        $this->command->info('Test batches created:');
        $this->command->table(
            ['Code', 'Breed', 'Age', 'Status', 'Qty'],
            [
                ['BATCH-2025-001', 'Lohmann Brown', '24 weeks', 'laying', '4,500'],
                ['BATCH-2025-002', 'Hy-Line Brown', '8 weeks', 'growing', '1,800'],
                ['BATCH-2025-003', 'ISA Brown', '2 weeks', 'brooding', '2,500'],
            ]
        );
    }
}
