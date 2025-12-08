<?php

namespace App\Filament\Resources\RearingTargetResource\Pages;

use App\Filament\Resources\RearingTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRearingTarget extends EditRecord
{
    protected static string $resource = RearingTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

