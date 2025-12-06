<?php

namespace App\Filament\Resources\DailyProductionResource\Pages;

use App\Filament\Resources\DailyProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyProductions extends ListRecords
{
    protected static string $resource = DailyProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
