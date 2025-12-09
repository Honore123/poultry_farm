<?php

namespace App\Filament\Resources\SalaryPaymentResource\Pages;

use App\Filament\Resources\SalaryPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalaryPayment extends CreateRecord
{
    protected static string $resource = SalaryPaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['processed_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        // If status is paid, create expense record
        if ($this->record->status === 'paid') {
            $this->record->createExpenseRecord();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

