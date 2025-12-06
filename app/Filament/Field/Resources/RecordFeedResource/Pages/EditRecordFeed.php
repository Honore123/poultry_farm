<?php

namespace App\Filament\Field\Resources\RecordFeedResource\Pages;

use App\Filament\Field\Resources\RecordFeedResource;
use Filament\Resources\Pages\EditRecord;

class EditRecordFeed extends EditRecord
{
    protected static string $resource = RecordFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

