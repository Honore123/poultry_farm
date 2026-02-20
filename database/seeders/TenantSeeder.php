<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['name' => 'Kabajogo Farm'],
            ['slug' => Str::slug('Kabajogo Farm')]
        );
    }
}
