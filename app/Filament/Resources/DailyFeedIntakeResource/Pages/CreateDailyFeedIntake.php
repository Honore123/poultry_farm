<?php

namespace App\Filament\Resources\DailyFeedIntakeResource\Pages;

use App\Filament\Resources\DailyFeedIntakeResource;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateDailyFeedIntake extends CreateRecord
{
    protected static string $resource = DailyFeedIntakeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Keep the inventory_lot_id in the data to save to the model
        unset($data['available_stock']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $lotId = $this->record->inventory_lot_id;
        
        if (!$lotId) {
            return;
        }

        $kgGiven = floatval($this->record->kg_given);
        $batchId = $this->record->batch_id;
        $date = $this->record->date;

        DB::transaction(function () use ($lotId, $kgGiven, $batchId, $date) {
            $lot = InventoryLot::lockForUpdate()->find($lotId);
            
            if ($lot && $lot->qty_on_hand >= $kgGiven) {
                $lot->qty_on_hand -= $kgGiven;
                $lot->save();
                
                InventoryMovement::create([
                    'lot_id' => $lotId,
                    'ts' => $date,
                    'direction' => 'out',
                    'qty' => $kgGiven,
                    'reference' => 'feed_consumption',
                    'batch_id' => $batchId,
                ]);
                
                Notification::make()
                    ->title('Inventory updated')
                    ->body("{$kgGiven} kg deducted from {$lot->item->name}")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Inventory warning')
                    ->body('Could not deduct from inventory - insufficient stock or lot not found')
                    ->warning()
                    ->send();
            }
        });
    }
}
