<?php

namespace App\Filament\Field\Resources\RecordMortalityResource\Pages;

use App\Filament\Field\Resources\RecordMortalityResource;
use Filament\Resources\Pages\EditRecord;

class EditRecordMortality extends EditRecord
{
    protected static string $resource = RecordMortalityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

