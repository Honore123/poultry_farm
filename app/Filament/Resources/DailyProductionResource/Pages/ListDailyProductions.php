<?php

namespace App\Filament\Resources\DailyProductionResource\Pages;

use App\Filament\Resources\DailyProductionResource;
use App\Filament\Resources\DailyProductionResource\Widgets\EggQualityStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyProductions extends ListRecords
{
    protected static string $resource = DailyProductionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            EggQualityStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
