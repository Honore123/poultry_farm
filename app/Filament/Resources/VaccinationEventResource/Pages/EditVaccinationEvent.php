<?php

namespace App\Filament\Resources\VaccinationEventResource\Pages;

use App\Filament\Resources\VaccinationEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVaccinationEvent extends EditRecord
{
    protected static string $resource = VaccinationEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
