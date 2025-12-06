<?php

namespace App\Filament\Resources\InventoryLotResource\Pages;

use App\Filament\Resources\InventoryLotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryLot extends EditRecord
{
    protected static string $resource = InventoryLotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
