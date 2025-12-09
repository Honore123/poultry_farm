<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeSalaryResource\Pages;
use App\Filament\Resources\EmployeeSalaryResource\RelationManagers;
use App\Models\EmployeeSalary;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeSalaryResource extends Resource
{
    protected static ?string $model = EmployeeSalary::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Payroll';

    protected static ?string $navigationLabel = 'Employee Salaries';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Link to System User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Select a user if this employee has system access')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user) {
                                        $set('employee_name', $user->name);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('employee_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Employee Name'),
                        Forms\Components\TextInput::make('employee_phone')
                            ->tel()
                            ->maxLength(255)
                            ->label('Phone Number'),
                        Forms\Components\TextInput::make('position')
                            ->maxLength(255)
                            ->label('Job Position'),
                    ])->columns(2),

                Forms\Components\Section::make('Salary Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('salary_amount')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('RWF ')
                            ->label('Monthly Salary'),
                        Forms\Components\Select::make('payment_day')
                            ->options(array_combine(range(1, 28), range(1, 28)))
                            ->default(1)
                            ->required()
                            ->label('Payment Day of Month')
                            ->helperText('Day of the month when salary is paid (1-28)'),
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->default(now())
                            ->label('Start Date'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->helperText('Leave empty if still active'),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'terminated' => 'Terminated',
                            ])
                            ->default('active')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_name')
                    ->searchable()
                    ->sortable()
                    ->label('Employee'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('System User')
                    ->badge()
                    ->color('info')
                    ->placeholder('Not linked'),
                Tables\Columns\TextColumn::make('position')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('salary_amount')
                    ->money('RWF')
                    ->sortable()
                    ->label('Monthly Salary'),
                Tables\Columns\TextColumn::make('payment_day')
                    ->label('Pay Day')
                    ->formatStateUsing(fn ($state) => "Day {$state}"),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'terminated',
                    ]),
                Tables\Columns\TextColumn::make('payments_count')
                    ->counts('payments')
                    ->label('Payments'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'terminated' => 'Terminated',
                    ]),
                Tables\Filters\TernaryFilter::make('has_user')
                    ->label('Linked to User')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('user_id'),
                        false: fn (Builder $query) => $query->whereNull('user_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('process_payment')
                    ->label('Process Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('payment_period')
                            ->required()
                            ->default(fn () => now()->format('F Y'))
                            ->helperText('e.g., December 2025'),
                        Forms\Components\TextInput::make('base_salary')
                            ->numeric()
                            ->required()
                            ->prefix('RWF ')
                            ->default(fn (EmployeeSalary $record) => $record->salary_amount),
                        Forms\Components\TextInput::make('bonus')
                            ->numeric()
                            ->default(0)
                            ->prefix('RWF '),
                        Forms\Components\TextInput::make('deductions')
                            ->numeric()
                            ->default(0)
                            ->prefix('RWF '),
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
                            ->rows(2),
                    ])
                    ->action(function (EmployeeSalary $record, array $data) {
                        $payment = $record->payments()->create([
                            ...$data,
                            'status' => 'paid',
                            'processed_by' => auth()->id(),
                        ]);

                        // Create expense record
                        $payment->createExpenseRecord();

                        \Filament\Notifications\Notification::make()
                            ->title('Payment Processed')
                            ->body("Salary payment of RWF " . number_format($payment->net_amount) . " has been recorded.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (EmployeeSalary $record) => $record->status === 'active'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeSalaries::route('/'),
            'create' => Pages\CreateEmployeeSalary::route('/create'),
            'view' => Pages\ViewEmployeeSalary::route('/{record}'),
            'edit' => Pages\EditEmployeeSalary::route('/{record}/edit'),
        ];
    }
}

