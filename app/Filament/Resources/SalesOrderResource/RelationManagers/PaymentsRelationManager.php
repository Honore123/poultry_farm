<?php

namespace App\Filament\Resources\SalesOrderResource\RelationManagers;

use App\Models\SalesOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'payment_date';

    public function form(Form $form): Form
    {
        /** @var SalesOrder $salesOrder */
        $salesOrder = $this->getOwnerRecord();
        $remainingAmount = $salesOrder->remaining_amount;

        return $form
            ->schema([
                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->default(now())
                    ->native(false),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->prefix('RWF')
                    ->default($remainingAmount)
                    ->helperText(fn () => "Remaining: RWF " . number_format($remainingAmount, 0)),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money' => 'Mobile Money',
                    ])
                    ->default('cash')
                    ->required(),
                Forms\Components\TextInput::make('reference')
                    ->label('Transaction Reference')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_date')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('RWF')
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money' => 'Mobile Money',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'cash' => 'success',
                        'bank_transfer' => 'info',
                        'mobile_money' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reference')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('receivedBy.name')
                    ->label('Received By')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Record Payment')
                    ->icon('heroicon-o-banknotes')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['received_by'] = auth()->id();
                        return $data;
                    })
                    ->before(function (array $data) {
                        /** @var SalesOrder $salesOrder */
                        $salesOrder = $this->getOwnerRecord();
                        $remainingAmount = $salesOrder->remaining_amount;

                        if ($data['amount'] > $remainingAmount) {
                            Notification::make()
                                ->title('Payment exceeds remaining amount')
                                ->body("The remaining amount is RWF " . number_format($remainingAmount, 0) . ". Please enter a valid amount.")
                                ->danger()
                                ->send();

                            $this->halt();
                        }
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Payment Recorded')
                            ->body("Payment of RWF " . number_format($record->amount, 0) . " has been recorded.")
                            ->success()
                            ->send();
                    })
                    ->visible(function () {
                        /** @var SalesOrder $salesOrder */
                        $salesOrder = $this->getOwnerRecord();
                        return $salesOrder->remaining_amount > 0 && auth()->user()?->can('create_sales_order_payments');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['received_by'] = auth()->id();
                        return $data;
                    })
                    ->visible(fn () => auth()->user()?->can('edit_sales_order_payments')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can('delete_sales_order_payments')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->can('delete_sales_order_payments')),
            ])
            ->defaultSort('payment_date', 'desc')
            ->emptyStateHeading('No payments recorded')
            ->emptyStateDescription('Record payments as the customer pays in installments.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}

