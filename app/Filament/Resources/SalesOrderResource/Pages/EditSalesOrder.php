<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $data = $this->data;
        $record = $this->record;
        $originalStatus = $record->getOriginal('status');
        $newStatus = $data['status'];

        // Check if status is being changed to confirmed
        if ($newStatus === 'confirmed' && $originalStatus !== 'confirmed') {
            $this->validateEggAvailability();
        }
    }

    protected function validateEggAvailability(): void
    {
        // Calculate eggs in this order from form data
        $items = $this->data['items'] ?? [];
        $orderEggs = 0;

        foreach ($items as $item) {
            $product = strtolower($item['product'] ?? '');
            if (str_contains($product, 'egg')) {
                $qty = (int) ($item['qty'] ?? 0);
                $uom = strtolower($item['uom'] ?? '');
                
                if ($uom === 'tray' || $uom === 'trays') {
                    $orderEggs += $qty * 30;
                } else {
                    $orderEggs += $qty;
                }
            }
        }

        if ($orderEggs > 0) {
            $availableEggs = SalesOrder::getAvailableEggs();

            if ($orderEggs > $availableEggs) {
                Notification::make()
                    ->danger()
                    ->title('Insufficient Eggs')
                    ->body("This order requires {$orderEggs} eggs but only {$availableEggs} are available.")
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }
    }
}
