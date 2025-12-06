<?php

namespace App\Filament\Resources\DailyFeedIntakeResource\Pages;

use App\Filament\Resources\DailyFeedIntakeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyFeedIntakes extends ListRecords
{
    protected static string $resource = DailyFeedIntakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
