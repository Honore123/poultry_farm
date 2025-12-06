<?php

namespace App\Filament\Field\Resources\RecordMortalityResource\Pages;

use App\Filament\Field\Resources\RecordMortalityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecordMortality extends ListRecords
{
    protected static string $resource = RecordMortalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Record Mortality')
                ->icon('heroicon-o-plus-circle')
                ->color('danger'),
        ];
    }
}

