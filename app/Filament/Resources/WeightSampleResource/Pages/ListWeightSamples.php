<?php

namespace App\Filament\Resources\WeightSampleResource\Pages;

use App\Filament\Resources\WeightSampleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeightSamples extends ListRecords
{
    protected static string $resource = WeightSampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
