<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HealthTreatmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'healthTreatments';

    protected static ?string $title = 'Treatments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->maxDate(now())
                    ->native(false),
                Forms\Components\TextInput::make('product')
                    ->required()
                    ->maxLength(255)
                    ->datalist([
                        'Antibiotic - Amoxicillin',
                        'Antibiotic - Enrofloxacin',
                        'Anticoccidial - Amprolium',
                        'Vitamins - AD3E',
                        'Vitamins - B-Complex',
                        'Electrolytes',
                        'Dewormer - Piperazine',
                    ]),
                Forms\Components\TextInput::make('dosage_per_liter_ml')
                    ->label('Dosage (ml/L)')
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(100)
                    ->step(0.01),
                Forms\Components\TextInput::make('duration_days')
                    ->label('Days')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(30),
                Forms\Components\Textarea::make('reason')
                    ->rows(1)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ])->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dosage_per_liter_ml')
                    ->label('ml/L')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Days'),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(30),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Treatment'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
