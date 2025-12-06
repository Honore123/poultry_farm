<?php

namespace App\Filament\Resources\SalesOrderItemResource\Pages;

use App\Filament\Resources\SalesOrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrderItem extends EditRecord
{
    protected static string $resource = SalesOrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
