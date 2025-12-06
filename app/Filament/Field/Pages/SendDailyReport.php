<?php

namespace App\Filament\Field\Pages;

use App\Models\Batch;
use App\Models\DailyFeedIntake;
use App\Models\DailyWaterUsage;
use App\Models\HealthTreatment;
use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\MortalityLog;
use App\Models\WeightSample;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SendDailyReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationLabel = 'Send Daily Report';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.field.pages.send-daily-report';

    public ?array $data = [];

    public ?string $generatedMessage = null;

    public ?string $whatsappUrl = null;

    public function mount(): void
    {
        $batchId = request()->query('batch');
        
        $this->form->fill([
            'batch_id' => $batchId,
            'date' => now()->format('Y-m-d'),
            'climate_condition' => 'normal',
            'health_condition' => 'healthy',
            'received_hens' => 0,
            'isolation' => 0,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Select Batch & Date')
                    ->schema([
                        Forms\Components\Select::make('batch_id')
                            ->label('Batch')
                            ->options(
                                Batch::whereIn('status', ['brooding', 'growing', 'laying'])
                                    ->with(['farm', 'house'])
                                    ->get()
                                    ->mapWithKeys(fn ($batch) => [
                                        $batch->id => "{$batch->code} - {$batch->farm->name} / {$batch->house->name}"
                                    ])
                            )
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->loadInventoryData()),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn () => $this->loadInventoryData()),
                    ])->columns(2),

                Forms\Components\Section::make('Flock Demography')
                    ->schema([
                        Forms\Components\TextInput::make('received_hens')
                            ->label('Received Hens Today')
                            ->numeric()
                            ->default(0),
                    ]),

                Forms\Components\Section::make('Feed Stock (Auto-calculated from Inventory)')
                    ->description('These values are calculated from your inventory records')
                    ->schema([
                        Forms\Components\Placeholder::make('feed_initial_stock_display')
                            ->label('Initial Stock (Start of Day)')
                            ->content(fn (Get $get) => number_format($get('feed_initial_stock') ?? 0, 1) . ' kg'),
                        Forms\Components\Placeholder::make('feed_distributed_display')
                            ->label('Feed Distributed (Recorded Today)')
                            ->content(fn (Get $get) => number_format($get('feed_distributed') ?? 0, 1) . ' kg'),
                        Forms\Components\Placeholder::make('feed_received_display')
                            ->label('Received Stock (Today)')
                            ->content(fn (Get $get) => number_format($get('feed_received_stock') ?? 0, 1) . ' kg'),
                        Forms\Components\Placeholder::make('feed_out_display')
                            ->label('Feed Out/Disposed (Today)')
                            ->content(fn (Get $get) => number_format($get('feed_out_stock') ?? 0, 1) . ' kg'),
                        Forms\Components\Placeholder::make('feed_closing_display')
                            ->label('Closing Stock')
                            ->content(fn (Get $get) => number_format($get('feed_closing_stock') ?? 0, 1) . ' kg'),
                        
                        // Hidden fields for calculations
                        Forms\Components\Hidden::make('feed_initial_stock'),
                        Forms\Components\Hidden::make('feed_distributed'),
                        Forms\Components\Hidden::make('feed_received_stock'),
                        Forms\Components\Hidden::make('feed_out_stock'),
                        Forms\Components\Hidden::make('feed_closing_stock'),
                    ])->columns(5),

                Forms\Components\Section::make('Record Feed Disposal (Optional)')
                    ->description('Record any feed that was disposed/wasted today (will be deducted from inventory)')
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
                            ->label('Quantity to Dispose (kg)')
                            ->numeric()
                            ->minValue(0.1)
                            ->suffix('kg'),
                        Forms\Components\TextInput::make('disposal_reason')
                            ->label('Reason')
                            ->placeholder('e.g., Spoiled, Contaminated, etc.'),
                    ])->columns(3)
                    ->collapsed(),

                Forms\Components\Section::make('Health & Environment')
                    ->schema([
                        Forms\Components\Select::make('climate_condition')
                            ->label('Climate Condition')
                            ->options([
                                'hot' => 'Hot',
                                'warm' => 'Warm',
                                'normal' => 'Normal',
                                'cold' => 'Cold',
                                'rainy' => 'Rainy',
                            ])
                            ->required(),
                        Forms\Components\Select::make('health_condition')
                            ->label('Health Condition')
                            ->options([
                                'healthy' => 'Healthy',
                                'sick' => 'Sick',
                                'recovering' => 'Recovering',
                                'under_treatment' => 'Under Treatment',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('isolation')
                            ->label('Isolation Count')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Additional Treatments')
                    ->description('Add any vitamins or drugs administered today - these will be saved to health records')
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
        // Initial = Current + Consumed + Disposed - Received
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

        // Handle feed disposal if entered
        if (!empty($data['disposal_lot_id']) && !empty($data['disposal_qty']) && $data['disposal_qty'] > 0) {
            $this->recordFeedDisposal($data);
        }

        $date = Carbon::parse($data['date']);
        
        // Re-load inventory data after potential disposal
        $this->loadInventoryData();
        $data = $this->form->getState();
        
        // Calculate age in weeks
        $ageWeeks = $batch->placement_date->diffInWeeks($date);
        
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
        
        // Get water usage for the day
        $waterUsed = DailyWaterUsage::where('batch_id', $batch->id)
            ->whereDate('date', $date)
            ->sum('liters_used');
        
        // Get latest weight sample
        $latestWeight = WeightSample::where('batch_id', $batch->id)
            ->whereDate('date', '<=', $date)
            ->orderBy('date', 'desc')
            ->first();
        $avgWeight = $latestWeight ? round($latestWeight->avg_weight_g) : 'N/A';
        
        // Get ongoing treatments
        $treatments = HealthTreatment::where('batch_id', $batch->id)
            ->whereDate('date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereRaw('DATE_ADD(date, INTERVAL COALESCE(duration_days, 0) DAY) >= ?', [$date->format('Y-m-d')])
                    ->orWhereNull('duration_days');
            })
            ->get();
        
        // Save additional treatments from form to database
        $savedTreatments = [];
        if (!empty($data['additional_treatments'])) {
            foreach ($data['additional_treatments'] as $treatmentData) {
                if (!empty($treatmentData['product'])) {
                    // Parse dosage - try to extract ml/L value if present for backward compatibility
                    $dosageMl = null;
                    if (!empty($treatmentData['dosage'])) {
                        // Try to extract numeric value for ml/L
                        if (preg_match('/(\d+(?:\.\d+)?)\s*ml/i', $treatmentData['dosage'], $matches)) {
                            $dosageMl = floatval($matches[1]);
                        }
                    }
                    
                    $savedTreatment = HealthTreatment::create([
                        'batch_id' => $batch->id,
                        'date' => $date,
                        'product' => $treatmentData['product'],
                        'reason' => $treatmentData['reason'] ?? null,
                        'dosage' => $treatmentData['dosage'] ?? null,
                        'dosage_per_liter_ml' => $dosageMl,
                        'duration_days' => !empty($treatmentData['duration_days']) ? intval($treatmentData['duration_days']) : null,
                    ]);
                    
                    $savedTreatments[] = $savedTreatment;
                }
            }
            
            if (count($savedTreatments) > 0) {
                Notification::make()
                    ->title('Treatments saved!')
                    ->body(count($savedTreatments) . ' treatment(s) recorded to health records')
                    ->success()
                    ->send();
                
                // Clear the form to prevent duplicate entries
                $this->data['additional_treatments'] = [];
            }
        }
        
        // Refresh treatments list to include newly added ones
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
            $dosageText = $treatment->dosage 
                ?? ($treatment->dosage_per_liter_ml ? "{$treatment->dosage_per_liter_ml}ml/L" : '')
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
        
        $message .= "----------------------------------------------------- plus\n\n";
        
        $message .= "👉🏿initial stock:" . number_format($feedInitial, 0) . "kgs\n\n";
        $message .= "👉🏿Feed distributed: " . number_format($feedDistributed, 0) . "kgs\n\n";
        $message .= "👉🏿Feed out stock:" . number_format($feedOut, 0) . "kgs\n\n";
        $message .= "👉🏿received stock:" . number_format($feedReceived, 0) . "kgs\n\n";
        $message .= "👉🏿closing stock:" . number_format($closingStock, 0) . "\n\n\n";
        
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

    protected function recordFeedDisposal(array $data): void
    {
        $lotId = $data['disposal_lot_id'];
        $qty = floatval($data['disposal_qty']);
        $reason = $data['disposal_reason'] ?? 'Disposed';
        $date = Carbon::parse($data['date']);
        
        DB::transaction(function () use ($lotId, $qty, $reason, $date) {
            $lot = InventoryLot::lockForUpdate()->find($lotId);
            
            if ($lot && $lot->qty_on_hand >= $qty) {
                // Deduct from inventory
                $lot->qty_on_hand -= $qty;
                $lot->save();
                
                // Create inventory movement record
                InventoryMovement::create([
                    'lot_id' => $lotId,
                    'ts' => $date,
                    'direction' => 'out',
                    'qty' => $qty,
                    'reference' => 'disposed',
                ]);
                
                Notification::make()
                    ->title('Feed disposal recorded')
                    ->body("{$qty} kg disposed. Reason: {$reason}")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Insufficient stock')
                    ->body('Cannot dispose more than available stock')
                    ->danger()
                    ->send();
            }
        });
        
        // Clear the disposal fields
        $this->data['disposal_lot_id'] = null;
        $this->data['disposal_qty'] = null;
        $this->data['disposal_reason'] = null;
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
