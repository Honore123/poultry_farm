<?php

namespace App\Filament\Field\Resources\RecordWaterResource\Pages;

use App\Filament\Field\Resources\RecordWaterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecordWater extends CreateRecord
{
    protected static string $resource = RecordWaterResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Water usage recorded successfully!';
    }
}

