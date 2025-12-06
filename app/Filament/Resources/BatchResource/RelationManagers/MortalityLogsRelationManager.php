<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use App\Models\MortalityLog;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MortalityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'mortalityLogs';

    protected static ?string $title = 'Mortality';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->maxDate(now())
                    ->native(false),
                Forms\Components\TextInput::make('count')
                    ->label('Deaths')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->rules([
                        fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            $batch = $this->getOwnerRecord();
                            $totalMortality = MortalityLog::where('batch_id', $batch->id)->sum('count');
                            $birdsAlive = $batch->placement_qty - $totalMortality;
                            
                            if ($value > $birdsAlive) {
                                $fail("Deaths ({$value}) cannot exceed birds alive ({$birdsAlive})");
                            }
                        },
                    ])
                    ->helperText(function () {
                        $batch = $this->getOwnerRecord();
                        $totalMortality = MortalityLog::where('batch_id', $batch->id)->sum('count');
                        $birdsAlive = $batch->placement_qty - $totalMortality;
                        return "Birds alive: {$birdsAlive}";
                    }),
                Forms\Components\TextInput::make('cause')
                    ->maxLength(255)
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
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('count')
                    ->label('Deaths')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cause'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Log Mortality'),
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
