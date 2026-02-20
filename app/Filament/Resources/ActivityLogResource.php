<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $modelLabel = 'Activity Log';

    protected static ?string $pluralModelLabel = 'Activity Logs';

    protected static ?int $navigationSort = 99;

    public static function canViewAny(): bool
    {
        return auth()->user()?->is_super_admin || auth()->user()?->hasRole('admin');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Activity Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description'),
                        Infolists\Components\TextEntry::make('subject_type')
                            ->label('Subject Type')
                            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-'),
                        Infolists\Components\TextEntry::make('subject_id')
                            ->label('Subject ID'),
                        Infolists\Components\TextEntry::make('causer.name')
                            ->label('Performed By')
                            ->default('System'),
                        Infolists\Components\TextEntry::make('event')
                            ->label('Event')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Date & Time')
                            ->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Changes')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('properties.old')
                            ->label('Old Values')
                            ->visible(fn ($record) => !empty($record->properties['old'] ?? null)),
                        Infolists\Components\KeyValueEntry::make('properties.attributes')
                            ->label('New Values')
                            ->visible(fn ($record) => !empty($record->properties['attributes'] ?? null)),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('By')
                    ->default('System'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Model')
                    ->options(fn () => Activity::query()
                        ->distinct()
                        ->pluck('subject_type', 'subject_type')
                        ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                        ->filter()
                        ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('User')
                    ->options(fn () => User::pluck('name', 'id')->toArray())
                    ->searchable(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
