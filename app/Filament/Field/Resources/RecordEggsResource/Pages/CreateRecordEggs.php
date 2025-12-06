<?php

namespace App\Filament\Field\Resources\RecordEggsResource\Pages;

use App\Filament\Field\Resources\RecordEggsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecordEggs extends CreateRecord
{
    protected static string $resource = RecordEggsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Egg production recorded successfully!';
    }
}

