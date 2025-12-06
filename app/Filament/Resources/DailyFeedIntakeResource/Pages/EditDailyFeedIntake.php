<?php

namespace App\Filament\Resources\DailyFeedIntakeResource\Pages;

use App\Filament\Resources\DailyFeedIntakeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyFeedIntake extends EditRecord
{
    protected static string $resource = DailyFeedIntakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
