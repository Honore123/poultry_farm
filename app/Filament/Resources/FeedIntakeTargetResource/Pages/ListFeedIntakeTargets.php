<?php

namespace App\Filament\Resources\FeedIntakeTargetResource\Pages;

use App\Filament\Resources\FeedIntakeTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeedIntakeTargets extends ListRecords
{
    protected static string $resource = FeedIntakeTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
