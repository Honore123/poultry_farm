<?php

namespace App\Filament\Resources\MortalityLogResource\Pages;

use App\Filament\Resources\MortalityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMortalityLog extends EditRecord
{
    protected static string $resource = MortalityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
