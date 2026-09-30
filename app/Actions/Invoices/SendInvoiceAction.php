<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Jobs\SendInvoiceNotificationJob;
use App\Models\Invoice;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SendInvoiceAction
{
    /**
     * Émet officiellement la facture au client et déclenche l'envoi de la notification par email.
     *
     * @throws DomainException Si la facture n'est pas à l'état de brouillon.
     */
    public function execute(Invoice $invoice): Invoice
    {
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw new DomainException(sprintf(
                'Impossible d\'émettre la facture %s : son statut actuel est [%s]. Seuls les brouillons peuvent être émis.',
                $invoice->invoice_number,
                $invoice->status?->value ?? (string) $invoice->status
            ));
        }

        DB::transaction(function () use ($invoice): void {
            $invoice->update([
                'status' => InvoiceStatus::SENT,
            ]);
        });

        // Expédition asynchrone du job de notification via Redis
        SendInvoiceNotificationJob::dispatch($invoice);

        return $invoice->fresh(['organization', 'items']);
    }
}
