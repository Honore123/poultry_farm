<?php

namespace App\Filament\Field\Resources\RecordEggsResource\Pages;

use App\Filament\Field\Resources\RecordEggsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecordEggs extends EditRecord
{
    protected static string $resource = RecordEggsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

