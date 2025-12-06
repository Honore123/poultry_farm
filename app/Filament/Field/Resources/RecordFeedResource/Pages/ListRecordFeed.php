<?php

namespace App\Filament\Field\Resources\RecordFeedResource\Pages;

use App\Filament\Field\Resources\RecordFeedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecordFeed extends ListRecords
{
    protected static string $resource = RecordFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Record Feed')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}

