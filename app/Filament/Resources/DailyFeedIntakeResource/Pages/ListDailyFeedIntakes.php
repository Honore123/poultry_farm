<?php

namespace App\Filament\Resources\DailyFeedIntakeResource\Pages;

use App\Filament\Resources\DailyFeedIntakeResource;
use App\Filament\Resources\DailyFeedIntakeResource\Widgets\FeedIntakeStatsWidget;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDailyFeedIntakes extends ListRecords
{
    protected static string $resource = DailyFeedIntakeResource::class;

    public ?string $filterFromDate = null;
    public ?string $filterUntilDate = null;
    public bool $filterActive = false;

    public function mount(): void
    {
        parent::mount();
        
        // Default to current month
        $this->filterFromDate = now()->startOfMonth()->format('Y-m-d');
        $this->filterUntilDate = now()->format('Y-m-d');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FeedIntakeStatsWidget::make([
                'fromDate' => $this->filterFromDate,
                'untilDate' => $this->filterUntilDate,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('filter')
                ->label('Filter by Period')
                ->icon('heroicon-o-funnel')
                ->color($this->filterActive ? 'primary' : 'gray')
                ->badge($this->filterActive ? 'Active' : null)
                ->form([
                    Section::make('Date Range')
                        ->schema([
                            DatePicker::make('from_date')
                                ->label('From Date')
                                ->default($this->filterFromDate),
                            DatePicker::make('until_date')
                                ->label('To Date')
                                ->default($this->filterUntilDate),
                        ])
                        ->columns(2),
                ])
                ->fillForm([
                    'from_date' => $this->filterFromDate,
                    'until_date' => $this->filterUntilDate,
                ])
                ->action(function (array $data): void {
                    $this->filterFromDate = $data['from_date'];
                    $this->filterUntilDate = $data['until_date'];
                    $this->filterActive = true;
                    
                    $this->dispatch('updateFeedIntakeWidgetFilters', 
                        fromDate: $this->filterFromDate, 
                        untilDate: $this->filterUntilDate
                    );
                    $this->resetTable();
                })
                ->modalHeading('Filter Daily Feed Intakes')
                ->modalSubmitActionLabel('Apply Filter')
                ->modalWidth('md'),

            Actions\Action::make('resetFilter')
                ->label('Reset')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->visible($this->filterActive)
                ->action(function (): void {
                    $this->filterFromDate = now()->startOfMonth()->format('Y-m-d');
                    $this->filterUntilDate = now()->format('Y-m-d');
                    $this->filterActive = false;
                    
                    $this->dispatch('updateFeedIntakeWidgetFilters', 
                        fromDate: $this->filterFromDate, 
                        untilDate: $this->filterUntilDate
                    );
                    $this->resetTable();
                }),

            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if ($this->filterFromDate) {
            $query->whereDate('date', '>=', $this->filterFromDate);
        }

        if ($this->filterUntilDate) {
            $query->whereDate('date', '<=', $this->filterUntilDate);
        }

        return $query;
    }
}
