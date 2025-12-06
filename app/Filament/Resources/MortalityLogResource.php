<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MortalityLogResource\Pages;
use App\Models\Batch;
use App\Models\MortalityLog;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MortalityLogResource extends Resource
{
    protected static ?string $model = MortalityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Mortality Record')
                    ->schema([
                        Forms\Components\Select::make('batch_id')
                            ->relationship('batch', 'code', fn ($query) => $query->whereIn('status', ['brooding', 'growing', 'laying']))
                            ->label('Batch')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('_birds_alive', self::getBirdsAlive($state))),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->helperText('Cannot select future dates'),
                        Forms\Components\TextInput::make('count')
                            ->label('Number of Deaths')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(fn (Get $get) => self::getBirdsAlive($get('batch_id')) ?: 100000)
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $birdsAlive = self::getBirdsAlive($get('batch_id'));
                                    if ($birdsAlive !== null && $value > $birdsAlive) {
                                        $fail("Deaths ({$value}) cannot exceed birds alive ({$birdsAlive})");
                                    }
                                },
                            ])
                            ->helperText(fn (Get $get) => self::getBirdsAliveHelperText($get('batch_id'))),
                        Forms\Components\TextInput::make('cause')
                            ->maxLength(255)
                            ->placeholder('e.g., Disease, Heat stress, Predator, Unknown')
                            ->datalist([
                                'Disease',
                                'Heat stress',
                                'Cold stress',
                                'Predator',
                                'Accident',
                                'Cannibalism',
                                'Prolapse',
                                'Unknown',
                            ]),
                    ])->columns(2),
            ]);
    }

    protected static function getBirdsAlive(?string $batchId): ?int
    {
        if (!$batchId) {
            return null;
        }

        $batch = Batch::find($batchId);
        if (!$batch) {
            return null;
        }

        $totalMortality = MortalityLog::where('batch_id', $batchId)->sum('count');
        return $batch->placement_qty - $totalMortality;
    }

    protected static function getBirdsAliveHelperText(?string $batchId): string
    {
        $birdsAlive = self::getBirdsAlive($batchId);
        if ($birdsAlive === null) {
            return 'Select a batch to see birds alive';
        }
        return "Birds currently alive: {$birdsAlive}";
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch.code')
                    ->label('Batch')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('count')
                    ->label('Deaths')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cause')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch')
                    ->relationship('batch', 'code'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListMortalityLogs::route('/'),
            'create' => Pages\CreateMortalityLog::route('/create'),
            'edit' => Pages\EditMortalityLog::route('/{record}/edit'),
        ];
    }
}
