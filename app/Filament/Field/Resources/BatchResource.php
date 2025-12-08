<?php

namespace App\Filament\Field\Resources;

use App\Filament\Field\Pages\DailyDataEntry;
use App\Filament\Field\Resources\BatchResource\Pages;
use App\Models\Batch;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'My Batches';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Batch::query()->whereIn('status', ['brooding', 'growing', 'laying'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Batch')
                    ->searchable()
                    ->sortable()
                    ->size('lg')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('farm.name')
                    ->label('Farm'),
                Tables\Columns\TextColumn::make('house.name')
                    ->label('House'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'brooding',
                        'info' => 'growing',
                        'success' => 'laying',
                    ]),
                Tables\Columns\TextColumn::make('placement_qty')
                    ->label('Birds Placed')
                    ->numeric(),
                Tables\Columns\TextColumn::make('age')
                    ->label('Age')
                    ->getStateUsing(fn (Batch $record) => (int) $record->placement_date->diffInWeeks(now()) . ' weeks'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('daily_activities')
                    ->label('Daily Activities')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->url(fn (Batch $record) => DailyDataEntry::getUrl(['batch' => $record->id])),
                Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('record_eggs')
                    ->label('Eggs')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn (Batch $record) => RecordEggsResource::getUrl('create', ['batch' => $record->id])),
                Tables\Actions\Action::make('record_feed')
                    ->label('Feed')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->url(fn (Batch $record) => RecordFeedResource::getUrl('create', ['batch' => $record->id])),
                Tables\Actions\Action::make('record_water')
                    ->label('Water')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->url(fn (Batch $record) => RecordWaterResource::getUrl('create', ['batch' => $record->id])),
                Tables\Actions\Action::make('record_mortality')
                    ->label('Mortality')
                    ->icon('heroicon-o-plus-circle')
                    ->color('danger')
                    ->url(fn (Batch $record) => RecordMortalityResource::getUrl('create', ['batch' => $record->id])),
                ])
                    ->label('Add Individual')
                    ->icon('heroicon-o-plus-circle')
                    ->color('gray'),
            ])
            ->bulkActions([])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatches::route('/'),
        ];
    }
}

