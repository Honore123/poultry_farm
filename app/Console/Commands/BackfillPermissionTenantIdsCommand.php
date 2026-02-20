<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillPermissionTenantIdsCommand extends Command
{
    protected $signature = 'farm:backfill-permission-tenant-ids
        {--force : Also fix mismatched tenant_id values (not just NULL)}
        {--dry-run : Show counts without updating data}';

    protected $description = 'Backfill tenant_id on permission pivots based on users.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info('Backfilling tenant_id on permission pivots...');

        $roleResults = $this->backfillPivot('model_has_roles', $dryRun, $force);
        $permResults = $this->backfillPivot('model_has_permissions', $dryRun, $force);

        $this->line('');
        $this->info('Summary:');
        $this->line("model_has_roles: matched {$roleResults['matched']}, updated {$roleResults['updated']}");
        $this->line("model_has_permissions: matched {$permResults['matched']}, updated {$permResults['updated']}");

        if ($dryRun) {
            $this->warn('Dry run only. No changes were written.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{matched:int, updated:int}
     */
    protected function backfillPivot(string $pivotTable, bool $dryRun, bool $force): array
    {
        if (!Schema::hasTable($pivotTable)) {
            $this->warn("Skipped {$pivotTable}: table not found.");
            return ['matched' => 0, 'updated' => 0];
        }

        if (!Schema::hasColumn($pivotTable, 'tenant_id')) {
            $this->warn("Skipped {$pivotTable}: tenant_id column missing. Run migrations first.");
            return ['matched' => 0, 'updated' => 0];
        }

        if (!Schema::hasTable('users')) {
            $this->warn("Skipped {$pivotTable}: users table not found.");
            return ['matched' => 0, 'updated' => 0];
        }

        $query = DB::table($pivotTable . ' as mh')
            ->join('users as u', function ($join) {
                $join->on('mh.model_id', '=', 'u.id')
                    ->where('mh.model_type', User::class);
            })
            ->whereNotNull('u.tenant_id');

        if ($force) {
            $query->where(function ($q) {
                $q->whereNull('mh.tenant_id')
                    ->orWhereColumn('mh.tenant_id', '!=', 'u.tenant_id');
            });
        } else {
            $query->whereNull('mh.tenant_id');
        }

        $matched = (clone $query)->count();

        if ($dryRun || $matched === 0) {
            return ['matched' => $matched, 'updated' => 0];
        }

        $updated = $query->update(['mh.tenant_id' => DB::raw('u.tenant_id')]);

        return ['matched' => $matched, 'updated' => $updated];
    }
}
