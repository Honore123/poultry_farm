<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TwoFactorToggleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = ' 2fa:toggle 
                            {action? : The action to perform (enable, disable, status)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable or disable two-factor authentication for the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        if (! $action) {
            $action = $this->choice(
                'What would you like to do?',
                ['status', 'enable', 'disable'],
                0
            );
        }

        return match ($action) {
            'enable' => $this->enable(),
            'disable' => $this->disable(),
            'status' => $this->status(),
            default => $this->invalidAction(),
        };
    }

    protected function enable(): int
    {
        $this->updateEnvFile('TWO_FACTOR_ENABLED', 'true');
        
        $this->components->info('Two-factor authentication has been ENABLED.');
        $this->components->bulletList([
            'Users will now receive a 6-digit code via email when logging in.',
            'The code expires after 10 minutes.',
        ]);

        return self::SUCCESS;
    }

    protected function disable(): int
    {
        $this->updateEnvFile('TWO_FACTOR_ENABLED', 'false');
        
        $this->components->warn('Two-factor authentication has been DISABLED.');
        $this->components->bulletList([
            'Users will now log in with just email and password.',
            'Consider enabling 2FA for better security.',
        ]);

        return self::SUCCESS;
    }

    protected function status(): int
    {
        $enabled = config('auth.two_factor_enabled', true);
        
        $this->components->info('Two-Factor Authentication Status');
        
        $this->table(
            ['Setting', 'Value'],
            [
                ['2FA Enabled', $enabled ? '✅ Yes' : '❌ No'],
                ['Code Expiry', '10 minutes'],
                ['Delivery Method', 'Email'],
            ]
        );

        return self::SUCCESS;
    }

    protected function invalidAction(): int
    {
        $this->components->error('Invalid action. Use: enable, disable, or status');

        return self::FAILURE;
    }

    protected function updateEnvFile(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        if (str_contains($envContent, "{$key}=")) {
            // Update existing key
            $envContent = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $envContent
            );
        } else {
            // Add new key
            $envContent .= "\n{$key}={$value}";
        }

        File::put($envPath, $envContent);

        // Clear config cache
        $this->callSilent('config:clear');
    }
}

