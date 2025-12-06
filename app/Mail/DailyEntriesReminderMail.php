<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyEntriesReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $date,
        public array $missingEntries,
        public array $upcomingVaccinations,
        public array $overdueVaccinations,
    ) {}

    public function envelope(): Envelope
    {
        $subject = 'Farm Daily Report - ' . $this->date->format('M d, Y');
        
        if (!empty($this->overdueVaccinations)) {
            $subject = '🚨 ' . $subject . ' (Action Required)';
        } elseif (!empty($this->missingEntries)) {
            $subject = '⚠️ ' . $subject . ' (Missing Entries)';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-entries-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

