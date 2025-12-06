<?php

namespace App\Filament\Resources\WeightSampleResource\Pages;

use App\Filament\Resources\WeightSampleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeightSample extends EditRecord
{
    protected static string $resource = WeightSampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
