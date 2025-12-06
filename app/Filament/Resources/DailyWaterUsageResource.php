<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyWaterUsageResource\Pages;
use App\Models\DailyWaterUsage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyWaterUsageResource extends Resource
{
    protected static ?string $model = DailyWaterUsage::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Water Usage Record')
                    ->schema([
                        Forms\Components\Select::make('batch_id')
                            ->relationship('batch', 'code', fn ($query) => $query->whereIn('status', ['brooding', 'growing', 'laying']))
                            ->label('Batch')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->helperText('Cannot select future dates'),
                        Forms\Components\TextInput::make('liters_used')
                            ->label('Water Used')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(100000)
                            ->step(0.01)
                            ->suffix('L')
                            ->helperText('Between 0.01 and 100,000 liters'),
                    ])->columns(2),
            ]);
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
                Tables\Columns\TextColumn::make('liters_used')
                    ->label('Liters Used')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch')
                    ->relationship('batch', 'code'),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('date', today())),
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
            'index' => Pages\ListDailyWaterUsages::route('/'),
            'create' => Pages\CreateDailyWaterUsage::route('/create'),
            'edit' => Pages\EditDailyWaterUsage::route('/{record}/edit'),
        ];
    }
}
