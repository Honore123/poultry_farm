<?php

namespace App\Filament\Field\Resources\RecordEggsResource\Pages;

use App\Filament\Field\Resources\RecordEggsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecordEggs extends ListRecords
{
    protected static string $resource = RecordEggsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Record Eggs')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}

