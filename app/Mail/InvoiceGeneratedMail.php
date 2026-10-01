<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Crée une nouvelle instance de message.
     */
    public function __construct(
        public readonly Invoice $invoice
    ) {}

    /**
     * Déclare l'enveloppe du mail (objet, expéditeur).
     */
    public function envelope(): Envelope
    {
        $senderName = $this->invoice->organization?->name ?? 'DUGHU DEALTOO';

        return new Envelope(
            subject: "Facture disponible : {$this->invoice->invoice_number} — {$senderName}",
        );
    }

    /**
     * Déclare le contenu (vue Blade).
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoices.generated',
        );
    }

    /**
     * Pièces jointes (si applicable).
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
