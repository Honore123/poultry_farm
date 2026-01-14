<?php

namespace App\Filament\Resources\EggStockAdjustmentResource\Pages;

use App\Filament\Resources\EggStockAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEggStockAdjustment extends ViewRecord
{
    protected static string $resource = EggStockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

