<?php

namespace App\Filament\Field\Resources;

use App\Filament\Field\Resources\RecordMortalityResource\Pages;
use App\Models\Batch;
use App\Models\MortalityLog;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecordMortalityResource extends Resource
{
    protected static ?string $model = MortalityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Record Mortality';

    protected static ?string $modelLabel = 'Mortality Record';

    protected static ?string $pluralModelLabel = 'Mortality Records';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('💀 Record Mortality')
                    ->description('Log bird deaths')
                    ->schema([
                        Forms\Components\Select::make('batch_id')
                            ->label('Select Batch')
                            ->required()
                            ->searchable()
                            ->default(request()->query('batch'))
                            ->live()
                            ->getSearchResultsUsing(fn (string $search) => Batch::whereIn('status', ['brooding', 'growing', 'laying'])
                                ->where('code', 'like', "%{$search}%")
                                ->orderBy('code')
                                ->limit(20)
                                ->pluck('code', 'id')
                                ->toArray())
                            ->getOptionLabelUsing(fn ($value) => Batch::whereKey($value)->value('code'))
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => static::setBirdsAlive($state, $set))
                            ->afterStateHydrated(fn ($state, Forms\Set $set) => static::setBirdsAlive($state, $set))
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('count')
                            ->label('Number of Deaths')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $batchId = $get('batch_id');
                                    if (!$batchId) return;
                                    
                                    $batch = Batch::find($batchId);
                                    if (!$batch) return;
                                    
                                    $totalMortality = MortalityLog::where('batch_id', $batchId)->sum('count');
                                    $birdsAlive = $batch->placement_qty - $totalMortality;
                                    
                                    if ($value > $birdsAlive) {
                                        $fail("Cannot exceed birds alive ({$birdsAlive})");
                                    }
                                },
                            ])
                            ->helperText(function (Get $get) {
                                $birdsAlive = $get('birds_alive');
                                if ($birdsAlive === null) {
                                    return 'Select a batch first';
                                }

                                return "Birds alive: {$birdsAlive}";
                            })
                            ->autofocus()
                            ->extraInputAttributes(['class' => 'text-2xl font-bold'])
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('birds_alive'),

                        Forms\Components\Select::make('cause')
                            ->label('Cause of Death')
                            ->options([
                                'Disease' => 'Disease',
                                'Heat stress' => 'Heat Stress',
                                'Cold stress' => 'Cold Stress',
                                'Predator' => 'Predator',
                                'Accident' => 'Accident',
                                'Cannibalism' => 'Cannibalism',
                                'Prolapse' => 'Prolapse',
                                'Unknown' => 'Unknown',
                            ])
                            ->searchable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function setBirdsAlive($state, Forms\Set $set): void
    {
        if (!$state) {
            $set('birds_alive', null);
            return;
        }

        $batch = Batch::find($state);
        if (!$batch) {
            $set('birds_alive', null);
            return;
        }

        $totalMortality = MortalityLog::where('batch_id', $state)->sum('count');
        $birdsAlive = $batch->placement_qty - $totalMortality;

        $set('birds_alive', max(0, $birdsAlive));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                MortalityLog::query()->orderByDesc('date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch.code')
                    ->label('Batch')
                    ->sortable(),
                Tables\Columns\TextColumn::make('count')
                    ->label('Deaths')
                    ->numeric()
                    ->size('lg')
                    ->weight('bold')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('cause')
                    ->badge(),
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
                    ->visible(fn (MortalityLog $record) => $record->date->isToday()),
            ])
            ->bulkActions([])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('No mortality records')
            ->emptyStateDescription('Good news! No deaths recorded.')
            ->emptyStateIcon('heroicon-o-heart');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordMortality::route('/'),
            'create' => Pages\CreateRecordMortality::route('/create'),
            'edit' => Pages\EditRecordMortality::route('/{record}/edit'),
        ];
    }
}
