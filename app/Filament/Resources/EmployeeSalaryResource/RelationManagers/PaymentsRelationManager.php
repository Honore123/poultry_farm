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
        $ownerRecord = $this->getOwnerRecord();
        $isSplitPayment = $ownerRecord->payment_schedule === 'split';
        
        return $form
            ->schema([
                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('payment_period')
                    ->required()
                    ->default(fn () => now()->format('F Y')),
                Forms\Components\Select::make('payment_type')
                    ->options($isSplitPayment 
                        ? [
                            'first_half' => 'First Half (50%)',
                            'second_half' => 'Second Half (50%)',
                        ]
                        : [
                            'full' => 'Full Payment (100%)',
                        ])
                    ->default($isSplitPayment ? 'first_half' : 'full')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($ownerRecord, $isSplitPayment) {
                        if ($isSplitPayment && ($state === 'first_half' || $state === 'second_half')) {
                            $set('base_salary', round($ownerRecord->salary_amount / 2, 2));
                        } else {
                            $set('base_salary', $ownerRecord->salary_amount);
                        }
                    })
                    ->label('Payment Type'),
                Forms\Components\TextInput::make('base_salary')
                    ->numeric()
                    ->required()
                    ->prefix('RWF ')
                    ->default(fn () => $isSplitPayment 
                        ? round($ownerRecord->salary_amount / 2, 2) 
                        : $ownerRecord->salary_amount)
                    ->helperText($isSplitPayment 
                        ? 'Half of monthly salary: RWF ' . number_format($ownerRecord->salary_amount / 2, 2) 
                        : null),
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
                Tables\Columns\BadgeColumn::make('payment_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'first_half' => '1st Half',
                        'second_half' => '2nd Half',
                        default => 'Full',
                    })
                    ->colors([
                        'info' => 'first_half',
                        'warning' => 'second_half',
                        'success' => 'full',
                    ]),
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
                Tables\Filters\SelectFilter::make('payment_type')
                    ->options([
                        'full' => 'Full Payment',
                        'first_half' => 'First Half',
                        'second_half' => 'Second Half',
                    ])
                    ->label('Payment Type'),
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

