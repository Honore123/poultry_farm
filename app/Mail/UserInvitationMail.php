<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
        public string $invitedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invitation',
            with: [
                'user' => $this->user,
                'setPasswordUrl' => url("/set-password/{$this->token}?email=" . urlencode($this->user->email)),
                'invitedBy' => $this->invitedBy,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

