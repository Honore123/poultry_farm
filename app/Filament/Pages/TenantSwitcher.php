<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class TenantSwitcher extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Tenant Switcher';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 95;

    protected static string $view = 'filament.pages.tenant-switcher';

    public ?int $tenantId = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->is_super_admin ?? false;
    }

    public function mount(): void
    {
        $this->tenantId = session('tenant_id');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Active Tenant')
                    ->description('Select a tenant to scope the admin panel. Leave empty to view all tenants.')
                    ->schema([
                        Select::make('tenantId')
                            ->label('Tenant')
                            ->options(Tenant::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->placeholder('All tenants')
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                session(['tenant_id' => $state ?: null]);

                                if (class_exists(PermissionRegistrar::class)) {
                                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                                }

                                Notification::make()
                                    ->title($state ? 'Tenant selected' : 'Viewing all tenants')
                                    ->success()
                                    ->send();
                            }),
                    ]),
            ]);
    }
}
