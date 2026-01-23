<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Permission;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function () {
                    if ($this->record->name === 'admin') {
                        Notification::make()
                            ->title('Cannot delete admin role')
                            ->body('The admin role is protected and cannot be deleted.')
                            ->danger()
                            ->send();
                        throw new \Exception('Cannot delete admin role');
                    }
                    
                    if ($this->record->users()->count() > 0) {
                        Notification::make()
                            ->title('Cannot delete role')
                            ->body('This role has users assigned to it. Remove users first.')
                            ->danger()
                            ->send();
                        throw new \Exception('Role has users assigned');
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        // If admin role, ensure it has all permissions
        if ($this->record->name === 'admin') {
            $this->record->syncPermissions(Permission::all());
        }
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Role updated successfully';
    }
}
