<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeightSampleResource\Pages;
use App\Models\WeightSample;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WeightSampleResource extends Resource
{
    protected static ?string $model = WeightSample::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 17;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Weight Sample Record')
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
                        Forms\Components\TextInput::make('sample_size')
                            ->label('Sample Size')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->suffix('birds')
                            ->helperText('Number of birds weighed (1-1000)'),
                        Forms\Components\TextInput::make('avg_weight_g')
                            ->label('Average Weight')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->step(0.01)
                            ->suffix('g')
                            ->helperText('Average weight in grams (1-10,000g)'),
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
                Tables\Columns\TextColumn::make('sample_size')
                    ->label('Sample')
                    ->numeric(),
                Tables\Columns\TextColumn::make('avg_weight_g')
                    ->label('Avg Weight (g)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
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
            'index' => Pages\ListWeightSamples::route('/'),
            'create' => Pages\CreateWeightSample::route('/create'),
            'edit' => Pages\EditWeightSample::route('/{record}/edit'),
        ];
    }
}
