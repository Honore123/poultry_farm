<?php

namespace App\Filament\Resources\EmployeeSalaryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'payment_period';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('payment_period')
                    ->required()
                    ->default(fn () => now()->format('F Y')),
                Forms\Components\TextInput::make('base_salary')
                    ->numeric()
                    ->required()
                    ->prefix('RWF ')
                    ->default(fn () => $this->getOwnerRecord()->salary_amount),
                Forms\Components\TextInput::make('bonus')
                    ->numeric()
                    ->default(0)
                    ->prefix('RWF '),
                Forms\Components\TextInput::make('deductions')
                    ->numeric()
                    ->default(0)
                    ->prefix('RWF '),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('paid')
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money' => 'Mobile Money',
                    ])
                    ->default('cash'),
                Forms\Components\TextInput::make('reference')
                    ->label('Transaction Reference'),
                Forms\Components\Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_period')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_period')
                    ->searchable(),
                Tables\Columns\TextColumn::make('base_salary')
                    ->money('RWF')
                    ->label('Base'),
                Tables\Columns\TextColumn::make('bonus')
                    ->money('RWF')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deductions')
                    ->money('RWF')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('net_amount')
                    ->money('RWF')
                    ->label('Net Pay')
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Processed By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['processed_by'] = auth()->id();
                        return $data;
                    })
                    ->after(function ($record) {
                        if ($record->status === 'paid') {
                            $record->createExpenseRecord();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('payment_date', 'desc');
    }
}

