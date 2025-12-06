<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyFeedIntakeResource\Pages;
use App\Models\DailyFeedIntake;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyFeedIntakeResource extends Resource
{
    protected static ?string $model = DailyFeedIntake::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feed Intake Record')
                    ->schema([
                        Forms\Components\Select::make('batch_id')
                            ->relationship('batch', 'code', fn ($query) => $query->whereIn('status', ['brooding', 'growing', 'laying']))
                            ->label('Batch')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->helperText('Cannot select future dates'),
                        Forms\Components\Select::make('feed_item_id')
                            ->relationship('feedItem', 'name', fn ($query) => $query->where('category', 'feed'))
                            ->label('Feed Type')
                            ->searchable()
                            ->preload()
                            ->helperText('Select from inventory feed items'),
                        Forms\Components\TextInput::make('kg_given')
                            ->label('Amount Given')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(10000)
                            ->step(0.01)
                            ->suffix('kg')
                            ->helperText('Between 0.01 and 10,000 kg'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch.code')
                    ->label('Batch')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('feedItem.name')
                    ->label('Feed')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kg_given')
                    ->label('Kg Given')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch')
                    ->relationship('batch', 'code'),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('date', today())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyFeedIntakes::route('/'),
            'create' => Pages\CreateDailyFeedIntake::route('/create'),
            'edit' => Pages\EditDailyFeedIntake::route('/{record}/edit'),
        ];
    }
}
