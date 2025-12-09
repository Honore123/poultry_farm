<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Sales & Finance';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Expense Details')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('category')
                            ->options(Expense::CATEGORIES)
                            ->searchable(),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('RWF '),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Allocation (Optional)')
                    ->schema([
                        Forms\Components\Select::make('farm_id')
                            ->relationship('farm', 'name')
                            ->label('Farm')
                            ->searchable(),
                        Forms\Components\Select::make('house_id')
                            ->relationship('house', 'name')
                            ->label('House')
                            ->searchable(),
                        Forms\Components\Select::make('batch_id')
                            ->relationship('batch', 'code')
                            ->label('Batch')
                            ->searchable(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('RWF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('farm.name')
                    ->label('Farm')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('batch.code')
                    ->label('Batch')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(Expense::CATEGORIES),
                Tables\Filters\SelectFilter::make('farm')
                    ->relationship('farm', 'name'),
                Tables\Filters\TernaryFilter::make('is_salary')
                    ->label('Salary Expenses')
                    ->queries(
                        true: fn (Builder $query) => $query->where('category', 'salary'),
                        false: fn (Builder $query) => $query->where('category', '!=', 'salary'),
                    ),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
