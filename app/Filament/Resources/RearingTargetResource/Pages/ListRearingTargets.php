<?php

namespace App\Filament\Resources\RearingTargetResource\Pages;

use App\Filament\Resources\RearingTargetResource;
use App\Filament\Resources\RearingTargetResource\Widgets\RearingStatsWidget;
use App\Models\Batch;
use App\Models\MortalityLog;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ListRearingTargets extends ListRecords
{
    protected static string $resource = RearingTargetResource::class;

    public ?int $selectedBatchId = null;

    protected function getHeaderWidgets(): array
    {
        return [
            RearingStatsWidget::make([
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
                    $this->dispatch('rearing-batch-selected', batchId: $this->selectedBatchId);
                }),
            Actions\Action::make('clearBatch')
                ->label('Clear')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->selectedBatchId !== null)
                ->action(function () {
                    $this->selectedBatchId = null;
                    $this->resetTable();
                    $this->dispatch('rearing-batch-selected', batchId: null);
                }),
            Actions\CreateAction::make(),
        ];
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
        $batch = $this->selectedBatchId ? Batch::find($this->selectedBatchId) : null;

        $columns = [
            TextColumn::make('week')
                ->label('Week')
                ->sortable()
                ->badge()
                ->color('primary'),
            TextColumn::make('age_days_from')
                ->label('Days From')
                ->numeric()
                ->toggleable(),
            TextColumn::make('age_days_to')
                ->label('Days To')
                ->numeric()
                ->toggleable(),
            TextColumn::make('daily_feed_min_g')
                ->label('Min g/bird/day')
                ->numeric()
                ->suffix(' g'),
            TextColumn::make('daily_feed_max_g')
                ->label('Max g/bird/day')
                ->numeric()
                ->suffix(' g'),
            TextColumn::make('min_kg_per_week')
                ->label('Min kg/bird/week')
                ->state(fn ($record) => $record->daily_feed_min_g 
                    ? round($record->daily_feed_min_g * 7 / 1000, 3) 
                    : null)
                ->numeric(decimalPlaces: 3)
                ->suffix(' kg')
                ->color('info'),
            TextColumn::make('max_kg_per_week')
                ->label('Max kg/bird/week')
                ->state(fn ($record) => $record->daily_feed_max_g 
                    ? round($record->daily_feed_max_g * 7 / 1000, 3) 
                    : null)
                ->numeric(decimalPlaces: 3)
                ->suffix(' kg')
                ->color('info'),
        ];

        // Add flock total columns when a batch is selected
        if ($this->selectedBatchId && $birdsAlive > 0) {
            $columns[] = TextColumn::make('flock_min_kg_week')
                ->label("Min kg/week ({$birdsAlive} birds)")
                ->state(fn ($record) => $record->daily_feed_min_g 
                    ? round($record->daily_feed_min_g * 7 * $birdsAlive / 1000, 1) 
                    : null)
                ->numeric(decimalPlaces: 1)
                ->suffix(' kg')
                ->color('success')
                ->weight('bold');

            $columns[] = TextColumn::make('flock_max_kg_week')
                ->label("Max kg/week ({$birdsAlive} birds)")
                ->state(fn ($record) => $record->daily_feed_max_g 
                    ? round($record->daily_feed_max_g * 7 * $birdsAlive / 1000, 1) 
                    : null)
                ->numeric(decimalPlaces: 1)
                ->suffix(' kg')
                ->color('success')
                ->weight('bold');
        }

        $columns[] = TextColumn::make('body_weight_min_g')
            ->label('Weight Min')
            ->numeric()
            ->suffix(' g')
            ->toggleable(isToggledHiddenByDefault: true);
        
        $columns[] = TextColumn::make('body_weight_max_g')
            ->label('Weight Max')
            ->numeric()
            ->suffix(' g')
            ->toggleable(isToggledHiddenByDefault: true);

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
