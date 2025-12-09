<?php

namespace App\Mail;

use App\Models\SalaryPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalaryPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SalaryPayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Salary Payment Confirmation - {$this->payment->payment_period}",
        );
    }

    public function content(): Content
    {
        $paymentMethodLabels = [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'mobile_money' => 'Mobile Money',
        ];

        return new Content(
            view: 'emails.salary-payment',
            with: [
                'payment' => $this->payment,
                'employee' => $this->payment->employeeSalary,
                'paymentMethod' => $paymentMethodLabels[$this->payment->payment_method] ?? ucwords(str_replace('_', ' ', $this->payment->payment_method ?? 'N/A')),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

