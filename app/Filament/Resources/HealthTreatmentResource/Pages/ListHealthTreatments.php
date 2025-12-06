<?php

namespace App\Filament\Resources\HealthTreatmentResource\Pages;

use App\Filament\Resources\HealthTreatmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHealthTreatments extends ListRecords
{
    protected static string $resource = HealthTreatmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
