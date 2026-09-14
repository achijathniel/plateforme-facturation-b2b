<?php

namespace App\Actions\Invoices;

use App\DTOs\Invoices\CreateInvoiceDTO;
use App\Enums\InvoiceStatus;
use App\Jobs\SendInvoiceNotificationJob;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class CreateInvoiceAction
{
    /**
     * Taux standard de TVA zone UEMOA (18%).
     */
    private const TVA_RATE = '0.18';

    /**
     * Exécute la logique métier de création de facture sous transaction atomique.
     */
    public function execute(CreateInvoiceDTO $dto): Invoice
    {
        $invoice = DB::transaction(function () use ($dto) {
            // 1. Calculs financiers stricts avec bcmath
            $subtotal = '0.00';
            $itemsData = [];

            foreach ($dto->items as $item) {
                // Montant total de la ligne = quantité * prix unitaire
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

            // Calcul de la taxe (18% TVA) et du total TTC
            $taxAmount = bcmul($subtotal, self::TVA_RATE, 2);
            $total = bcadd($subtotal, $taxAmount, 2);

            // 2. Génération du numéro légal séquentiel de la facture
            $year = now()->format('Y');

            // Verrouillage de la dernière facture de l'année pour éviter les accès concurrents (compatible PostgreSQL)
            $lastInvoice = Invoice::withTrashed()
                ->whereYear('created_at', $year)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $nextSequence = 1;
            if ($lastInvoice && preg_match('/INV-\d{4}-(\d+)/', (string) $lastInvoice->invoice_number, $matches)) {
                $nextSequence = ((int) $matches[1]) + 1;
            }

            $invoiceNumber = sprintf('INV-%s-%05d', $year, $nextSequence);

            // 3. Persistance de la facture
            $invoice = Invoice::create([
                'organization_id' => $dto->organizationId,
                'invoice_number'  => $invoiceNumber,
                'status'          => InvoiceStatus::SENT,
                'issue_date'      => now()->toDateString(),
                'due_date'        => $dto->dueDate,
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
                'currency'        => 'XOF',
                'notes'           => $dto->notes,
            ]);

            // 4. Persistance des lignes d'articles
            foreach ($itemsData as $data) {
                $invoice->items()->create($data);
            }

            return $invoice->load(['organization', 'items']);
        });

        // 5. Expédition asynchrone du job de notification via Redis
        SendInvoiceNotificationJob::dispatch($invoice);

        return $invoice;
    }
}
