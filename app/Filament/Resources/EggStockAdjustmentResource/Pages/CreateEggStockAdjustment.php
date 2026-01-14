<?php

namespace App\Filament\Resources\EggStockAdjustmentResource\Pages;

use App\Filament\Resources\EggStockAdjustmentResource;
use App\Models\SalesOrder;
use Filament\Resources\Pages\CreateRecord;

class CreateEggStockAdjustment extends CreateRecord
{
    protected static string $resource = EggStockAdjustmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure we capture the current system count at creation time
        if (!isset($data['system_count']) || $data['system_count'] === null) {
            $data['system_count'] = SalesOrder::getAvailableEggs();
        }
        
        $data['adjusted_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

