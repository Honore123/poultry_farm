<?php

namespace App\Filament\Resources\FeedIntakeTargetResource\Pages;

use App\Filament\Resources\FeedIntakeTargetResource;
use App\Tenancy\TenantContext;
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

    public function getSubheading(): ?string
    {
        $context = app(TenantContext::class);

        if ($context->currentTenantId() || !$context->isSuperAdmin()) {
            return null;
        }

        return 'Template mode: editing global defaults used for new tenants.';
    }
}
