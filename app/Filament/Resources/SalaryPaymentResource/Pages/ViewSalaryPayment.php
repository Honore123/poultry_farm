<?php

namespace App\Filament\Resources\SalaryPaymentResource\Pages;

use App\Filament\Resources\SalaryPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalaryPayment extends ViewRecord
{
    protected static string $resource = SalaryPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

