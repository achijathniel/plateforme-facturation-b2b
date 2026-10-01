<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\DTOs\Invoices\CreateInvoiceDTO;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

final class CreateInvoiceAction
{
    /**
     * Taux standard de TVA zone UEMOA (18%).
     */
    private const TVA_RATE = '0.18';

    /**
     * Exécute la logique métier de création de facture sous transaction atomique.
     * La facture est créée à l'état de brouillon (DRAFT) sans envoi d'email.
     */
    public function execute(CreateInvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto): Invoice {
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

            // Verrouillage de la dernière facture de l'année pour éviter les accès concurrents
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

            // 3. Persistance de la facture au statut DRAFT (brouillon vérifiable)
            $invoice = Invoice::create([
                'organization_id'   => $dto->organizationId,
                'invoice_number'    => $invoiceNumber,
                'client_name'       => $dto->clientName,
                'client_email'      => $dto->clientEmail,
                'client_address'    => $dto->clientAddress,
                'client_tax_number' => $dto->clientTaxNumber,
                'client_phone'      => $dto->clientPhone,
                'status'            => InvoiceStatus::DRAFT,
                'issue_date'        => now()->toDateString(),
                'due_date'          => $dto->dueDate,
                'subtotal'          => $subtotal,
                'tax_amount'        => $taxAmount,
                'total'             => $total,
                'currency'          => 'XOF',
                'notes'             => $dto->notes,
            ]);

            // 4. Persistance des lignes d'articles
            foreach ($itemsData as $data) {
                $invoice->items()->create($data);
            }

            return $invoice->load(['organization', 'items']);
        });
    }
}
