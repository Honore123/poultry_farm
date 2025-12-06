<?php

namespace App\Filament\Field\Resources\RecordFeedResource\Pages;

use App\Filament\Field\Resources\RecordFeedResource;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateRecordFeed extends CreateRecord
{
    protected static string $resource = RecordFeedResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove fields not in the model
        unset($data['inventory_lot_id']);
        unset($data['available_stock']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $lotId = $this->data['inventory_lot_id'] ?? null;
        $kgGiven = $this->record->kg_given;
        
        if ($lotId) {
            DB::transaction(function () use ($lotId, $kgGiven) {
                $lot = InventoryLot::lockForUpdate()->find($lotId);
                
                if ($lot && $lot->qty_on_hand >= $kgGiven) {
                    // Deduct from inventory
                    $lot->qty_on_hand -= $kgGiven;
                    $lot->save();
                    
                    // Create inventory movement record
                    InventoryMovement::create([
                        'lot_id' => $lotId,
                        'ts' => $this->record->date,
                        'direction' => 'out',
                        'qty' => $kgGiven,
                        'reference' => 'feed_consumption',
                        'batch_id' => $this->record->batch_id,
                    ]);
                    
                    Notification::make()
                        ->title('Inventory updated!')
                        ->body("{$kgGiven} kg deducted from inventory. Remaining: {$lot->qty_on_hand} kg")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Inventory warning')
                        ->body('Could not deduct from inventory - insufficient stock')
                        ->warning()
                        ->send();
                }
            });
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Feed consumption recorded successfully!';
    }
}
