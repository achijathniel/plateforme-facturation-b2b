<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\DTOs\Invoices\UpdateInvoiceDTO;
use App\Enums\InvoiceStatus;
use App\Jobs\SendInvoiceNotificationJob;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

final class UpdateInvoiceAction
{
    /**
     * Taux standard de TVA zone UEMOA (18%).
     */
    private const TVA_RATE = '0.18';

    /**
     * Met à jour une facture et ses lignes sous transaction atomique.
     * Si l'action est 'send', passe le statut à SENT et envoie l'email.
     */
    public function execute(Invoice $invoice, UpdateInvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($invoice, $dto): Invoice {
            // 1. Calculs financiers stricts avec bcmath
            $subtotal = '0.00';
            $itemsData = [];

            foreach ($dto->items as $item) {
                $lineTotal = bcmul($item->quantity, $item->unitPrice, 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);

                $itemsData[] = [
                    'description' => $item->description,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->unitPrice,
                    'tax_rate'    => 18.00,
                    'total'       => $lineTotal,
                ];
            }

            $taxAmount = bcmul($subtotal, self::TVA_RATE, 2);
            $total = bcadd($subtotal, $taxAmount, 2);

            $updatePayload = [
                'due_date'   => $dto->dueDate,
                'subtotal'   => $subtotal,
                'tax_amount' => $taxAmount,
                'total'      => $total,
                'notes'      => $dto->notes,
            ];

            $shouldSend = $dto->action === 'send';
            if ($shouldSend) {
                $updatePayload['status'] = InvoiceStatus::SENT;
            }

            // 2. Mise à jour de la facture
            $invoice->update($updatePayload);

            // 3. Remplacement synchronisé des lignes d'articles
            $invoice->items()->delete();
            foreach ($itemsData as $data) {
                $invoice->items()->create($data);
            }

            // 4. Déclenchement de l'envoi email si demandé
            if ($shouldSend) {
                SendInvoiceNotificationJob::dispatch($invoice)->afterCommit();
            }

            return $invoice->fresh(['organization', 'items']);
        });
    }
}
