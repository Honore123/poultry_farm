<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowInventoryAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $outOfStockItems,
        public array $criticalStockItems,
        public array $lowStockItems,
    ) {}

    public function envelope(): Envelope
    {
        $subject = 'Inventory Alert - ' . now()->format('M d, Y');
        
        if (!empty($this->outOfStockItems)) {
            $subject = '🚨 ' . $subject . ' (Out of Stock!)';
        } elseif (!empty($this->criticalStockItems)) {
            $subject = '⚠️ ' . $subject . ' (Critical Low Stock)';
        } else {
            $subject = '📦 ' . $subject . ' (Low Stock)';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-inventory-alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

