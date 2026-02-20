<?php

namespace App\Filament\Resources\ProductionTargetResource\Pages;

use App\Filament\Resources\ProductionTargetResource;
use App\Filament\Resources\ProductionTargetResource\Widgets\ProductionStatsWidget;
use App\Models\Batch;
use App\Models\MortalityLog;
use App\Tenancy\TenantContext;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;

class ListProductionTargets extends ListRecords
{
    protected static string $resource = ProductionTargetResource::class;

    public ?int $selectedBatchId = null;

    protected function getHeaderWidgets(): array
    {
        return [
            ProductionStatsWidget::make([
                'selectedBatchId' => $this->selectedBatchId,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('selectBatch')
                ->label($this->getSelectedBatchLabel())
                ->icon('heroicon-o-funnel')
                ->color($this->selectedBatchId ? 'success' : 'gray')
                ->form([
                    Select::make('batch_id')
                        ->label('Select Batch')
                        ->options(
                            Batch::whereIn('status', ['brooding', 'growing', 'laying'])
                                ->get()
                                ->mapWithKeys(fn ($batch) => [
                                    $batch->id => "{$batch->code} - {$batch->breed} ({$batch->placement_qty} birds)"
                                ])
                        )
                        ->placeholder('Choose a batch to calculate flock totals')
                        ->searchable()
                        ->default($this->selectedBatchId),
                ])
                ->action(function (array $data) {
                    $this->selectedBatchId = $data['batch_id'];
                    $this->resetTable();
                    $this->dispatch('production-batch-selected', batchId: $this->selectedBatchId);
                }),
            Actions\Action::make('clearBatch')
                ->label('Clear')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->selectedBatchId !== null)
                ->action(function () {
                    $this->selectedBatchId = null;
                    $this->resetTable();
                    $this->dispatch('production-batch-selected', batchId: null);
                }),
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

    protected function getSelectedBatchLabel(): string
    {
        if ($this->selectedBatchId) {
            $batch = Batch::find($this->selectedBatchId);
            return $batch ? "Batch: {$batch->code}" : 'Select Batch';
        }
        return 'Select Batch';
    }

    protected function getBirdsAlive(): int
    {
        if (!$this->selectedBatchId) {
            return 0;
        }

        $batch = Batch::find($this->selectedBatchId);
        if (!$batch) {
            return 0;
        }

        $totalMortality = MortalityLog::where('batch_id', $this->selectedBatchId)->sum('count');
        return $batch->placement_qty - $totalMortality;
    }

    public function getTableColumns(): array
    {
        $birdsAlive = $this->getBirdsAlive();

        $columns = [
            TextColumn::make('week')
                ->label('Week')
                ->sortable()
                ->badge()
                ->color('success'),
            TextColumn::make('hen_day_production_pct')
                ->label('HD Prod %')
                ->numeric(decimalPlaces: 1)
                ->suffix('%')
                ->sortable(),
            TextColumn::make('feed_intake_per_day_g')
                ->label('g/bird/day')
                ->numeric()
                ->suffix(' g'),
            TextColumn::make('kg_per_week')
                ->label('kg/bird/week')
                ->state(fn ($record) => $record->feed_intake_per_day_g 
                    ? round($record->feed_intake_per_day_g * 7 / 1000, 3) 
                    : null)
                ->numeric(decimalPlaces: 3)
                ->suffix(' kg')
                ->color('info'),
        ];

        // Add flock total column when a batch is selected
        if ($this->selectedBatchId && $birdsAlive > 0) {
            $columns[] = TextColumn::make('flock_kg_week')
                ->label("Flock kg/week ({$birdsAlive} birds)")
                ->state(fn ($record) => $record->feed_intake_per_day_g 
                    ? round($record->feed_intake_per_day_g * 7 * $birdsAlive / 1000, 1) 
                    : null)
                ->numeric(decimalPlaces: 1)
                ->suffix(' kg')
                ->color('success')
                ->weight('bold');
        }

        $columns = array_merge($columns, [
            TextColumn::make('avg_egg_weight_g')
                ->label('Egg Wt')
                ->numeric(decimalPlaces: 1)
                ->suffix(' g')
                ->toggleable(),
            TextColumn::make('fcr_week')
                ->label('FCR')
                ->numeric(decimalPlaces: 2)
                ->toggleable(),
            TextColumn::make('livability_pct')
                ->label('Livability')
                ->numeric(decimalPlaces: 1)
                ->suffix('%')
                ->toggleable(),
            TextColumn::make('body_weight_g')
                ->label('Body Wt')
                ->numeric()
                ->suffix(' g')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('cum_feed_kg')
                ->label('Cum Feed')
                ->numeric(decimalPlaces: 1)
                ->suffix(' kg')
                ->toggleable(isToggledHiddenByDefault: true),
        ]);

        return $columns;
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns($this->getTableColumns())
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('week', 'asc');
    }
}
