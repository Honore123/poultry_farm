<?php

namespace App\Filament\Field\Resources\BatchResource\Pages;

use App\Filament\Field\Resources\BatchResource;
use Filament\Resources\Pages\ListRecords;

class ListBatches extends ListRecords
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

