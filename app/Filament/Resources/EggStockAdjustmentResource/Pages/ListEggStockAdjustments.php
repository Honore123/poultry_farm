<?php

namespace App\Filament\Resources\EggStockAdjustmentResource\Pages;

use App\Filament\Resources\EggStockAdjustmentResource;
use App\Filament\Resources\EggStockAdjustmentResource\Widgets\StockAdjustmentStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEggStockAdjustments extends ListRecords
{
    protected static string $resource = EggStockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Stock Adjustment'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StockAdjustmentStatsWidget::class,
        ];
    }
}

