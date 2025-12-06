<?php

namespace App\Filament\Resources\DailyWaterUsageResource\Pages;

use App\Filament\Resources\DailyWaterUsageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyWaterUsage extends EditRecord
{
    protected static string $resource = DailyWaterUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
