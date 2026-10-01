<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Jobs\SendInvoiceNotificationJob;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PortalInvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1 : Un comptable connecté peut consulter les détails d'une facture de son organisation.
     */
    public function test_accountant_can_view_invoice_details(): void
    {
        $org = Organization::factory()->create(['name' => 'Tech Solutions Ltd']);
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'invoice_number'  => 'INV-2026-00042',
            'status'          => InvoiceStatus::DRAFT,
            'subtotal'        => 100000,
            'tax_amount'      => 18000,
            'total'           => 118000,
            'currency'        => 'XOF',
        ]);

        InvoiceItem::factory()->create([
            'invoice_id'  => $invoice->id,
            'description' => 'Abonnement Cloud Annuel',
            'quantity'    => 1,
            'unit_price'  => 100000,
            'total'       => 100000,
        ]);

        $response = $this->actingAs($accountant)->get(route('portal.invoices.show', $invoice->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Invoices/Show')
            ->where('invoice.id', $invoice->id)
            ->where('invoice.invoice_number', 'INV-2026-00042')
            ->where('invoice.status', 'draft')
            ->where('invoice.can_edit', true)
            ->where('invoice.can_send', true)
            ->has('invoice.items', 1)
            ->where('organization.name', 'Tech Solutions Ltd')
        );
    }

    /**
     * Test 2 : Un comptable ne peut PAS consulter une facture appartenant à une autre organisation (HTTP 403).
     */
    public function test_accountant_cannot_view_invoice_from_another_organization(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Entreprise A']);
        $orgB = Organization::factory()->create(['name' => 'Entreprise B']);

        $accountantA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $invoiceB = Invoice::factory()->create([
            'organization_id' => $orgB->id,
        ]);

        $response = $this->actingAs($accountantA)->get(route('portal.invoices.show', $invoiceB->id));

        $response->assertForbidden();
    }

    /**
     * Test 3 : Un comptable peut accéder au formulaire d'édition pour une facture modifiable de son entreprise.
     */
    public function test_accountant_can_view_edit_form_for_draft_invoice(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::DRAFT,
            'notes'           => 'Note test avant modification',
        ]);

        InvoiceItem::factory()->create([
            'invoice_id'  => $invoice->id,
            'description' => 'Item initial',
            'quantity'    => 2,
            'unit_price'  => 50000,
            'total'       => 100000,
        ]);

        $response = $this->actingAs($accountant)->get(route('portal.invoices.edit', $invoice->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Invoices/Edit')
            ->where('invoice.id', $invoice->id)
            ->where('invoice.notes', 'Note test avant modification')
            ->has('invoice.items', 1)
        );
    }

    /**
     * Test 4 : Un comptable ne peut PAS accéder au formulaire d'édition d'une facture PAYÉE (HTTP 403).
     */
    public function test_accountant_cannot_edit_paid_invoice(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $paidInvoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::PAID,
        ]);

        $response = $this->actingAs($accountant)->get(route('portal.invoices.edit', $paidInvoice->id));

        $response->assertForbidden();
    }

    /**
     * Test 5 : Un comptable met à jour une facture en conservant le statut brouillon.
     */
    public function test_accountant_can_update_draft_invoice_successfully(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::DRAFT,
            'subtotal'        => 50000,
            'tax_amount'      => 9000,
            'total'           => 59000,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id'  => $invoice->id,
            'description' => 'Ancien service',
            'quantity'    => 1,
            'unit_price'  => 50000,
            'total'       => 50000,
        ]);

        $payload = [
            'client_name'    => 'Client SARL Modifie',
            'client_email'   => 'compta@client-sarl.com',
            'client_address' => 'Nouvelle adresse client',
            'due_date'       => now()->addDays(20)->toDateString(),
            'notes'          => 'Conditions mises à jour',
            'action'         => 'draft',
            'items'          => [
                [
                    'description' => 'Nouveau matériel serveur',
                    'quantity'    => 2,
                    'unit_price'  => 200000,
                ],
                [
                    'description' => 'Configuration réseau',
                    'quantity'    => 1,
                    'unit_price'  => 50000,
                ],
            ],
        ];

        $response = $this->actingAs($accountant)->put(
            route('portal.invoices.update', $invoice->id),
            $payload
        );

        $response->assertRedirect(route('portal.invoices.show', $invoice->id));
        $response->assertSessionHas('success');

        // Vérification de la persistance et du recalcul exact en base de données
        $this->assertDatabaseHas('invoices', [
            'id'           => $invoice->id,
            'client_name'  => 'Client SARL Modifie',
            'client_email' => 'compta@client-sarl.com',
            'status'       => InvoiceStatus::DRAFT->value,
            'notes'        => 'Conditions mises à jour',
            'subtotal'     => 450000.00, // (2 * 200 000) + (1 * 50 000)
            'tax_amount'   => 81000.00,  // 450 000 * 18%
            'total'        => 531000.00, // 450 000 + 81 000
        ]);

        // Vérification que les anciens articles ont été remplacés par les nouveaux
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id'  => $invoice->id,
            'description' => 'Nouveau matériel serveur',
            'quantity'    => 2,
            'unit_price'  => 200000.00,
            'total'       => 400000.00,
        ]);
    }

    /**
     * Test 6 : Un comptable met à jour une facture et choisit "Enregistrer & Envoyer au client".
     */
    public function test_accountant_can_update_and_send_invoice_to_client(): void
    {
        Queue::fake();

        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::DRAFT,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
        ]);

        $payload = [
            'client_name'  => 'Client Entreprise Envoi',
            'client_email' => 'envoi@client-entreprise.com',
            'due_date'     => now()->addDays(30)->toDateString(),
            'notes'        => 'Paiement immédiat requis',
            'action'       => 'send',
            'items'        => [
                [
                    'description' => 'Développement spécifique API',
                    'quantity'    => 1,
                    'unit_price'  => 300000,
                ],
            ],
        ];

        $response = $this->actingAs($accountant)->put(
            route('portal.invoices.update', $invoice->id),
            $payload
        );

        $response->assertRedirect(route('portal.invoices.show', $invoice->id));
        $response->assertSessionHas('success');

        // Le statut doit avoir basculé vers SENT avec mise à jour du client
        $this->assertDatabaseHas('invoices', [
            'id'           => $invoice->id,
            'client_name'  => 'Client Entreprise Envoi',
            'client_email' => 'envoi@client-entreprise.com',
            'status'       => InvoiceStatus::SENT->value,
            'total'        => 354000.00, // 300 000 + (300 000 * 0.18)
        ]);

        Queue::assertPushed(SendInvoiceNotificationJob::class, function ($job) use ($invoice) {
            return $job->invoice->id === $invoice->id
                && $job->invoice->client_email === 'envoi@client-entreprise.com';
        });
    }

    /**
     * Test 7 : Un comptable peut transmettre directement une facture brouillon depuis l'action dédiée.
     */
    public function test_accountant_can_send_existing_draft_invoice(): void
    {
        Queue::fake();

        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::DRAFT,
        ]);

        $response = $this->actingAs($accountant)->post(
            route('portal.invoices.send', $invoice->id)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'id'     => $invoice->id,
            'status' => InvoiceStatus::SENT->value,
        ]);

        Queue::assertPushed(SendInvoiceNotificationJob::class);
    }

    /**
     * Test 8 : Un invité non authentifié est redirigé vers la page de connexion.
     */
    public function test_unauthenticated_user_cannot_access_portal_invoice_views(): void
    {
        $org = Organization::factory()->create();
        $invoice = Invoice::factory()->create(['organization_id' => $org->id]);

        $this->get(route('portal.invoices.show', $invoice->id))->assertRedirect(route('portal.login'));
        $this->get(route('portal.invoices.edit', $invoice->id))->assertRedirect(route('portal.login'));
        $this->put(route('portal.invoices.update', $invoice->id), [])->assertRedirect(route('portal.login'));
    }
}
