<?php

namespace App\Filament\Resources\SalesOrderItemResource\Pages;

use App\Filament\Resources\SalesOrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesOrderItems extends ListRecords
{
    protected static string $resource = SalesOrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
