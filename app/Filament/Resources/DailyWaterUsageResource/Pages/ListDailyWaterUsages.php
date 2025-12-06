<?php

namespace App\Filament\Resources\DailyWaterUsageResource\Pages;

use App\Filament\Resources\DailyWaterUsageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyWaterUsages extends ListRecords
{
    protected static string $resource = DailyWaterUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
