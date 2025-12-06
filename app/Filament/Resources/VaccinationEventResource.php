<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VaccinationEventResource\Pages;
use App\Models\VaccinationEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VaccinationEventResource extends Resource
{
    protected static ?string $model = VaccinationEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vaccination Record')
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
                        Forms\Components\TextInput::make('vaccine')
                            ->required()
                            ->maxLength(255)
                            ->datalist([
                                "Marek's Disease",
                                'ND + IB (Newcastle + Infectious Bronchitis)',
                                'Gumboro (IBD)',
                                'Fowl Pox',
                                'NDV Lasota',
                                'Fowl Cholera',
                                'Infectious Coryza',
                                'Avian Encephalomyelitis',
                                'Egg Drop Syndrome',
                            ])
                            ->helperText('Type or select from common vaccines'),
                        Forms\Components\Select::make('method')
                            ->options([
                                'eye_drop' => 'Eye Drop',
                                'drinking_water' => 'Drinking Water',
                                'spray' => 'Spray',
                                'injection' => 'Injection (IM/SC)',
                                'wing_web' => 'Wing Web',
                            ])
                            ->native(false),
                        Forms\Components\TextInput::make('administered_by')
                            ->maxLength(255)
                            ->label('Administered By')
                            ->placeholder('Name of person/vet'),
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
                Tables\Columns\TextColumn::make('vaccine')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('administered_by')
                    ->label('By')
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
            'index' => Pages\ListVaccinationEvents::route('/'),
            'create' => Pages\CreateVaccinationEvent::route('/create'),
            'edit' => Pages\EditVaccinationEvent::route('/{record}/edit'),
        ];
    }
}
