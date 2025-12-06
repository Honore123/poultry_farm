<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HealthTreatmentResource\Pages;
use App\Models\HealthTreatment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HealthTreatmentResource extends Resource
{
    protected static ?string $model = HealthTreatment::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Treatment Record')
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
                        Forms\Components\TextInput::make('product')
                            ->required()
                            ->maxLength(255)
                            ->datalist([
                                'Antibiotic - Amoxicillin',
                                'Antibiotic - Enrofloxacin',
                                'Antibiotic - Tylosin',
                                'Antibiotic - Doxycycline',
                                'Anticoccidial - Amprolium',
                                'Anticoccidial - Toltrazuril',
                                'Vitamins - AD3E',
                                'Vitamins - B-Complex',
                                'Electrolytes',
                                'Dewormer - Piperazine',
                                'Dewormer - Levamisole',
                                'Liver Tonic',
                            ])
                            ->helperText('Type or select from common products'),
                        Forms\Components\Textarea::make('reason')
                            ->rows(2)
                            ->placeholder('Reason for treatment (symptoms observed)')
                            ->maxLength(500),
                        Forms\Components\TextInput::make('dosage_per_liter_ml')
                            ->label('Dosage')
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('ml/L')
                            ->helperText('Dosage per liter of drinking water'),
                        Forms\Components\TextInput::make('duration_days')
                            ->label('Duration')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->suffix('days')
                            ->helperText('Treatment duration (1-30 days)'),
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
                Tables\Columns\TextColumn::make('product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('dosage_per_liter_ml')
                    ->label('Dosage')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' ml/L')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Days')
                    ->suffix(' days')
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
            'index' => Pages\ListHealthTreatments::route('/'),
            'create' => Pages\CreateHealthTreatment::route('/create'),
            'edit' => Pages\EditHealthTreatment::route('/{record}/edit'),
        ];
    }
}
