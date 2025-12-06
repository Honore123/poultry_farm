<?php

namespace App\Filament\Resources\SalesOrderItemResource\Pages;

use App\Filament\Resources\SalesOrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrderItem extends CreateRecord
{
    protected static string $resource = SalesOrderItemResource::class;
}
