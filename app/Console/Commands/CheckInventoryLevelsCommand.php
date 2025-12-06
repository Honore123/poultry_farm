<?php

namespace App\Console\Commands;

use App\Mail\LowInventoryAlertMail;
use App\Models\InventoryLot;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckInventoryLevelsCommand extends Command
{
    protected $signature = 'farm:check-inventory 
                            {--threshold=5 : Default threshold for low stock alert}
                            {--email= : Email to send notification to (defaults to admin users)}';

    protected $description = 'Check inventory levels and alert when stock is low';

    // Define thresholds per category (in base UOM)
    protected array $thresholds = [
        'feed' => [
            'default' => 100,  // 100 kg
            'critical' => 50,  // 50 kg
        ],
        'drug' => [
            'default' => 20,   // 20 units
            'critical' => 10,  // 10 units
        ],
        'packaging' => [
            'default' => 100,  // 100 pieces
            'critical' => 50,  // 50 pieces
        ],
        'equipment' => [
            'default' => 5,    // 5 pieces
            'critical' => 2,   // 2 pieces
        ],
        'other' => [
            'default' => 10,
            'critical' => 5,
        ],
    ];

    public function handle(): int
    {
        $this->info('Checking inventory levels...');

        $lowStockItems = [];
        $criticalStockItems = [];
        $outOfStockItems = [];

        // Get all inventory lots grouped by item
        $lots = InventoryLot::with(['item', 'supplier'])
            ->where('qty_on_hand', '>', 0)
            ->orWhere(function ($query) {
                // Include items that are out of stock
                $query->where('qty_on_hand', '<=', 0);
            })
            ->get()
            ->groupBy('item_id');

        // Also check for items with no lots at all
        $itemsWithStock = InventoryLot::distinct()->pluck('item_id');
        $itemsWithNoStock = \App\Models\InventoryItem::whereNotIn('id', $itemsWithStock)->get();

        foreach ($itemsWithNoStock as $item) {
            $outOfStockItems[] = [
                'item' => $item,
                'total_qty' => 0,
                'uom' => $item->uom,
                'category' => $item->category,
            ];
        }

        foreach ($lots as $itemId => $itemLots) {
            $firstLot = $itemLots->first();
            $item = $firstLot->item;
            
            if (!$item) continue;

            $totalQty = $itemLots->sum('qty_on_hand');
            $category = $item->category ?? 'other';
            $thresholds = $this->thresholds[$category] ?? $this->thresholds['other'];

            $stockInfo = [
                'item' => $item,
                'total_qty' => $totalQty,
                'uom' => $item->uom,
                'category' => $category,
                'lots' => $itemLots,
            ];

            if ($totalQty <= 0) {
                $outOfStockItems[] = $stockInfo;
            } elseif ($totalQty <= $thresholds['critical']) {
                $criticalStockItems[] = $stockInfo;
            } elseif ($totalQty <= $thresholds['default']) {
                $lowStockItems[] = $stockInfo;
            }
        }

        // Display results
        $this->displayResults($outOfStockItems, $criticalStockItems, $lowStockItems);

        // Send email if there are alerts
        if (!empty($outOfStockItems) || !empty($criticalStockItems) || !empty($lowStockItems)) {
            $this->sendNotification($outOfStockItems, $criticalStockItems, $lowStockItems);
        } else {
            $this->info('✓ All inventory levels are adequate!');
        }

        return self::SUCCESS;
    }

    protected function displayResults(array $outOfStock, array $critical, array $low): void
    {
        if (!empty($outOfStock)) {
            $this->error("\n🚨 OUT OF STOCK:");
            foreach ($outOfStock as $item) {
                $this->line("  - {$item['item']->name} ({$item['item']->sku})");
            }
        }

        if (!empty($critical)) {
            $this->warn("\n⚠️ CRITICAL LOW STOCK:");
            foreach ($critical as $item) {
                $this->line("  - {$item['item']->name}: {$item['total_qty']} {$item['uom']} remaining");
            }
        }

        if (!empty($low)) {
            $this->info("\n📦 LOW STOCK:");
            foreach ($low as $item) {
                $this->line("  - {$item['item']->name}: {$item['total_qty']} {$item['uom']} remaining");
            }
        }
    }

    protected function sendNotification(array $outOfStock, array $critical, array $low): void
    {
        $email = $this->option('email');
        
        if ($email) {
            $recipients = [$email];
        } else {
            // Send to admin and manager users
            $recipients = User::role(['admin', 'manager'])->pluck('email')->toArray();
        }

        if (empty($recipients)) {
            $this->warn('No recipients found for email notification.');
            return;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new LowInventoryAlertMail(
                $outOfStock,
                $critical,
                $low
            ));
        }

        $this->info("\n📧 Email notification sent to: " . implode(', ', $recipients));
    }
}

