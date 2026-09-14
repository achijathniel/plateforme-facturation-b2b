<?php

namespace Tests\Feature\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessOverdueInvoicesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1 : La commande bascule uniquement les factures échues au statut OVERDUE.
     */
    public function test_command_marks_expired_sent_invoices_as_overdue(): void
    {
        $org = Organization::factory()->create();

        // 1. Facture échue (échéance il y a 5 jours)
        $expiredInvoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::SENT,
            'due_date'        => now()->subDays(5)->toDateString(),
        ]);

        // 2. Facture valide (échéance dans 10 jours)
        $validInvoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::SENT,
            'due_date'        => now()->addDays(10)->toDateString(),
        ]);

        // 3. Facture déjà payée (échéance dépassée mais payée)
        $paidInvoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::PAID,
            'due_date'        => now()->subDays(15)->toDateString(),
        ]);

        // Exécution de la commande Artisan
        $this->artisan('app:process-overdue-invoices')
            ->assertExitCode(0);

        // 1. La facture échue doit être devenue OVERDUE
        $this->assertEquals(InvoiceStatus::OVERDUE, $expiredInvoice->fresh()->status);

        // 2. La facture non échue doit rester SENT
        $this->assertEquals(InvoiceStatus::SENT, $validInvoice->fresh()->status);

        // 3. La facture payée doit rester PAID
        $this->assertEquals(InvoiceStatus::PAID, $paidInvoice->fresh()->status);
    }
}
