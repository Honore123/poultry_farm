<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Mail\UserInvitationMail;
use App\Models\Tenant;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->options(fn () => Tenant::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(fn () => auth()->user()?->is_super_admin ?? false)
                            ->default(fn () => app(TenantContext::class)->currentTenantId() ?? auth()->user()?->tenant_id)
                            ->visible(fn () => auth()->user()?->is_super_admin ?? false)
                            ->live()
                            ->afterStateHydrated(function ($state) {
                                if (!auth()->user()?->is_super_admin) {
                                    return;
                                }

                                session(['tenant_id' => $state ?: null]);

                                $tenant = $state ? Tenant::query()->find($state) : null;
                                $context = app(TenantContext::class);
                                $context->setTenant($tenant);
                                $context->applyPermissionContext($tenant);
                            })
                            ->afterStateUpdated(function ($state) {
                                if (auth()->user()?->is_super_admin) {
                                    session(['tenant_id' => $state ?: null]);
                                    $tenant = $state ? Tenant::query()->find($state) : null;
                                    $context = app(TenantContext::class);
                                    $context->setTenant($tenant);
                                    $context->applyPermissionContext($tenant);
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_super_admin')
                            ->label('Super Admin')
                            ->helperText('Super admins can access all tenants and manage global permissions.')
                            ->visible(fn () => auth()->user()?->is_super_admin ?? false),
                        Forms\Components\Placeholder::make('invitation_notice')
                            ->label('')
                            ->content('📧 An invitation email will be sent to this user to set their password.')
                            ->visible(fn (string $context): bool => $context === 'create')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->same('passwordConfirmation')
                            ->visible(fn (string $context): bool => $context === 'edit')
                            ->helperText('Leave empty to keep current password'),
                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->password()
                            ->label('Confirm Password')
                            ->dehydrated(false)
                            ->visible(fn (string $context): bool => $context === 'edit'),
                    ])->columns(2),

                Forms\Components\Section::make('Roles')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->relationship('roles', 'name', function (Builder $query) {
                                $tenantId = app(TenantContext::class)->currentTenantId()
                                    ?? auth()->user()?->tenant_id;

                                if ($tenantId) {
                                    $roleTable = $query->getModel()->getTable();
                                    $query->where($roleTable . '.tenant_id', $tenantId);
                                    return;
                                }

                                $query->whereRaw('1 = 0');
                            })
                            ->saveRelationshipsUsing(function (Forms\Components\CheckboxList $component, $record, $state, Get $get) {
                                $tenantId = $record->tenant_id
                                    ?? $get('tenant_id')
                                    ?? app(TenantContext::class)->currentTenantId()
                                    ?? auth()->user()?->tenant_id;

                                if (!$tenantId) {
                                    $record->syncRoles([]);
                                    return;
                                }

                                $roleIds = collect($state ?? [])
                                    ->filter()
                                    ->values()
                                    ->all();

                                $roles = Role::query()
                                    ->whereIn('id', $roleIds)
                                    ->where('tenant_id', $tenantId)
                                    ->get();

                                $tenant = Tenant::query()->find($tenantId);
                                if ($tenant) {
                                    app(TenantContext::class)->runForTenant($tenant, function () use ($record, $roles): void {
                                        $record->syncRoles($roles);
                                    });
                                } else {
                                    $record->syncRoles($roles);
                                }

                                if ($roles->isNotEmpty()) {
                                    $pivotTable = config('permission.table_names.model_has_roles', 'model_has_roles');

                                    DB::table($pivotTable)
                                        ->where('model_id', $record->getKey())
                                        ->where('model_type', $record->getMorphClass())
                                        ->whereIn('role_id', $roles->pluck('id')->all())
                                        ->update(['tenant_id' => $tenantId]);
                                }
                            })
                            ->columns(3)
                            ->helperText('Select the roles for this user'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => auth()->user()?->is_super_admin ?? false),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'manager',
                        'success' => 'staff',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('resend_invitation')
                    ->label('Resend Invite')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Resend Invitation')
                    ->modalDescription(fn (User $record) => "Send a new password setup email to {$record->email}?")
                    ->action(function (User $record) {
                        $token = Str::random(64);
                        
                        DB::table('password_reset_tokens')->updateOrInsert(
                            ['email' => $record->email],
                            [
                                'email' => $record->email,
                                'token' => Hash::make($token),
                                'created_at' => now(),
                            ]
                        );
                        
                        try {
                            Mail::to($record->email)->send(new UserInvitationMail(
                                user: $record,
                                token: $token,
                                invitedBy: auth()->user()->name,
                            ));
                            
                            Notification::make()
                                ->title('Invitation sent!')
                                ->body("A new invitation has been sent to {$record->email}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to send email')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        // Prevent deleting yourself
                        if ($record->id === auth()->id()) {
                            throw new \Exception('You cannot delete your own account.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $context = app(TenantContext::class);
        $tenantId = $context->currentTenantId();

        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        }

        if (!$context->isSuperAdmin()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
