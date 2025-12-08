<?php

namespace App\Filament\Resources\ProductionTargetResource\Pages;

use App\Filament\Resources\ProductionTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionTarget extends EditRecord
{
    protected static string $resource = ProductionTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

