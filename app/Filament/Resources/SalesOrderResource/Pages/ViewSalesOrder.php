<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesOrder extends ViewRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('record_payment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('payment_date')
                        ->required()
                        ->default(now())
                        ->native(false),
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn () => $this->record->remaining_amount)
                        ->prefix('RWF')
                        ->default(fn () => $this->record->remaining_amount)
                        ->helperText(fn () => "Remaining: RWF " . number_format($this->record->remaining_amount, 0)),
                    \Filament\Forms\Components\Select::make('payment_method')
                        ->options([
                            'cash' => 'Cash',
                            'bank_transfer' => 'Bank Transfer',
                            'mobile_money' => 'Mobile Money',
                        ])
                        ->default('cash')
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('reference')
                        ->label('Transaction Reference'),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->payments()->create([
                        ...$data,
                        'received_by' => auth()->id(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Payment Recorded')
                        ->body("Payment of RWF " . number_format($data['amount'], 0) . " has been recorded.")
                        ->success()
                        ->send();

                    // Refresh the page to show updated data
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->visible(fn () => $this->record->remaining_amount > 0 && auth()->user()?->can('create_sales_order_payments')),
            Actions\EditAction::make(),
        ];
    }
}

