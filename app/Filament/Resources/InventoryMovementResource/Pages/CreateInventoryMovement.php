<?php

namespace App\Filament\Resources\InventoryMovementResource\Pages;

use App\Filament\Resources\InventoryMovementResource;
use App\Models\InventoryLot;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateInventoryMovement extends CreateRecord
{
    protected static string $resource = InventoryMovementResource::class;

    protected function afterCreate(): void
    {
        $movement = $this->record;
        
        DB::transaction(function () use ($movement) {
            $lot = InventoryLot::lockForUpdate()->find($movement->lot_id);
            
            if ($lot) {
                if ($movement->direction === 'in') {
                    $lot->qty_on_hand += $movement->qty;
                    $lot->save();
                    
                    Notification::make()
                        ->title('Inventory updated')
                        ->body("Added {$movement->qty} {$lot->uom}. New stock: {$lot->qty_on_hand} {$lot->uom}")
                        ->success()
                        ->send();
                } elseif ($movement->direction === 'out') {
                    if ($lot->qty_on_hand >= $movement->qty) {
                        $lot->qty_on_hand -= $movement->qty;
                        $lot->save();
                        
                        Notification::make()
                            ->title('Inventory updated')
                            ->body("Deducted {$movement->qty} {$lot->uom}. Remaining: {$lot->qty_on_hand} {$lot->uom}")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Warning: Negative stock')
                            ->body("Stock went negative. Current: {$lot->qty_on_hand} {$lot->uom}, Deducted: {$movement->qty} {$lot->uom}")
                            ->warning()
                            ->send();
                        
                        // Still deduct even if it goes negative (admin should be able to correct inventory)
                        $lot->qty_on_hand -= $movement->qty;
                        $lot->save();
                    }
                }
            }
        });
    }
}
