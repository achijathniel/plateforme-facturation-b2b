<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessOverdueInvoicesCommand extends Command
{
    /**
     * Nom et signature de la commande en ligne de commande.
     *
     * @var string
     */
    protected $signature = 'app:process-overdue-invoices';

    /**
     * Description de la commande.
     *
     * @var string
     */
    protected $description = 'Détecte les factures envoyées dont l\'échéance est passée et les marque comme en retard (OVERDUE).';

    /**
     * Exécute la commande console.
     */
    public function handle(): int
    {
        $today = now()->toDateString();
        $this->info("Démarrage de la détection des factures impayées au {$today}...");

        $processedCount = 0;

        // Traitement par lots (chunkById) pour garantir une empreinte mémoire constante (< 30 Mo de RAM)
        Invoice::where('status', InvoiceStatus::SENT)
            ->where('due_date', '<', $today)
            ->chunkById(100, function ($invoices) use (&$processedCount) {
                foreach ($invoices as $invoice) {
                    $invoice->update([
                        'status' => InvoiceStatus::OVERDUE,
                    ]);
                    $processedCount++;
                }
            });

        $this->info("Traitement terminé : {$processedCount} facture(s) basculée(s) au statut OVERDUE.");

        Log::info("Commande planifiée app:process-overdue-invoices : {$processedCount} factures échues traitées.");

        return self::SUCCESS;
    }
}
