<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerformanceInvoiceSeeder extends Seeder
{
    /**
     * Taux standard de TVA UEMOA (18%).
     */
    private const TVA_RATE = 0.18;

    /**
     * Insère 50 000 factures en Bulk Insert pour benchmarker PostgreSQL.
     */
    public function run(): void
    {
        // Désactiver le log des requêtes pour ne pas saturer la mémoire RAM de PHP
        DB::disableQueryLog();

        // Récupérer les IDs des organisations existantes ou en créer 5 si besoin
        $organizationIds = Organization::pluck('id')->toArray();
        if (empty($organizationIds)) {
            $organizationIds = Organization::factory()->count(5)->create()->pluck('id')->toArray();
        }

        $totalRecords = 50000;
        $batchSize = 2500;
        $statuses = [
            InvoiceStatus::DRAFT->value,
            InvoiceStatus::SENT->value,
            InvoiceStatus::PAID->value,
            InvoiceStatus::OVERDUE->value,
            InvoiceStatus::CANCELLED->value,
        ];

        $this->command?->info("Démarrage de l'insertion en masse de {$totalRecords} factures...");

        $batch = [];
        $now = Carbon::now();

        for ($i = 1; $i <= $totalRecords; $i++) {
            $orgId = $organizationIds[array_rand($organizationIds)];
            $status = $statuses[array_rand($statuses)];
            
            // Dates réparties sur les 2 dernières années
            $daysAgo = rand(1, 730);
            $issueDate = $now->copy()->subDays($daysAgo)->toDateString();
            $dueDate = $now->copy()->subDays($daysAgo)->addDays(30)->toDateString();

            // Montants aléatoires en XOF
            $subtotal = rand(50000, 5000000);
            $taxAmount = round($subtotal * self::TVA_RATE, 2);
            $total = $subtotal + $taxAmount;

            $batch[] = [
                'organization_id' => $orgId,
                'invoice_number'  => sprintf('INV-PERF-%06d', $i),
                'status'          => $status,
                'issue_date'      => $issueDate,
                'due_date'        => $dueDate,
                'subtotal'        => number_format($subtotal, 2, '.', ''),
                'tax_amount'      => number_format($taxAmount, 2, '.', ''),
                'total'           => number_format($total, 2, '.', ''),
                'currency'        => 'XOF',
                'notes'           => 'Facture volumétrie pour analyse de performance et indexation SQL.',
                'created_at'      => $issueDate,
                'updated_at'      => $issueDate,
            ];

            // Insertion par paquet dès que la taille du lot est atteinte
            if (count($batch) === $batchSize) {
                DB::table('invoices')->insert($batch);
                $batch = [];
                $this->command?->info("Progression : {$i} / {$totalRecords} factures insérées.");
            }
        }

        // Insérer le reliquat s'il en reste
        if (!empty($batch)) {
            DB::table('invoices')->insert($batch);
        }

        $this->command?->info("Terminé ! {$totalRecords} factures insérées avec succès dans PostgreSQL.");
    }
}
