<?php

namespace App\Filament\Field\Resources\RecordWaterResource\Pages;

use App\Filament\Field\Resources\RecordWaterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecordWater extends ListRecords
{
    protected static string $resource = RecordWaterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Record Water')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}

