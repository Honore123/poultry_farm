<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RearingTargetResource\Pages;
use App\Models\RearingTarget;
use App\Tenancy\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class RearingTargetResource extends Resource
{
    protected static ?string $model = RearingTarget::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Rearing Targets (Week 1-18)';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Week & Age')
                    ->schema([
                        Forms\Components\TextInput::make('week')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(18)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule) {
                                    $tenantId = app(TenantContext::class)->currentTenantId()
                                        ?? auth()->user()?->tenant_id;

                                    if ($tenantId) {
                                        $rule->where('tenant_id', $tenantId);
                                    } else {
                                        $rule->whereNull('tenant_id');
                                    }

                                    return $rule;
                                }
                            )
                            ->label('Week'),
                        Forms\Components\TextInput::make('age_days_from')
                            ->numeric()
                            ->minValue(0)
                            ->label('Age Days From'),
                        Forms\Components\TextInput::make('age_days_to')
                            ->numeric()
                            ->minValue(0)
                            ->label('Age Days To'),
                    ])->columns(3),

                Forms\Components\Section::make('Daily Feed Intake')
                    ->schema([
                        Forms\Components\TextInput::make('daily_feed_min_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Min g/bird/day')
                            ->suffix('g')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, $state) => 
                                $set('min_kg_per_week_display', $state ? round($state * 7 / 1000, 3) : null)
                            ),
                        Forms\Components\TextInput::make('daily_feed_max_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Max g/bird/day')
                            ->suffix('g')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, $state) => 
                                $set('max_kg_per_week_display', $state ? round($state * 7 / 1000, 3) : null)
                            ),
                    ])->columns(2),

                Forms\Components\Section::make('Weekly Projection (per bird)')
                    ->description('Calculated: daily intake × 7 days')
                    ->schema([
                        Forms\Components\Placeholder::make('min_kg_per_week_display')
                            ->label('Min kg/bird/week')
                            ->content(fn ($record) => $record && $record->daily_feed_min_g
                                ? number_format($record->daily_feed_min_g * 7 / 1000, 3) . ' kg'
                                : 'Enter min g/bird/day'),
                        Forms\Components\Placeholder::make('max_kg_per_week_display')
                            ->label('Max kg/bird/week')
                            ->content(fn ($record) => $record && $record->daily_feed_max_g
                                ? number_format($record->daily_feed_max_g * 7 / 1000, 3) . ' kg'
                                : 'Enter max g/bird/day'),
                    ])->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Cumulative Feed')
                    ->schema([
                        Forms\Components\TextInput::make('cumulative_feed_min_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Cumulative Min (g)')
                            ->suffix('g'),
                        Forms\Components\TextInput::make('cumulative_feed_max_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Cumulative Max (g)')
                            ->suffix('g'),
                    ])->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Body Weight')
                    ->schema([
                        Forms\Components\TextInput::make('body_weight_min_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Min Weight (g)')
                            ->suffix('g'),
                        Forms\Components\TextInput::make('body_weight_max_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Max Weight (g)')
                            ->suffix('g'),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('week')
                    ->label('Week')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('age_days_from')
                    ->label('Days From')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('age_days_to')
                    ->label('Days To')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('daily_feed_min_g')
                    ->label('Min g/bird/day')
                    ->numeric()
                    ->suffix(' g'),
                Tables\Columns\TextColumn::make('daily_feed_max_g')
                    ->label('Max g/bird/day')
                    ->numeric()
                    ->suffix(' g'),
                Tables\Columns\TextColumn::make('min_kg_per_week')
                    ->label('Min kg/bird/week')
                    ->state(fn ($record) => $record->daily_feed_min_g 
                        ? round($record->daily_feed_min_g * 7 / 1000, 3) 
                        : null)
                    ->numeric(decimalPlaces: 3)
                    ->suffix(' kg')
                    ->color('info'),
                Tables\Columns\TextColumn::make('max_kg_per_week')
                    ->label('Max kg/bird/week')
                    ->state(fn ($record) => $record->daily_feed_max_g 
                        ? round($record->daily_feed_max_g * 7 / 1000, 3) 
                        : null)
                    ->numeric(decimalPlaces: 3)
                    ->suffix(' kg')
                    ->color('info'),
                Tables\Columns\TextColumn::make('body_weight_min_g')
                    ->label('Weight Min')
                    ->numeric()
                    ->suffix(' g')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('body_weight_max_g')
                    ->label('Weight Max')
                    ->numeric()
                    ->suffix(' g')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('week', 'asc');
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
            'index' => Pages\ListRearingTargets::route('/'),
            'create' => Pages\CreateRearingTarget::route('/create'),
            'edit' => Pages\EditRearingTarget::route('/{record}/edit'),
        ];
    }
}
