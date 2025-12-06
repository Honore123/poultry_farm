<?php

namespace App\Filament\Resources\VaccinationEventResource\Pages;

use App\Filament\Resources\VaccinationEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVaccinationEvents extends ListRecords
{
    protected static string $resource = VaccinationEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
