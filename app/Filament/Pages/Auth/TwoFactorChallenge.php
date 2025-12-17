<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorChallenge extends SimplePage
{
    use WithRateLimiting;

    protected static string $view = 'filament.pages.auth.two-factor-challenge';

    public ?array $data = [];

    public function mount(): void
    {
        // Check if user has a pending 2FA session
        if (! session('two_factor_user_id')) {
            $this->redirect(Filament::getLoginUrl());

            return;
        }

        $this->form->fill();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Two-Factor Authentication';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Verify Your Identity';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Enter the 6-digit code sent to your email';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Verification Code')
                    ->placeholder('000000')
                    ->required()
                    ->numeric()
                    ->length(6)
                    ->autofocus()
                    ->autocomplete('one-time-code')
                    ->extraInputAttributes([
                        'style' => 'text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem;',
                    ]),
            ])
            ->statePath('data');
    }

    public function verify(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(__('filament-panels::pages/auth/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();
        $userId = session('two_factor_user_id');

        $user = User::find($userId);

        if (! $user) {
            session()->forget('two_factor_user_id');
            $this->redirect(Filament::getLoginUrl());

            return;
        }

        if (! $user->verifyTwoFactorCode($data['code'])) {
            throw ValidationException::withMessages([
                'data.code' => 'The verification code is invalid or has expired.',
            ]);
        }

        // Clear the 2FA code
        $user->clearTwoFactorCode();

        // Clear the session
        session()->forget('two_factor_user_id');

        // Log the user in
        Auth::login($user);

        session()->regenerate();

        // Redirect to intended URL or dashboard
        $this->redirect(
            session('url.intended', Filament::getUrl())
        );
    }

    public function resend(): void
    {
        try {
            $this->rateLimit(1, 60); // 1 resend per minute
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Please wait')
                ->body("You can request a new code in {$exception->secondsUntilAvailable} seconds.")
                ->warning()
                ->send();

            return;
        }

        $userId = session('two_factor_user_id');
        $user = User::find($userId);

        if (! $user) {
            session()->forget('two_factor_user_id');
            $this->redirect(Filament::getLoginUrl());

            return;
        }

        $user->generateTwoFactorCode();

        Notification::make()
            ->title('Code Sent!')
            ->body('A new verification code has been sent to your email.')
            ->success()
            ->send();
    }

    public function cancel(): void
    {
        session()->forget('two_factor_user_id');
        $this->redirect(Filament::getLoginUrl());
    }
}

