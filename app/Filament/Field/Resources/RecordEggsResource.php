<?php

namespace App\Filament\Field\Resources;

use App\Filament\Field\Resources\RecordEggsResource\Pages;
use App\Models\Batch;
use App\Models\DailyProduction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecordEggsResource extends Resource
{
    protected static ?string $model = DailyProduction::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Record Eggs';

    protected static ?string $modelLabel = 'Egg Record';

    protected static ?string $pluralModelLabel = 'Egg Records';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🥚 Record Egg Production')
                    ->description('Enter today\'s egg collection data')
                    ->schema([
                        Forms\Components\Select::make('batch_id')
                            ->label('Select Batch')
                            ->options(
                                Batch::whereIn('status', ['laying'])
                                    ->pluck('code', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->default(request()->query('batch'))
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('eggs_total')
                            ->label('Total Eggs Collected')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100000)
                            ->live(onBlur: true)
                            ->autofocus()
                            ->extraInputAttributes(['class' => 'text-2xl font-bold'])
                            ->columnSpanFull(),

                        Forms\Components\Fieldset::make('Egg Quality (Optional)')
                            ->schema([
                                Forms\Components\TextInput::make('eggs_cracked')
                                    ->label('Cracked')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->lte('eggs_total'),
                                Forms\Components\TextInput::make('eggs_dirty')
                                    ->label('Dirty')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->lte('eggs_total'),
                                Forms\Components\TextInput::make('eggs_soft')
                                    ->label('Soft Shell')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->lte('eggs_total'),
                            ])->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                DailyProduction::query()->orderByDesc('date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch.code')
                    ->label('Batch')
                    ->sortable(),
                Tables\Columns\TextColumn::make('eggs_total')
                    ->label('Total')
                    ->numeric()
                    ->size('lg')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('eggs_cracked')
                    ->label('Cracked'),
                Tables\Columns\TextColumn::make('eggs_dirty')
                    ->label('Dirty'),
                Tables\Columns\TextColumn::make('eggs_soft')
                    ->label('Soft'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch')
                    ->relationship('batch', 'code'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (DailyProduction $record) => $record->date->isToday()),
            ])
            ->bulkActions([])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('No egg records yet')
            ->emptyStateDescription('Start recording egg production for your batches')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordEggs::route('/'),
            'create' => Pages\CreateRecordEggs::route('/create'),
            'edit' => Pages\EditRecordEggs::route('/{record}/edit'),
        ];
    }
}

