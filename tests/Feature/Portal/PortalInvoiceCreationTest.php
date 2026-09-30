<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Jobs\SendInvoiceNotificationJob;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PortalInvoiceCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1 : Un comptable connecté peut accéder à la page de création de facture.
     */
    public function test_accountant_can_view_invoice_creation_form(): void
    {
        $org = Organization::factory()->create(['name' => 'Acme Corp']);
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $response = $this->actingAs($accountant)->get(route('portal.invoices.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Invoices/Create')
            ->where('organization.name', 'Acme Corp')
            ->has('defaultDueDate')
        );
    }

    /**
     * Test 2 : Un utilisateur ayant le rôle CLIENT ne peut pas accéder au formulaire de création (HTTP 403).
     */
    public function test_client_cannot_access_invoice_creation_form(): void
    {
        $org = Organization::factory()->create();
        $client = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::CLIENT,
        ]);

        $response = $this->actingAs($client)->get(route('portal.invoices.create'));

        $response->assertForbidden();
    }

    /**
     * Test 3 : Un administrateur système ne peut pas accéder aux routes du portail comptable (HTTP 403).
     */
    public function test_admin_cannot_access_portal_invoice_creation_form(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('portal.invoices.create'));

        $response->assertForbidden();
    }

    /**
     * Test 4 : Un comptable peut créer une facture en mode BROUILLON (DRAFT) sans aucun envoi d'email.
     */
    public function test_accountant_can_create_draft_invoice_without_sending_email(): void
    {
        Queue::fake();

        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $payload = [
            'due_date' => now()->addDays(30)->toDateString(),
            'notes'    => 'Brouillon pour consultation préalable',
            'action'   => 'draft',
            'items'    => [
                [
                    'description' => 'Prestation audit financier',
                    'quantity'    => 2,
                    'unit_price'  => 250000,
                ],
            ],
        ];

        $response = $this->actingAs($accountant)->post(route('portal.invoices.store'), $payload);

        $response->assertRedirect(route('portal.dashboard'));
        $response->assertSessionHas('success');

        // Vérification en base : statut DRAFT
        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::DRAFT->value,
            'subtotal'        => '500000.00',
            'tax_amount'      => '90000.00',
            'total'           => '590000.00',
        ]);

        // Aucun job de notification ne doit avoir été poussé
        Queue::assertNotPushed(SendInvoiceNotificationJob::class);
    }

    /**
     * Test 5 : Un comptable peut créer et émettre immédiatement une facture avec envoi de l'email (SENT).
     */
    public function test_accountant_can_create_and_immediately_send_invoice(): void
    {
        Queue::fake();

        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $payload = [
            'due_date' => now()->addDays(30)->toDateString(),
            'notes'    => 'Facture directe pour validation client',
            'action'   => 'send',
            'items'    => [
                [
                    'description' => 'Développement spécifique Laravel',
                    'quantity'    => 1,
                    'unit_price'  => 800000,
                ],
            ],
        ];

        $response = $this->actingAs($accountant)->post(route('portal.invoices.store'), $payload);

        $response->assertRedirect(route('portal.dashboard'));
        $response->assertSessionHas('success');

        // Vérification en base : statut SENT
        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::SENT->value,
            'total'           => '944000.00',
        ]);

        // Le job de notification DOIT avoir été dispatché
        Queue::assertPushed(SendInvoiceNotificationJob::class, function ($job) use ($org) {
            return $job->invoice->organization_id === $org->id;
        });
    }

    /**
     * Test 6 : Un comptable peut émettre et expédier un brouillon de facture existant.
     */
    public function test_accountant_can_send_existing_draft_invoice(): void
    {
        Queue::fake();

        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $draftInvoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'status'          => InvoiceStatus::DRAFT,
            'total'           => 350000.00,
        ]);

        $response = $this->actingAs($accountant)->post(route('portal.invoices.send', $draftInvoice->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Statut mis à jour à SENT
        $this->assertDatabaseHas('invoices', [
            'id'     => $draftInvoice->id,
            'status' => InvoiceStatus::SENT->value,
        ]);

        Queue::assertPushed(SendInvoiceNotificationJob::class, function ($job) use ($draftInvoice) {
            return $job->invoice->id === $draftInvoice->id;
        });
    }

    /**
     * Test 7 : Un comptable ne peut pas émettre une facture appartenant à une autre organisation (HTTP 403).
     */
    public function test_accountant_cannot_send_invoice_of_another_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $accountantA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $foreignDraftInvoice = Invoice::factory()->create([
            'organization_id' => $orgB->id,
            'status'          => InvoiceStatus::DRAFT,
        ]);

        $response = $this->actingAs($accountantA)->post(route('portal.invoices.send', $foreignDraftInvoice->id));

        $response->assertForbidden();
    }

    /**
     * Test 8 : Un comptable peut consulter la liste des factures de son organisation.
     */
    public function test_accountant_can_view_invoices_list(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Acme Corp']);
        $orgB = Organization::factory()->create(['name' => 'Autre Entreprise Secrète']);

        $accountantA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        // Factures appartenant à Acme Corp
        $ownInvoices = Invoice::factory()->count(3)->create([
            'organization_id' => $orgA->id,
        ]);

        // Factures d'une autre entreprise (ne doivent absolument pas fuiter)
        $foreignInvoice = Invoice::factory()->create([
            'organization_id' => $orgB->id,
            'invoice_number'  => 'INV-SECRET-FORBIDDEN',
        ]);

        $response = $this->actingAs($accountantA)->get(route('portal.invoices.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Invoices/Index')
            ->where('organization.name', 'Acme Corp')
            ->has('invoices.data', 3)
            ->where('invoices.data.0.organization_id', $orgA->id)
            ->where('invoices.data.1.organization_id', $orgA->id)
            ->where('invoices.data.2.organization_id', $orgA->id)
        );
    }
}

