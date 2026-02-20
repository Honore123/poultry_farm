<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Models\Role;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = 'web';
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Automatically give the new permission to admin role
        $adminRoles = Role::withoutGlobalScopes()->where('name', 'admin')->get();
        foreach ($adminRoles as $adminRole) {
            $adminRole->givePermissionTo($this->record);
        }
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Permission created successfully';
    }
}
