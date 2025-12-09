<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryPaymentResource\Pages;
use App\Models\SalaryPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalaryPaymentResource extends Resource
{
    protected static ?string $model = SalaryPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Payment History';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('employee_salary_id')
                            ->relationship('employeeSalary', 'employee_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Employee')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $employeeSalary = \App\Models\EmployeeSalary::find($state);
                                    if ($employeeSalary) {
                                        $set('base_salary', $employeeSalary->salary_amount);
                                    }
                                }
                            }),
                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('payment_period')
                            ->required()
                            ->default(fn () => now()->format('F Y'))
                            ->helperText('e.g., December 2025'),
                    ])->columns(3),

                Forms\Components\Section::make('Amounts')
                    ->schema([
                        Forms\Components\TextInput::make('base_salary')
                            ->numeric()
                            ->required()
                            ->prefix('RWF ')
                            ->label('Base Salary'),
                        Forms\Components\TextInput::make('bonus')
                            ->numeric()
                            ->default(0)
                            ->prefix('RWF '),
                        Forms\Components\TextInput::make('deductions')
                            ->numeric()
                            ->default(0)
                            ->prefix('RWF '),
                        Forms\Components\Placeholder::make('net_preview')
                            ->label('Net Amount')
                            ->content(function (Forms\Get $get) {
                                $base = floatval($get('base_salary') ?? 0);
                                $bonus = floatval($get('bonus') ?? 0);
                                $deductions = floatval($get('deductions') ?? 0);
                                $net = $base + $bonus - $deductions;
                                return 'RWF ' . number_format($net, 2);
                            }),
                    ])->columns(4),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
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
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employeeSalary.employee_name')
                    ->searchable()
                    ->sortable()
                    ->label('Employee'),
                Tables\Columns\TextColumn::make('employeeSalary.position')
                    ->label('Position')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payment_period')
                    ->searchable(),
                Tables\Columns\TextColumn::make('base_salary')
                    ->money('RWF')
                    ->label('Base')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bonus')
                    ->money('RWF')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deductions')
                    ->money('RWF')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('net_amount')
                    ->money('RWF')
                    ->label('Net Pay')
                    ->sortable()
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
                Tables\Columns\IconColumn::make('expense_id')
                    ->label('Expense')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($state) => $state ? 'Linked to expense' : 'Not linked'),
                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Processed By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('employee_salary_id')
                    ->relationship('employeeSalary', 'employee_name')
                    ->label('Employee')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money' => 'Mobile Money',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('create_expense')
                    ->label('Create Expense')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (SalaryPayment $record) => $record->status === 'paid' && !$record->expense_id)
                    ->action(function (SalaryPayment $record) {
                        $record->createExpenseRecord();
                        \Filament\Notifications\Notification::make()
                            ->title('Expense Created')
                            ->body('Expense record has been created for this payment.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('payment_date', 'desc');
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
            'index' => Pages\ListSalaryPayments::route('/'),
            'create' => Pages\CreateSalaryPayment::route('/create'),
            'view' => Pages\ViewSalaryPayment::route('/{record}'),
            'edit' => Pages\EditSalaryPayment::route('/{record}/edit'),
        ];
    }
}

