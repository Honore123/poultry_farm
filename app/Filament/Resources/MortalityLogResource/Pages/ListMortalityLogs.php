<?php

namespace App\Filament\Resources\MortalityLogResource\Pages;

use App\Filament\Resources\MortalityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMortalityLogs extends ListRecords
{
    protected static string $resource = MortalityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
