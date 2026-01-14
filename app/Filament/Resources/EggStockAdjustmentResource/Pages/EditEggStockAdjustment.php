<?php

namespace App\Filament\Resources\EggStockAdjustmentResource\Pages;

use App\Filament\Resources\EggStockAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEggStockAdjustment extends EditRecord
{
    protected static string $resource = EggStockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

