<?php

namespace App\Filament\Resources\DailyFeedIntakeResource\Pages;

use App\Filament\Resources\DailyFeedIntakeResource;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditDailyFeedIntake extends EditRecord
{
    protected static string $resource = DailyFeedIntakeResource::class;

    protected ?float $originalKgGiven = null;
    protected ?int $originalLotId = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Store original values from the record
        $this->originalKgGiven = floatval($data['kg_given'] ?? 0);
        $this->originalLotId = $data['inventory_lot_id'] ?? null;
        
        // Set available stock for the form
        if ($this->originalLotId) {
            $lot = InventoryLot::find($this->originalLotId);
            if ($lot) {
                // Show available including what was already taken
                $data['available_stock'] = $lot->qty_on_hand + $this->originalKgGiven;
            }
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Keep the inventory_lot_id, just remove available_stock
        unset($data['available_stock']);
        
        return $data;
    }

    protected function afterSave(): void
    {
        $newKgGiven = floatval($this->record->kg_given);
        $newLotId = $this->record->inventory_lot_id;
        $batchId = $this->record->batch_id;
        $date = $this->record->date;

        // Skip if nothing changed
        $lotChanged = $this->originalLotId != $newLotId;
        $qtyChanged = abs($this->originalKgGiven - $newKgGiven) > 0.001; // Use tolerance for float comparison
        
        if (!$lotChanged && !$qtyChanged) {
            return; // Nothing changed, no inventory adjustment needed
        }

        DB::transaction(function () use ($newKgGiven, $newLotId, $batchId, $date, $lotChanged, $qtyChanged) {
            // Case 1: Same lot, just quantity changed
            if ($this->originalLotId && !$lotChanged && $qtyChanged) {
                $lot = InventoryLot::lockForUpdate()->find($this->originalLotId);
                
                if ($lot) {
                    $difference = $newKgGiven - $this->originalKgGiven;
                    
                    if ($difference > 0) {
                        if ($lot->qty_on_hand >= $difference) {
                            $lot->qty_on_hand -= $difference;
                            $lot->save();
                            
                            // Update the movement record
                            InventoryMovement::where('lot_id', $this->originalLotId)
                                ->where('batch_id', $batchId)
                                ->where('reference', 'feed_consumption')
                                ->whereDate('ts', $date)
                                ->update(['qty' => $newKgGiven]);
                            
                            Notification::make()
                                ->title('Inventory updated')
                                ->body("Additional {$difference} kg deducted from {$lot->item->name}")
                                ->success()
                                ->send();
                        }
                    } else {
                        $restoreAmount = abs($difference);
                        $lot->qty_on_hand += $restoreAmount;
                        $lot->save();
                        
                        // Update the movement record
                        InventoryMovement::where('lot_id', $this->originalLotId)
                            ->where('batch_id', $batchId)
                            ->where('reference', 'feed_consumption')
                            ->whereDate('ts', $date)
                            ->update(['qty' => $newKgGiven]);
                        
                        Notification::make()
                            ->title('Inventory updated')
                            ->body("{$restoreAmount} kg restored to {$lot->item->name}")
                            ->success()
                            ->send();
                    }
                }
            }
            // Case 2: Lot changed (had original lot) - restore original and deduct from new
            elseif ($this->originalLotId && $lotChanged && $newLotId) {
                // Restore to original lot
                $originalLot = InventoryLot::lockForUpdate()->find($this->originalLotId);
                if ($originalLot) {
                    $originalLot->qty_on_hand += $this->originalKgGiven;
                    $originalLot->save();
                    
                    // Delete old movement
                    InventoryMovement::where('lot_id', $this->originalLotId)
                        ->where('batch_id', $batchId)
                        ->where('reference', 'feed_consumption')
                        ->whereDate('ts', $date)
                        ->delete();
                }
                
                // Deduct from new lot
                $newLot = InventoryLot::lockForUpdate()->find($newLotId);
                if ($newLot && $newLot->qty_on_hand >= $newKgGiven) {
                    $newLot->qty_on_hand -= $newKgGiven;
                    $newLot->save();
                    
                    InventoryMovement::create([
                        'lot_id' => $newLotId,
                        'ts' => $date,
                        'direction' => 'out',
                        'qty' => $newKgGiven,
                        'reference' => 'feed_consumption',
                        'batch_id' => $batchId,
                    ]);
                    
                    Notification::make()
                        ->title('Inventory updated')
                        ->body("Switched from {$originalLot->item->name} to {$newLot->item->name}")
                        ->success()
                        ->send();
                }
            }
            // Case 3: No original lot but new lot selected - check if movement already exists
            elseif (!$this->originalLotId && $newLotId) {
                // Check if a movement already exists for this record to avoid double-deducting
                $existingMovement = InventoryMovement::where('lot_id', $newLotId)
                    ->where('batch_id', $batchId)
                    ->where('reference', 'feed_consumption')
                    ->whereDate('ts', $date)
                    ->first();
                
                if ($existingMovement) {
                    // Movement exists, just update if quantity changed
                    if ($qtyChanged) {
                        $lot = InventoryLot::lockForUpdate()->find($newLotId);
                        if ($lot) {
                            $difference = $newKgGiven - $existingMovement->qty;
                            $lot->qty_on_hand -= $difference;
                            $lot->save();
                            $existingMovement->update(['qty' => $newKgGiven]);
                        }
                    }
                    return;
                }
                
                // No existing movement, create new one
                $lot = InventoryLot::lockForUpdate()->find($newLotId);
                
                if ($lot && $lot->qty_on_hand >= $newKgGiven) {
                    $lot->qty_on_hand -= $newKgGiven;
                    $lot->save();
                    
                    InventoryMovement::create([
                        'lot_id' => $newLotId,
                        'ts' => $date,
                        'direction' => 'out',
                        'qty' => $newKgGiven,
                        'reference' => 'feed_consumption',
                        'batch_id' => $batchId,
                    ]);
                    
                    Notification::make()
                        ->title('Inventory updated')
                        ->body("{$newKgGiven} kg deducted from {$lot->item->name}")
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
                    // Restore inventory when deleting
                    if ($this->originalLotId && $this->originalKgGiven > 0) {
                        DB::transaction(function () {
                            $lot = InventoryLot::lockForUpdate()->find($this->originalLotId);
                            
                            if ($lot) {
                                $lot->qty_on_hand += $this->originalKgGiven;
                                $lot->save();
                                
                                // Delete the movement record
                                InventoryMovement::where('batch_id', $this->record->batch_id)
                                    ->where('reference', 'feed_consumption')
                                    ->whereDate('ts', $this->record->date)
                                    ->where('qty', $this->originalKgGiven)
                                    ->delete();
                                
                                Notification::make()
                                    ->title('Inventory restored')
                                    ->body("{$this->originalKgGiven} kg restored to {$lot->item->name}")
                                    ->success()
                                    ->send();
                            }
                        });
                    }
                }),
        ];
    }
}
