<?php

namespace App\Filament\Field\Pages;

use App\Models\Batch;
use App\Models\DailyFeedIntake;
use App\Models\DailyProduction;
use App\Models\DailyWaterUsage;
use App\Models\HealthTreatment;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\MortalityLog;
use App\Models\WeightSample;
use Closure;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class DailyDataEntry extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $title = 'Daily Activities';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.field.pages.daily-data-entry';

    public ?array $data = [];

    public bool $eggsSaved = false;
    public bool $feedSaved = false;
    public bool $waterSaved = false;
    public bool $mortalitySaved = false;

    public ?string $generatedMessage = null;
    public ?string $whatsappUrl = null;

    public function mount(): void
    {
        $batchId = request()->query('batch');
        
        $this->form->fill([
            'batch_id' => $batchId,
            'date' => now()->format('Y-m-d'),
            'eggs_total' => null,
            'eggs_cracked' => 0,
            'eggs_dirty' => 0,
            'eggs_soft' => 0,
            'eggs_small' => 0,
            'feed_entries' => [['inventory_lot_id' => null, 'kg_given' => null, 'feed_item_id' => null, 'available_stock' => null]],
            'liters_used' => null,
            'mortality_count' => null,
            'mortality_cause' => null,
            'climate_condition' => 'normal',
            'health_condition' => 'healthy',
            'received_hens' => 0,
            'isolation' => 0,
        ]);

        // Load existing data if batch is selected
        if ($batchId) {
            $this->loadExistingData();
            $this->loadInventoryData();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Select Batch & Date')
                    ->description('Choose the batch and date for data entry')
                    ->schema([
                        Forms\Components\Select::make('batch_id')
                            ->label('Select Batch')
                            ->options(
                                Batch::whereIn('status', ['brooding', 'growing', 'laying'])
                                    ->with(['farm', 'house'])
                                    ->get()
                                    ->mapWithKeys(fn ($batch) => [
                                        $batch->id => "{$batch->code} - {$batch->farm->name} / {$batch->house->name}"
                                    ])
                            )
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->resetSavedStatus();
                                $this->loadExistingData();
                                $this->loadInventoryData();
                                $this->generatedMessage = null;
                            })
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->resetSavedStatus();
                                $this->loadExistingData();
                                $this->loadInventoryData();
                                $this->generatedMessage = null;
                            })
                            ->columnSpan(1),
                    ])->columns(2),

                // ========== DATA ENTRY SECTIONS ==========
                Forms\Components\Section::make('🥚 Egg Production')
                    ->description('Record today\'s egg collection')
                    ->icon('heroicon-o-circle-stack')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(5)
                            ->schema([
                                Forms\Components\TextInput::make('eggs_total')
                                    ->label('Total Eggs')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100000)
                                    ->placeholder('Enter total eggs')
                                    ->extraInputAttributes(['class' => 'text-xl font-bold'])
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('eggs_cracked')
                                    ->label('Cracked')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('eggs_dirty')
                                    ->label('Dirty')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('eggs_soft')
                                    ->label('Soft Shell')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('eggs_small')
                                    ->label('Small')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Placeholder::make('eggs_status')
                            ->label('')
                            ->content(fn () => $this->eggsSaved 
                                ? '✅ Eggs saved successfully' 
                                : '')
                            ->visible(fn () => $this->eggsSaved),
                    ]),

                Forms\Components\Section::make('🌾 Feed Consumption')
                    ->description('Record today\'s feed given - will be deducted from inventory. Add multiple entries if mixing feeds.')
                    ->icon('heroicon-o-beaker')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('feed_entries')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('inventory_lot_id')
                                    ->label('Select Feed Lot')
                                    ->options(function (Get $get) {
                                        // Get all selected lot IDs from other entries
                                        $allEntries = $get('../../feed_entries') ?? [];
                                        $currentLotId = $get('inventory_lot_id');
                                        $selectedLotIds = collect($allEntries)
                                            ->pluck('inventory_lot_id')
                                            ->filter()
                                            ->reject(fn ($id) => $id == $currentLotId)
                                            ->toArray();

                                        return InventoryLot::whereHas('item', fn ($q) => $q->where('category', 'feed'))
                                            ->where('qty_on_hand', '>', 0)
                                            ->whereNotIn('id', $selectedLotIds)
                                            ->with('item')
                                            ->get()
                                            ->mapWithKeys(fn ($lot) => [
                                                $lot->id => "{$lot->item->name} - {$lot->lot_code} ({$lot->qty_on_hand} kg available)"
                                            ]);
                                    })
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $lot = InventoryLot::find($state);
                                            if ($lot) {
                                                $set('feed_item_id', $lot->item_id);
                                                $set('available_stock', $lot->qty_on_hand);
                                            }
                                        } else {
                                            $set('feed_item_id', null);
                                            $set('available_stock', null);
                                        }
                                    })
                                    ->helperText('Select from available feed inventory'),

                                Forms\Components\TextInput::make('kg_given')
                                    ->label('Feed Given (kg)')
                                    ->numeric()
                                    ->minValue(0.1)
                                    ->maxValue(fn (Get $get) => $get('available_stock') ?: 100000)
                                    ->step(0.01)
                                    ->suffix('kg')
                                    ->placeholder('Enter kg')
                                    ->extraInputAttributes(['class' => 'text-xl font-bold'])
                                    ->helperText(fn (Get $get) => $get('available_stock') 
                                        ? 'Available: ' . number_format($get('available_stock'), 1) . ' kg'
                                        : 'Select a feed lot first')
                                    ->live(debounce: 500),

                                Forms\Components\Hidden::make('feed_item_id'),
                                Forms\Components\Hidden::make('available_stock'),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add Another Feed')
                            ->reorderable(false)
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['inventory_lot_id']) && $state['inventory_lot_id'] 
                                    ? InventoryLot::find($state['inventory_lot_id'])?->item?->name ?? 'Feed Entry'
                                    : 'Feed Entry'
                            ),

                        Forms\Components\Placeholder::make('feed_status')
                            ->label('')
                            ->content(fn () => $this->feedSaved 
                                ? '✅ Feed saved successfully' 
                                : '')
                            ->visible(fn () => $this->feedSaved),
                    ]),

                Forms\Components\Section::make('💧 Water Usage')
                    ->description('Record today\'s water consumption')
                    ->icon('heroicon-o-beaker')
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('liters_used')
                            ->label('Water Used (Liters)')
                            ->numeric()
                            ->minValue(0.1)
                            ->maxValue(100000)
                            ->step(0.01)
                            ->suffix('L')
                            ->placeholder('Enter liters')
                            ->extraInputAttributes(['class' => 'text-xl font-bold'])
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('water_status')
                            ->label('')
                            ->content(fn () => $this->waterSaved 
                                ? '✅ Water saved successfully' 
                                : '')
                            ->visible(fn () => $this->waterSaved),
                    ]),

                Forms\Components\Section::make('💀 Mortality')
                    ->description('Record bird deaths (leave empty if none)')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('mortality_count')
                                    ->label('Number of Deaths')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Enter count')
                                    ->extraInputAttributes(['class' => 'text-xl font-bold'])
                                    ->rules([
                                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            if (!$value) return;
                                            
                                            $batchId = $get('batch_id');
                                            if (!$batchId) return;
                                            
                                            $batch = Batch::find($batchId);
                                            if (!$batch) return;
                                            
                                            $totalMortality = MortalityLog::where('batch_id', $batchId)->sum('count');
                                            $birdsAlive = $batch->placement_qty - $totalMortality;
                                            
                                            if ($value > $birdsAlive) {
                                                $fail("Cannot exceed birds alive ({$birdsAlive})");
                                            }
                                        },
                                    ])
                                    ->helperText(function (Get $get) {
                                        $batchId = $get('batch_id');
                                        if (!$batchId) return 'Select a batch first';
                                        
                                        $batch = Batch::find($batchId);
                                        if (!$batch) return '';
                                        
                                        $totalMortality = MortalityLog::where('batch_id', $batchId)->sum('count');
                                        $birdsAlive = $batch->placement_qty - $totalMortality;
                                        
                                        return "Birds alive: {$birdsAlive}";
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Select::make('mortality_cause')
                                    ->label('Cause of Death')
                                    ->options([
                                        'Disease' => 'Disease',
                                        'Heat stress' => 'Heat Stress',
                                        'Cold stress' => 'Cold Stress',
                                        'Predator' => 'Predator',
                                        'Accident' => 'Accident',
                                        'Cannibalism' => 'Cannibalism',
                                        'Prolapse' => 'Prolapse',
                                        'Unknown' => 'Unknown',
                                    ])
                                    ->searchable()
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Placeholder::make('mortality_status')
                            ->label('')
                            ->content(fn () => $this->mortalitySaved 
                                ? '✅ Mortality saved successfully' 
                                : '')
                            ->visible(fn () => $this->mortalitySaved),
                    ]),

                // ========== REPORT SECTIONS ==========
                Forms\Components\Section::make('📊 Daily Report Information')
                    ->description('Additional information for the daily WhatsApp report')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('received_hens')
                                    ->label('Received Hens Today')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\Select::make('climate_condition')
                                    ->label('Climate Condition')
                                    ->options([
                                        'hot' => 'Hot',
                                        'warm' => 'Warm',
                                        'normal' => 'Normal',
                                        'cold' => 'Cold',
                                        'rainy' => 'Rainy',
                                    ])
                                    ->default('normal'),

                                Forms\Components\Select::make('health_condition')
                                    ->label('Health Condition')
                                    ->options([
                                        'healthy' => 'Healthy',
                                        'sick' => 'Sick',
                                        'recovering' => 'Recovering',
                                        'under_treatment' => 'Under Treatment',
                                    ])
                                    ->default('healthy'),
                            ]),

                        Forms\Components\TextInput::make('isolation')
                            ->label('Isolation Count')
                            ->numeric()
                            ->default(0),
                    ]),

                Forms\Components\Section::make('Feed Stock Summary')
                    ->description('Auto-calculated from inventory records')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(5)
                            ->schema([
                                Forms\Components\Placeholder::make('feed_initial_stock_display')
                                    ->label('Initial Stock')
                                    ->content(fn (Get $get) => number_format($get('feed_initial_stock') ?? 0, 1) . ' kg'),
                                Forms\Components\Placeholder::make('feed_distributed_display')
                                    ->label('Distributed')
                                    ->content(function (Get $get) {
                                        $recorded = floatval($get('feed_distributed') ?? 0);
                                        $entering = 0;
                                        $feedEntries = $get('feed_entries') ?? [];
                                        foreach ($feedEntries as $entry) {
                                            $entering += floatval($entry['kg_given'] ?? 0);
                                        }
                                        return number_format($recorded + $entering, 1) . ' kg';
                                    }),
                                Forms\Components\Placeholder::make('feed_received_display')
                                    ->label('Received')
                                    ->content(function (Get $get) {
                                        $entering = floatval($get('received_qty') ?? 0);
                                        return number_format($entering, 1) . ' kg';
                                    }),
                                Forms\Components\Placeholder::make('feed_out_display')
                                    ->label('Feed Out')
                                    ->content(function (Get $get) {
                                        $recorded = floatval($get('feed_out_stock') ?? 0);
                                        $entering = floatval($get('disposal_qty') ?? 0);
                                        return number_format($recorded + $entering, 1) . ' kg';
                                    }),
                                Forms\Components\Placeholder::make('feed_closing_display')
                                    ->label('Closing Stock')
                                    ->content(function (Get $get) {
                                        $closingStock = floatval($get('feed_closing_stock') ?? 0);
                                        $feedEntering = 0;
                                        $feedEntries = $get('feed_entries') ?? [];
                                        foreach ($feedEntries as $entry) {
                                            $feedEntering += floatval($entry['kg_given'] ?? 0);
                                        }
                                        $feedOut = floatval($get('disposal_qty') ?? 0);
                                        $feedReceived = floatval($get('received_qty') ?? 0);
                                        return number_format($closingStock - $feedEntering - $feedOut + $feedReceived, 1) . ' kg';
                                    }),
                            ]),

                        Forms\Components\Hidden::make('feed_initial_stock'),
                        Forms\Components\Hidden::make('feed_distributed'),
                        Forms\Components\Hidden::make('feed_received_stock'),
                        Forms\Components\Hidden::make('feed_out_stock'),
                        Forms\Components\Hidden::make('feed_closing_stock'),
                    ]),

                Forms\Components\Section::make('Record Received Stock (Optional)')
                    ->description('Record feed received today (purchased, transferred in, returned, etc.)')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('received_lot_id')
                                    ->label('Add to Feed Lot')
                                    ->options(function () {
                                        return InventoryLot::whereHas('item', fn ($q) => $q->where('category', 'feed'))
                                            ->with('item')
                                            ->get()
                                            ->mapWithKeys(fn ($lot) => [
                                                $lot->id => "{$lot->item->name} - {$lot->lot_code} ({$lot->qty_on_hand} kg)"
                                            ]);
                                    })
                                    ->searchable()
                                    ->helperText('Select existing lot to add stock to'),
                                Forms\Components\TextInput::make('received_qty')
                                    ->label('Quantity (kg)')
                                    ->numeric()
                                    ->minValue(0.1)
                                    ->suffix('kg')
                                    ->live(debounce: 500),
                                Forms\Components\TextInput::make('received_reason')
                                    ->label('Source/Reason')
                                    ->placeholder('e.g., Purchased, Transferred in, Returned'),
                            ]),
                    ]),

                Forms\Components\Section::make('Record Feed Out (Optional)')
                    ->description('Record feed sent out of stock (disposed, lent to friends, transferred, etc.)')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('disposal_lot_id')
                                    ->label('Select Feed Lot')
                                    ->options(function () {
                                        return InventoryLot::whereHas('item', fn ($q) => $q->where('category', 'feed'))
                                            ->where('qty_on_hand', '>', 0)
                                            ->with('item')
                                            ->get()
                                            ->mapWithKeys(fn ($lot) => [
                                                $lot->id => "{$lot->item->name} - {$lot->lot_code} ({$lot->qty_on_hand} kg)"
                                            ]);
                                    })
                                    ->searchable(),
                                Forms\Components\TextInput::make('disposal_qty')
                                    ->label('Quantity (kg)')
                                    ->numeric()
                                    ->minValue(0.1)
                                    ->suffix('kg')
                                    ->live(debounce: 500),
                                Forms\Components\TextInput::make('disposal_reason')
                                    ->label('Reason')
                                    ->placeholder('e.g., Lent to friend, Disposed, Transferred'),
                            ]),
                    ]),

                Forms\Components\Section::make('Additional Treatments')
                    ->description('Add any vitamins or drugs administered today')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('additional_treatments')
                            ->schema([
                                Forms\Components\TextInput::make('product')
                                    ->label('Product Name')
                                    ->required()
                                    ->placeholder('e.g., Vitamin amino total'),
                                Forms\Components\TextInput::make('reason')
                                    ->label('Reason')
                                    ->placeholder('e.g., Vitamin supplement'),
                                Forms\Components\TextInput::make('dosage')
                                    ->label('Dosage')
                                    ->placeholder('e.g., 40gram or 5ml/L'),
                                Forms\Components\TextInput::make('duration_days')
                                    ->label('Duration (days)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('e.g., 3'),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Add Treatment'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function loadExistingData(): void
    {
        $batchId = $this->data['batch_id'] ?? null;
        $dateStr = $this->data['date'] ?? null;

        if (!$batchId || !$dateStr) {
            return;
        }

        $date = Carbon::parse($dateStr);

        // Load existing eggs data
        $eggs = DailyProduction::where('batch_id', $batchId)
            ->whereDate('date', $date)
            ->first();

        if ($eggs) {
            $this->data['eggs_total'] = $eggs->eggs_total;
            $this->data['eggs_cracked'] = $eggs->eggs_cracked;
            $this->data['eggs_dirty'] = $eggs->eggs_dirty;
            $this->data['eggs_soft'] = $eggs->eggs_soft;
            $this->data['eggs_small'] = $eggs->eggs_small;
        }

        // Load existing water data
        $water = DailyWaterUsage::where('batch_id', $batchId)
            ->whereDate('date', $date)
            ->first();

        if ($water) {
            $this->data['liters_used'] = $water->liters_used;
        }
    }

    public function loadInventoryData(): void
    {
        $batchId = $this->data['batch_id'] ?? null;
        $dateStr = $this->data['date'] ?? null;
        
        if (!$batchId || !$dateStr) {
            return;
        }

        $date = Carbon::parse($dateStr);
        
        // Get current total feed stock (all feed lots)
        $currentStock = InventoryLot::whereHas('item', fn ($q) => $q->where('category', 'feed'))
            ->sum('qty_on_hand');
        
        // Get feed distributed today for this batch
        $feedDistributed = DailyFeedIntake::where('batch_id', $batchId)
            ->whereDate('date', $date)
            ->sum('kg_given');
        
        // Get all feed movements for today
        $feedLotIds = InventoryLot::whereHas('item', fn ($q) => $q->where('category', 'feed'))
            ->pluck('id');
        
        // Received today (direction = 'in')
        $receivedToday = InventoryMovement::whereIn('lot_id', $feedLotIds)
            ->whereDate('ts', $date)
            ->where('direction', 'in')
            ->sum('qty');
        
        // Feed out/disposed today (direction = 'out' AND reference = 'disposed')
        $disposedToday = InventoryMovement::whereIn('lot_id', $feedLotIds)
            ->whereDate('ts', $date)
            ->where('direction', 'out')
            ->where('reference', 'disposed')
            ->sum('qty');
        
        // Feed consumption today (direction = 'out' AND reference = 'feed_consumption')
        $consumedToday = InventoryMovement::whereIn('lot_id', $feedLotIds)
            ->whereDate('ts', $date)
            ->where('direction', 'out')
            ->where('reference', 'feed_consumption')
            ->sum('qty');
        
        // Calculate initial stock (stock at start of day)
        $initialStock = $currentStock + $consumedToday + $disposedToday - $receivedToday;
        
        // Closing stock is current stock
        $closingStock = $currentStock;
        
        // Update form data
        $this->data['feed_initial_stock'] = $initialStock;
        $this->data['feed_distributed'] = $feedDistributed;
        $this->data['feed_received_stock'] = $receivedToday;
        $this->data['feed_out_stock'] = $disposedToday;
        $this->data['feed_closing_stock'] = $closingStock;
    }

    protected function resetSavedStatus(): void
    {
        $this->eggsSaved = false;
        $this->feedSaved = false;
        $this->waterSaved = false;
        $this->mortalitySaved = false;
    }

    public function saveAll(): void
    {
        $data = $this->form->getState();
        
        if (empty($data['batch_id'])) {
            Notification::make()
                ->title('Please select a batch first')
                ->danger()
                ->send();
            return;
        }

        $hasData = false;
        $savedItems = [];

        // Save eggs if provided
        if (!empty($data['eggs_total']) && $data['eggs_total'] > 0) {
            $this->saveEggs($data);
            $savedItems[] = 'eggs';
            $hasData = true;
        }

        // Save feed entries if provided
        if (!empty($data['feed_entries'])) {
            $feedCount = $this->saveFeedEntries($data);
            if ($feedCount > 0) {
                $savedItems[] = "{$feedCount} feed entry(s)";
                $hasData = true;
            }
        }

        // Save water if provided
        if (!empty($data['liters_used']) && $data['liters_used'] > 0) {
            $this->saveWater($data);
            $savedItems[] = 'water';
            $hasData = true;
        }

        // Save mortality if provided
        if (!empty($data['mortality_count']) && $data['mortality_count'] > 0) {
            $this->saveMortality($data);
            $savedItems[] = 'mortality';
            $hasData = true;
        }

        // Save additional treatments if provided
        if (!empty($data['additional_treatments'])) {
            $treatmentCount = $this->saveTreatments($data);
            if ($treatmentCount > 0) {
                $savedItems[] = "{$treatmentCount} treatment(s)";
                $hasData = true;
            }
        }

        // Save received stock if provided
        if (!empty($data['received_lot_id']) && !empty($data['received_qty']) && $data['received_qty'] > 0) {
            $this->recordReceivedStock($data);
            $savedItems[] = 'received stock';
            $hasData = true;
        }

        // Save feed disposal if provided
        if (!empty($data['disposal_lot_id']) && !empty($data['disposal_qty']) && $data['disposal_qty'] > 0) {
            $this->recordFeedDisposal($data);
            $savedItems[] = 'feed out';
            $hasData = true;
        }

        if (!$hasData) {
            Notification::make()
                ->title('No data to save')
                ->body('Please enter at least one value')
                ->warning()
                ->send();
            return;
        }

        // Reload inventory data after saving
        $this->loadInventoryData();

        Notification::make()
            ->title('Data saved successfully!')
            ->body('Saved: ' . implode(', ', $savedItems))
            ->success()
            ->send();

        // Clear entry fields after saving
        $this->data['feed_entries'] = [['inventory_lot_id' => null, 'kg_given' => null, 'feed_item_id' => null, 'available_stock' => null]];
        $this->data['mortality_count'] = null;
        $this->data['mortality_cause'] = null;
        $this->data['received_lot_id'] = null;
        $this->data['received_qty'] = null;
        $this->data['received_reason'] = null;
        $this->data['disposal_lot_id'] = null;
        $this->data['disposal_qty'] = null;
        $this->data['disposal_reason'] = null;
    }

    protected function saveEggs(array $data): void
    {
        $existing = DailyProduction::where('batch_id', $data['batch_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            $existing->update([
                'eggs_total' => $data['eggs_total'],
                'eggs_cracked' => $data['eggs_cracked'] ?? 0,
                'eggs_dirty' => $data['eggs_dirty'] ?? 0,
                'eggs_soft' => $data['eggs_soft'] ?? 0,
                'eggs_small' => $data['eggs_small'] ?? 0,
            ]);
        } else {
            DailyProduction::create([
                'batch_id' => $data['batch_id'],
                'date' => $data['date'],
                'eggs_total' => $data['eggs_total'],
                'eggs_cracked' => $data['eggs_cracked'] ?? 0,
                'eggs_dirty' => $data['eggs_dirty'] ?? 0,
                'eggs_soft' => $data['eggs_soft'] ?? 0,
                'eggs_small' => $data['eggs_small'] ?? 0,
            ]);
        }

        $this->eggsSaved = true;
    }

    protected function saveFeedEntries(array $data): int
    {
        $savedCount = 0;
        $totalKg = 0;
        $hasInvalidEntries = false;

        foreach ($data['feed_entries'] as $entry) {
            // Check if entry has partial data (one field filled, other empty)
            $hasLot = !empty($entry['inventory_lot_id']);
            $hasKg = !empty($entry['kg_given']) && $entry['kg_given'] > 0;
            
            if ($hasLot && !$hasKg) {
                $hasInvalidEntries = true;
                Notification::make()
                    ->title('Feed entry incomplete')
                    ->body('Please enter the kg amount for the selected feed lot')
                    ->warning()
                    ->send();
                continue;
            }
            
            if (!$hasLot && $hasKg) {
                $hasInvalidEntries = true;
                Notification::make()
                    ->title('Feed entry incomplete')
                    ->body('Please select a feed lot for the entered amount')
                    ->warning()
                    ->send();
                continue;
            }
            
            if (!$hasLot || !$hasKg) {
                continue;
            }

            $lotId = $entry['inventory_lot_id'];
            $kgGiven = floatval($entry['kg_given']);
            $feedItemId = $entry['feed_item_id'] ?? null;

            // If feed_item_id wasn't set, get it from the lot
            if (!$feedItemId) {
                $lot = InventoryLot::find($lotId);
                $feedItemId = $lot?->item_id;
            }

            DB::transaction(function () use ($data, $lotId, $kgGiven, $feedItemId, &$savedCount, &$totalKg) {
                DailyFeedIntake::create([
                    'batch_id' => $data['batch_id'],
                    'date' => $data['date'],
                    'feed_item_id' => $feedItemId,
                    'inventory_lot_id' => $lotId,
                    'kg_given' => $kgGiven,
                ]);

                $lot = InventoryLot::lockForUpdate()->find($lotId);
                
                if ($lot && $lot->qty_on_hand >= $kgGiven) {
                    $lot->qty_on_hand -= $kgGiven;
                    $lot->save();
                    
                    InventoryMovement::create([
                        'lot_id' => $lotId,
                        'ts' => $data['date'],
                        'direction' => 'out',
                        'qty' => $kgGiven,
                        'reference' => 'feed_consumption',
                        'batch_id' => $data['batch_id'],
                    ]);

                    $savedCount++;
                    $totalKg += $kgGiven;
                } else {
                    Notification::make()
                        ->title('Inventory warning')
                        ->body("Could not deduct {$kgGiven} kg from {$lot?->item?->name} - insufficient stock")
                        ->warning()
                        ->send();
                }
            });
        }

        if ($savedCount > 0) {
            $this->feedSaved = true;
            Notification::make()
                ->title('Feed inventory updated')
                ->body("Total: {$totalKg} kg deducted from {$savedCount} feed lot(s)")
                ->info()
                ->send();
        }

        return $savedCount;
    }

    protected function saveWater(array $data): void
    {
        $existing = DailyWaterUsage::where('batch_id', $data['batch_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            $existing->update([
                'liters_used' => $data['liters_used'],
            ]);
        } else {
            DailyWaterUsage::create([
                'batch_id' => $data['batch_id'],
                'date' => $data['date'],
                'liters_used' => $data['liters_used'],
            ]);
        }

        $this->waterSaved = true;
    }

    protected function saveMortality(array $data): void
    {
        MortalityLog::create([
            'batch_id' => $data['batch_id'],
            'date' => $data['date'],
            'count' => $data['mortality_count'],
            'cause' => $data['mortality_cause'] ?? null,
        ]);

        $this->mortalitySaved = true;
    }

    protected function saveTreatments(array $data): int
    {
        $savedCount = 0;
        $date = Carbon::parse($data['date']);
        
        if (!empty($data['additional_treatments'])) {
            foreach ($data['additional_treatments'] as $treatmentData) {
                if (!empty($treatmentData['product'])) {
                    HealthTreatment::create([
                        'batch_id' => $data['batch_id'],
                        'date' => $date,
                        'product' => $treatmentData['product'],
                        'reason' => $treatmentData['reason'] ?? null,
                        'dosage' => $treatmentData['dosage'] ?? null,
                        'duration_days' => !empty($treatmentData['duration_days']) ? intval($treatmentData['duration_days']) : null,
                    ]);
                    
                    $savedCount++;
                }
            }
            
            // Clear the treatments from the form after saving
            if ($savedCount > 0) {
                $this->data['additional_treatments'] = [];
            }
        }
        
        return $savedCount;
    }

    protected function recordFeedDisposal(array $data): void
    {
        $lotId = $data['disposal_lot_id'];
        $qty = floatval($data['disposal_qty']);
        $reason = $data['disposal_reason'] ?? 'Disposed';
        $date = Carbon::parse($data['date']);
        
        DB::transaction(function () use ($lotId, $qty, $reason, $date) {
            $lot = InventoryLot::lockForUpdate()->find($lotId);
            
            if ($lot && $lot->qty_on_hand >= $qty) {
                $lot->qty_on_hand -= $qty;
                $lot->save();
                
                InventoryMovement::create([
                    'lot_id' => $lotId,
                    'ts' => $date,
                    'direction' => 'out',
                    'qty' => $qty,
                    'reference' => $reason,
                ]);
                
                Notification::make()
                    ->title('Feed out recorded')
                    ->body("{$qty} kg removed. Reason: {$reason}")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Insufficient stock')
                    ->body('Cannot remove more than available stock')
                    ->danger()
                    ->send();
            }
        });
        
        $this->data['disposal_lot_id'] = null;
        $this->data['disposal_qty'] = null;
        $this->data['disposal_reason'] = null;
    }

    protected function recordReceivedStock(array $data): void
    {
        $lotId = $data['received_lot_id'];
        $qty = floatval($data['received_qty']);
        $reason = $data['received_reason'] ?? 'Received';
        $date = Carbon::parse($data['date']);
        
        DB::transaction(function () use ($lotId, $qty, $reason, $date) {
            $lot = InventoryLot::lockForUpdate()->find($lotId);
            
            if ($lot) {
                $lot->qty_on_hand += $qty;
                $lot->save();
                
                InventoryMovement::create([
                    'lot_id' => $lotId,
                    'ts' => $date,
                    'direction' => 'in',
                    'qty' => $qty,
                    'reference' => $reason,
                ]);
                
                Notification::make()
                    ->title('Received stock recorded')
                    ->body("{$qty} kg added. Source: {$reason}. New total: {$lot->qty_on_hand} kg")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Error')
                    ->body('Could not find the selected lot')
                    ->danger()
                    ->send();
            }
        });
        
        $this->data['received_lot_id'] = null;
        $this->data['received_qty'] = null;
        $this->data['received_reason'] = null;
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        
        $batch = Batch::with(['farm', 'house'])->find($data['batch_id']);
        
        if (!$batch) {
            Notification::make()
                ->title('Please select a batch')
                ->danger()
                ->send();
            return;
        }

        $date = Carbon::parse($data['date']);
        
        // Calculate age in weeks
        $ageWeeks = (int) $batch->placement_date->diffInWeeks($date);
        
        // Get mortality for the day
        $mortalityToday = MortalityLog::where('batch_id', $batch->id)
            ->whereDate('date', $date)
            ->sum('count');
        
        // Calculate total mortality BEFORE today (not including today)
        $totalMortalityBeforeToday = MortalityLog::where('batch_id', $batch->id)
            ->whereDate('date', '<', $date)
            ->sum('count');
        
        // Effective number = birds at start of day (before today's mortality)
        $effectiveNumber = $batch->placement_qty - $totalMortalityBeforeToday + ($data['received_hens'] ?? 0);
        
        // Remaining hens = effective number - today's mortality
        $remainingHens = $effectiveNumber - $mortalityToday;
        
        // Get feed data from inventory
        $feedInitial = floatval($data['feed_initial_stock'] ?? 0);
        $feedDistributed = floatval($data['feed_distributed'] ?? 0);
        $feedReceived = floatval($data['feed_received_stock'] ?? 0);
        $feedOut = floatval($data['feed_out_stock'] ?? 0);
        $closingStock = floatval($data['feed_closing_stock'] ?? 0);
        
        // Get detailed feed breakdown for the day
        $feedDetails = DailyFeedIntake::where('batch_id', $batch->id)
            ->whereDate('date', $date)
            ->with('feedItem')
            ->get()
            ->groupBy(fn ($intake) => $intake->feedItem?->name ?? 'Unknown Feed')
            ->map(fn ($group) => $group->sum('kg_given'));
        
        // Get closing stock breakdown by feed type
        $closingStockDetails = InventoryLot::whereHas('item', fn ($q) => $q->where('category', 'feed'))
            ->where('qty_on_hand', '>', 0)
            ->with('item')
            ->get()
            ->groupBy(fn ($lot) => $lot->item?->name ?? 'Unknown Feed')
            ->map(fn ($group) => $group->sum('qty_on_hand'));
        
        // Get water usage for the day
        $waterUsed = DailyWaterUsage::where('batch_id', $batch->id)
            ->whereDate('date', $date)
            ->sum('liters_used');
        
        // Get eggs for the day
        $eggsToday = DailyProduction::where('batch_id', $batch->id)
            ->whereDate('date', $date)
            ->first();
        
        // Get latest weight sample
        $latestWeight = WeightSample::where('batch_id', $batch->id)
            ->whereDate('date', '<=', $date)
            ->orderBy('date', 'desc')
            ->first();
        $avgWeight = $latestWeight ? round($latestWeight->avg_weight_g) : 'N/A';
        
        // Get ongoing treatments (already saved via Save All button)
        $treatments = HealthTreatment::where('batch_id', $batch->id)
            ->whereDate('date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereRaw('DATE_ADD(date, INTERVAL COALESCE(duration_days, 0) DAY) >= ?', [$date->format('Y-m-d')])
                    ->orWhereNull('duration_days');
            })
            ->get();
        
        // Build treatments text
        $treatmentsText = '';
        foreach ($treatments as $treatment) {
            // Use dosage field, fallback to legacy dosage_per_liter_ml for old records
            $dosageText = $treatment->dosage 
                ?: ($treatment->dosage_per_liter_ml ? "{$treatment->dosage_per_liter_ml}ml/L" : '')
                ?: ($treatment->reason ?? '');
            $treatmentsText .= "\n-{$treatment->product}" . ($dosageText ? ":{$dosageText}" : '');
        }
        
        if (empty($treatmentsText)) {
            $treatmentsText = "\n-None";
        }

        // Build the WhatsApp message
        $message = "#" . $batch->farm->name . "\n\n";
        $message .= "Daily report [" . $batch->house->name . "]\n\n";
        $message .= "Date: " . $date->format('d-m-Y') . "\n\n";
        
        $message .= "1. Flock demography:\n\n";
        $message .= "-Age of chicken: {$ageWeeks}weeks\n\n";
        $message .= "-Effective Number: {$effectiveNumber}\n\n";
        $message .= "-Received Hens: " . ($data['received_hens'] ?? 0) . "\n\n";
        $message .= "-Mortalities: {$mortalityToday}\n\n";
        $message .= "-Remaining hens:{$remainingHens}\n\n";
        
        // Add eggs production with breakdown if available
        if ($eggsToday) {
            $message .= "-Eggs collected: {$eggsToday->eggs_total}\n";
            $eggBreakdown = [];
            if ($eggsToday->eggs_cracked > 0) {
                $eggBreakdown[] = "Cracked: {$eggsToday->eggs_cracked}";
            }
            if ($eggsToday->eggs_dirty > 0) {
                $eggBreakdown[] = "Dirty: {$eggsToday->eggs_dirty}";
            }
            if ($eggsToday->eggs_soft > 0) {
                $eggBreakdown[] = "Soft: {$eggsToday->eggs_soft}";
            }
            if ($eggsToday->eggs_small > 0) {
                $eggBreakdown[] = "Small: {$eggsToday->eggs_small}";
            }
            if (!empty($eggBreakdown)) {
                $message .= "   (" . implode(', ', $eggBreakdown) . ")\n";
            }
            $message .= "\n";
        }
        
        $message .= "----------------------------------------------------- plus\n\n";
        
        $message .= "👉🏿initial stock:" . number_format($feedInitial, 0) . "kgs\n\n";
        
        // Build feed distributed breakdown
        $message .= "👉🏿Feed distributed: " . number_format($feedDistributed, 0) . "kgs\n";
        if ($feedDetails->isNotEmpty()) {
            foreach ($feedDetails as $feedName => $kgAmount) {
                $message .= "   -{$feedName}: " . number_format($kgAmount, 1) . " kg\n";
            }
        }
        $message .= "\n";
        
        $message .= "👉🏿Feed out stock:" . number_format($feedOut, 0) . "kgs\n\n";
        $message .= "👉🏿received stock:" . number_format($feedReceived, 0) . "kgs\n\n";
        
        // Build closing stock breakdown
        $message .= "👉🏿closing stock:" . number_format($closingStock, 0) . "kgs\n";
        if ($closingStockDetails->isNotEmpty()) {
            foreach ($closingStockDetails as $feedName => $kgAmount) {
                $message .= "   -{$feedName}: " . number_format($kgAmount, 1) . " kg\n";
            }
        }
        $message .= "\n\n";
        
        $message .= "3. Water distributed: " . number_format($waterUsed, 0) . "\n\n";
        
        $message .= "4. Ongoing treatments: " . $treatmentsText . "\n\n";
        
        $message .= "5.climate condition : " . ($data['climate_condition'] ?? 'normal') . "\n\n";
        
        $message .= "6.Health condition: " . ($data['health_condition'] ?? 'healthy') . "\n\n";
        
        $message .= "7.average body weight:" . $avgWeight . "\n\n";
        
        $message .= "8.isolation :" . ($data['isolation'] ?? 0);

        $this->generatedMessage = $message;
        
        // Create WhatsApp URL
        $this->whatsappUrl = 'https://wa.me/?text=' . urlencode($message);
        
        Notification::make()
            ->title('Report generated successfully!')
            ->success()
            ->send();
    }

    public function copyMessage(): void
    {
        if ($this->generatedMessage) {
            Notification::make()
                ->title('Message copied to clipboard!')
                ->success()
                ->send();
        }
    }
}
