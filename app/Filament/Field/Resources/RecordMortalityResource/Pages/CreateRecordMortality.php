<?php

namespace App\Filament\Field\Resources\RecordMortalityResource\Pages;

use App\Filament\Field\Resources\RecordMortalityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecordMortality extends CreateRecord
{
    protected static string $resource = RecordMortalityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Mortality recorded successfully!';
    }
}

