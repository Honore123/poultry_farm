<?php

namespace App\Console\Commands;

use App\Mail\DailyEntriesReminderMail;
use App\Models\Batch;
use App\Models\DailyFeedIntake;
use App\Models\DailyProduction;
use App\Models\DailyWaterUsage;
use App\Models\MortalityLog;
use App\Models\User;
use App\Models\VaccinationEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckDailyEntriesCommand extends Command
{
    protected $signature = 'farm:check-daily-entries 
                            {--date= : The date to check (defaults to today)}
                            {--email= : Email to send notification to (defaults to all admin users)}';

    protected $description = 'Check for missing daily entries and upcoming health events';

    public function handle(): int
    {
        $date = $this->option('date') ? now()->parse($this->option('date')) : today();
        
        $this->info("Checking daily entries for: {$date->format('Y-m-d')}");

        // Get active batches
        $activeBatches = Batch::whereIn('status', ['brooding', 'growing', 'laying'])
            ->with(['farm', 'house'])
            ->get();

        if ($activeBatches->isEmpty()) {
            $this->info('No active batches found.');
            return self::SUCCESS;
        }

        $missingEntries = [];
        $upcomingVaccinations = [];
        $overdueVaccinations = [];

        foreach ($activeBatches as $batch) {
            $missing = $this->checkMissingEntries($batch, $date);
            if (!empty($missing)) {
                $missingEntries[$batch->id] = [
                    'batch' => $batch,
                    'missing' => $missing,
                ];
            }

            // Check vaccination schedules
            $vaccineInfo = $this->checkVaccinationSchedule($batch);
            if (!empty($vaccineInfo['upcoming'])) {
                $upcomingVaccinations[$batch->id] = [
                    'batch' => $batch,
                    'vaccinations' => $vaccineInfo['upcoming'],
                ];
            }
            if (!empty($vaccineInfo['overdue'])) {
                $overdueVaccinations[$batch->id] = [
                    'batch' => $batch,
                    'vaccinations' => $vaccineInfo['overdue'],
                ];
            }
        }

        // Display results
        $this->displayResults($missingEntries, $upcomingVaccinations, $overdueVaccinations);

        // Send email if there are issues
        if (!empty($missingEntries) || !empty($upcomingVaccinations) || !empty($overdueVaccinations)) {
            $this->sendNotification($date, $missingEntries, $upcomingVaccinations, $overdueVaccinations);
        } else {
            $this->info('✓ All entries complete and no pending vaccinations!');
        }

        return self::SUCCESS;
    }

    protected function checkMissingEntries(Batch $batch, $date): array
    {
        $missing = [];

        // Check egg production (required for laying batches)
        $hasProduction = DailyProduction::where('batch_id', $batch->id)
            ->whereDate('date', $date)
            ->exists();
        
        if (!$hasProduction && $batch->status === 'laying') {
            $missing[] = 'Egg Production';
        }

        // Check feed intake (required for all active batches)
        $hasFeed = DailyFeedIntake::where('batch_id', $batch->id)
            ->whereDate('date', $date)
            ->exists();
        
        if (!$hasFeed) {
            $missing[] = 'Feed Intake';
        }

        // Check water usage (required for all active batches)
        $hasWater = DailyWaterUsage::where('batch_id', $batch->id)
            ->whereDate('date', $date)
            ->exists();
        
        if (!$hasWater) {
            $missing[] = 'Water Usage';
        }

        // Mortality is optional - only flag if batch has history of daily logging
        // (Some farms only log when there are deaths)

        return $missing;
    }

    protected function checkVaccinationSchedule(Batch $batch): array
    {
        $result = ['upcoming' => [], 'overdue' => []];
        
        // Calculate batch age in weeks
        $ageInWeeks = (int) $batch->placement_date->diffInWeeks(now());
        $ageInDays = $batch->placement_date->diffInDays(now());

        // Standard vaccination schedule for layers (customize as needed)
        $schedule = [
            ['name' => "Marek's Disease", 'day' => 1, 'type' => 'vaccination'],
            ['name' => 'ND + IB (First)', 'day' => 7, 'type' => 'vaccination'],
            ['name' => 'Gumboro (First)', 'day' => 14, 'type' => 'vaccination'],
            ['name' => 'Gumboro (Booster)', 'day' => 21, 'type' => 'vaccination'],
            ['name' => 'ND + IB (Booster)', 'day' => 28, 'type' => 'vaccination'],
            ['name' => 'Deworming', 'day' => 42, 'type' => 'deworming', 'recurring' => 42],
            ['name' => 'Fowl Pox', 'day' => 56, 'type' => 'vaccination'],
            ['name' => 'ND Lasota', 'day' => 70, 'type' => 'vaccination'],
        ];

        foreach ($schedule as $item) {
            $dueDay = $item['day'];
            
            // Handle recurring items (like deworming every 6 weeks)
            if (isset($item['recurring']) && $ageInDays > $dueDay) {
                $recurringInterval = $item['recurring'];
                $dueDay = $dueDay + (floor(($ageInDays - $dueDay) / $recurringInterval) * $recurringInterval);
                if ($ageInDays > $dueDay) {
                    $dueDay += $recurringInterval;
                }
            }

            // Check if this vaccination was already done
            $alreadyDone = VaccinationEvent::where('batch_id', $batch->id)
                ->where('vaccine', 'like', '%' . explode(' ', $item['name'])[0] . '%')
                ->whereBetween('date', [
                    $batch->placement_date->copy()->addDays($dueDay - 7),
                    $batch->placement_date->copy()->addDays($dueDay + 7),
                ])
                ->exists();

            if ($alreadyDone) {
                continue;
            }

            $dueDate = $batch->placement_date->copy()->addDays($dueDay);
            $daysUntilDue = now()->startOfDay()->diffInDays($dueDate, false);

            // Upcoming (within next 7 days)
            if ($daysUntilDue >= 0 && $daysUntilDue <= 7) {
                $result['upcoming'][] = [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'due_date' => $dueDate,
                    'days_until' => $daysUntilDue,
                ];
            }
            // Overdue (past due date but not too old)
            elseif ($daysUntilDue < 0 && $daysUntilDue >= -14) {
                $result['overdue'][] = [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'due_date' => $dueDate,
                    'days_overdue' => abs($daysUntilDue),
                ];
            }
        }

        return $result;
    }

    protected function displayResults(array $missingEntries, array $upcomingVaccinations, array $overdueVaccinations): void
    {
        if (!empty($missingEntries)) {
            $this->warn("\n⚠ Missing Daily Entries:");
            foreach ($missingEntries as $data) {
                $batch = $data['batch'];
                $this->line("  - {$batch->code} ({$batch->farm->name}): " . implode(', ', $data['missing']));
            }
        }

        if (!empty($overdueVaccinations)) {
            $this->error("\n🚨 Overdue Vaccinations:");
            foreach ($overdueVaccinations as $data) {
                $batch = $data['batch'];
                foreach ($data['vaccinations'] as $vax) {
                    $this->line("  - {$batch->code}: {$vax['name']} ({$vax['days_overdue']} days overdue)");
                }
            }
        }

        if (!empty($upcomingVaccinations)) {
            $this->info("\n📅 Upcoming Vaccinations (next 7 days):");
            foreach ($upcomingVaccinations as $data) {
                $batch = $data['batch'];
                foreach ($data['vaccinations'] as $vax) {
                    $dueText = $vax['days_until'] == 0 ? 'TODAY' : "in {$vax['days_until']} days";
                    $this->line("  - {$batch->code}: {$vax['name']} ({$dueText})");
                }
            }
        }
    }

    protected function sendNotification($date, array $missingEntries, array $upcomingVaccinations, array $overdueVaccinations): void
    {
        $email = $this->option('email');
        
        if ($email) {
            $recipients = [$email];
        } else {
            // Send to all users (in production, you might want to filter by role)
            $recipients = User::pluck('email')->toArray();
        }

        if (empty($recipients)) {
            $this->warn('No recipients found for email notification.');
            return;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new DailyEntriesReminderMail(
                $date,
                $missingEntries,
                $upcomingVaccinations,
                $overdueVaccinations
            ));
        }

        $this->info("\n📧 Email notification sent to: " . implode(', ', $recipients));
    }
}

