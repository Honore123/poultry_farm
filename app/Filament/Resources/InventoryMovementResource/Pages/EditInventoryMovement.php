<?php

namespace App\Filament\Resources\InventoryMovementResource\Pages;

use App\Filament\Resources\InventoryMovementResource;
use App\Models\InventoryLot;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditInventoryMovement extends EditRecord
{
    protected static string $resource = InventoryMovementResource::class;

    protected ?string $oldDirection = null;
    protected ?float $oldQty = null;
    protected ?int $oldLotId = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Store original values before editing
        $this->oldDirection = $this->record->direction;
        $this->oldQty = (float) $this->record->qty;
        $this->oldLotId = $this->record->lot_id;
        
        return $data;
    }

    protected function afterSave(): void
    {
        $movement = $this->record;
        
        DB::transaction(function () use ($movement) {
            // If lot changed, we need to update both lots
            if ($this->oldLotId !== $movement->lot_id) {
                // Reverse the old movement on the old lot
                $oldLot = InventoryLot::lockForUpdate()->find($this->oldLotId);
                if ($oldLot) {
                    if ($this->oldDirection === 'in') {
                        $oldLot->qty_on_hand -= $this->oldQty;
                    } else {
                        $oldLot->qty_on_hand += $this->oldQty;
                    }
                    $oldLot->save();
                }
                
                // Apply the new movement on the new lot
                $newLot = InventoryLot::lockForUpdate()->find($movement->lot_id);
                if ($newLot) {
                    if ($movement->direction === 'in') {
                        $newLot->qty_on_hand += $movement->qty;
                    } else {
                        $newLot->qty_on_hand -= $movement->qty;
                    }
                    $newLot->save();
                    
                    Notification::make()
                        ->title('Inventory updated')
                        ->body("Lot changed. New lot stock: {$newLot->qty_on_hand} {$newLot->uom}")
                        ->success()
                        ->send();
                }
            } else {
                // Same lot - calculate the difference
                $lot = InventoryLot::lockForUpdate()->find($movement->lot_id);
                
                if ($lot) {
                    // First, reverse the old movement
                    if ($this->oldDirection === 'in') {
                        $lot->qty_on_hand -= $this->oldQty;
                    } else {
                        $lot->qty_on_hand += $this->oldQty;
                    }
                    
                    // Then apply the new movement
                    if ($movement->direction === 'in') {
                        $lot->qty_on_hand += $movement->qty;
                    } else {
                        $lot->qty_on_hand -= $movement->qty;
                    }
                    
                    $lot->save();
                    
                    Notification::make()
                        ->title('Inventory updated')
                        ->body("Stock adjusted. Current: {$lot->qty_on_hand} {$lot->uom}")
                        ->success()
                        ->send();
                }
            }
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Reverse the movement effect before deleting
                    DB::transaction(function () {
                        $lot = InventoryLot::lockForUpdate()->find($this->record->lot_id);
                        
                        if ($lot) {
                            if ($this->record->direction === 'in') {
                                $lot->qty_on_hand -= $this->record->qty;
                            } else {
                                $lot->qty_on_hand += $this->record->qty;
                            }
                            $lot->save();
                            
                            Notification::make()
                                ->title('Inventory reversed')
                                ->body("Movement deleted. Stock adjusted to: {$lot->qty_on_hand} {$lot->uom}")
                                ->info()
                                ->send();
                        }
                    });
                }),
        ];
    }
}
