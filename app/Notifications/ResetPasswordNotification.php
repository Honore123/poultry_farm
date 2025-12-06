<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $panelId = 'admin'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('🔐 Reset Your Password - ' . config('app.name'))
            ->view('emails.password-reset', [
                'user' => $notifiable,
                'resetUrl' => $resetUrl,
            ]);
    }

    protected function resetUrl(object $notifiable): string
    {
        return url("/set-password/{$this->token}?email=" . urlencode($notifiable->email));
    }
}
