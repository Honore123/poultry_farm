<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('model_has_roles') || !Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('model_has_roles', 'tenant_id')) {
            DB::table('model_has_roles as mhr')
                ->join('users as u', function ($join) {
                    $join->on('mhr.model_id', '=', 'u.id')
                        ->where('mhr.model_type', User::class);
                })
                ->whereNotNull('u.tenant_id')
                ->where(function ($query) {
                    $query->whereNull('mhr.tenant_id')
                        ->orWhereColumn('mhr.tenant_id', '!=', 'u.tenant_id');
                })
                ->update(['mhr.tenant_id' => DB::raw('u.tenant_id')]);
        }

        if (Schema::hasTable('model_has_permissions') && Schema::hasColumn('model_has_permissions', 'tenant_id')) {
            DB::table('model_has_permissions as mhp')
                ->join('users as u', function ($join) {
                    $join->on('mhp.model_id', '=', 'u.id')
                        ->where('mhp.model_type', User::class);
                })
                ->whereNotNull('u.tenant_id')
                ->where(function ($query) {
                    $query->whereNull('mhp.tenant_id')
                        ->orWhereColumn('mhp.tenant_id', '!=', 'u.tenant_id');
                })
                ->update(['mhp.tenant_id' => DB::raw('u.tenant_id')]);
        }
    }

    public function down(): void
    {
        // No safe rollback for data correction.
    }
};
