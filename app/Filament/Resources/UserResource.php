<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Mail\UserInvitationMail;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
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
                            ->relationship('roles', 'name')
                            ->columns(3)
                            ->helperText('Select the roles for this user'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

