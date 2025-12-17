<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->data;

        // Check if creating as confirmed
        if (($data['status'] ?? 'draft') === 'confirmed') {
            $this->validateEggAvailability();
        }
    }

    protected function validateEggAvailability(): void
    {
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
