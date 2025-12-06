<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VaccinationEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'vaccinationEvents';

    protected static ?string $title = 'Vaccinations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->maxDate(now())
                    ->native(false),
                Forms\Components\TextInput::make('vaccine')
                    ->required()
                    ->maxLength(255)
                    ->datalist([
                        "Marek's Disease",
                        'ND + IB',
                        'Gumboro (IBD)',
                        'Fowl Pox',
                        'NDV Lasota',
                        'Fowl Cholera',
                    ]),
                Forms\Components\Select::make('method')
                    ->options([
                        'eye_drop' => 'Eye Drop',
                        'drinking_water' => 'Drinking Water',
                        'spray' => 'Spray',
                        'injection' => 'Injection',
                        'wing_web' => 'Wing Web',
                    ])
                    ->native(false),
                Forms\Components\TextInput::make('administered_by')
                    ->label('By')
                    ->maxLength(255),
            ])->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vaccine')
                    ->searchable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge(),
                Tables\Columns\TextColumn::make('administered_by')
                    ->label('By'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Vaccination'),
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
