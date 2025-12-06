<?php

namespace App\Filament\Field\Resources\RecordWaterResource\Pages;

use App\Filament\Field\Resources\RecordWaterResource;
use Filament\Resources\Pages\EditRecord;

class EditRecordWater extends EditRecord
{
    protected static string $resource = RecordWaterResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

