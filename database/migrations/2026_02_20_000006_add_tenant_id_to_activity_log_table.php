<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->index('tenant_id');
        });

        $defaultTenantId = DB::table('tenants')->where('name', 'Kabajogo Farm')->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($defaultTenantId) {
            DB::table('activity_log')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
