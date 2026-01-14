<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\EggStockAdjustmentResource;
use App\Filament\Resources\SalesOrderResource;
use App\Filament\Resources\SalesOrderResource\Widgets\EggInventoryWidget;
use App\Filament\Resources\SalesOrderResource\Widgets\SalesOrderStatsWidget;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = SalesOrderResource::class;

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
            SalesOrderStatsWidget::make([
                'fromDate' => $this->filterFromDate,
                'untilDate' => $this->filterUntilDate,
            ]),
            EggInventoryWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('filter')
                ->label('Filter by Date')
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
                    
                    $this->dispatch('updateSalesOrderWidgetFilters', 
                        fromDate: $this->filterFromDate, 
                        untilDate: $this->filterUntilDate
                    );
                    $this->resetTable();
                })
                ->modalHeading('Filter Sales Orders')
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
                    
                    $this->dispatch('updateSalesOrderWidgetFilters', 
                        fromDate: $this->filterFromDate, 
                        untilDate: $this->filterUntilDate
                    );
                    $this->resetTable();
                }),

            Actions\Action::make('stockAdjustment')
                ->label('Stock Adjustment')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('warning')
                ->url(EggStockAdjustmentResource::getUrl('create'))
                ->visible(fn () => auth()->user()?->can('create_egg_stock_adjustments')),

            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if ($this->filterFromDate) {
            $query->whereDate('order_date', '>=', $this->filterFromDate);
        }

        if ($this->filterUntilDate) {
            $query->whereDate('order_date', '<=', $this->filterUntilDate);
        }

        return $query;
    }
}
