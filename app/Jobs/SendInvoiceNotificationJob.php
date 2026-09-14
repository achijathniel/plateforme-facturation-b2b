<?php

namespace App\Jobs;

use App\Mail\InvoiceGeneratedMail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendInvoiceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre maximal de tentatives avant marquage en échec.
     */
    public int $tries = 3;

    /**
     * Délai d'attente (en secondes) entre chaque tentative.
     */
    public int $backoff = 10;

    /**
     * Temps d'exécution maximal (en secondes) pour ce job.
     */
    public int $timeout = 60;

    /**
     * Crée une nouvelle instance du Job.
     */
    public function __construct(
        public readonly Invoice $invoice
    ) {}

    /**
     * Exécute le Job en arrière-plan.
     */
    public function handle(): void
    {
        // S'assurer que les relations nécessaires sont chargées
        $this->invoice->loadMissing(['organization', 'items']);

        $recipientEmail = $this->invoice->organization?->email;

        if (! $recipientEmail) {
            Log::warning("Envoi facture annulé : aucun email trouvé pour l'organisation ID {$this->invoice->organization_id}.");
            return;
        }

        // Expédition via le service de messagerie SMTP (Mailpit en local)
        Mail::to($recipientEmail)->send(new InvoiceGeneratedMail($this->invoice));

        Log::info("Facture {$this->invoice->invoice_number} envoyée avec succès à {$recipientEmail}.");
    }

    /**
     * Gestionnaire d'échec en cas d'épuisement des tentatives (Alerting / Monitoring).
     */
    public function failed(?Throwable $exception): void
    {
        Log::critical("Échec définitif de l'envoi de la facture {$this->invoice->invoice_number} après {$this->tries} tentatives.", [
            'invoice_id' => $this->invoice->id,
            'error'      => $exception?->getMessage(),
        ]);
    }
}
