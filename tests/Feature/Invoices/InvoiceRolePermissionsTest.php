<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class InvoiceRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organizationA;
    private Organization $organizationB;
    private User $directorA;
    private User $accountantA;
    private User $collaboratorA;
    private User $directorB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizationA = Organization::factory()->create(['name' => 'Entreprise A']);
        $this->organizationB = Organization::factory()->create(['name' => 'Entreprise B']);

        $this->directorA = User::factory()->director()->create([
            'organization_id' => $this->organizationA->id,
        ]);

        $this->accountantA = User::factory()->accountant()->create([
            'organization_id' => $this->organizationA->id,
        ]);

        $this->collaboratorA = User::factory()->collaborator()->create([
            'organization_id' => $this->organizationA->id,
        ]);

        $this->directorB = User::factory()->director()->create([
            'organization_id' => $this->organizationB->id,
        ]);
    }

    /**
     * Test 1 : Le Directeur peut accéder au formulaire et créer une facture pour son entreprise.
     */
    public function test_director_can_create_invoice_for_own_organization(): void
    {
        // 1. Accès au formulaire sur le portail
        $portalResponse = $this->actingAs($this->directorA)->get(route('portal.invoices.create'));
        $portalResponse->assertOk();

        // 2. Création via l'API REST
        $token = $this->directorA->createToken('director-token')->plainTextToken;
        $payload = [
            'due_date'          => now()->addDays(30)->toDateString(),
            'client_name'       => 'Client Test Direction',
            'client_email'      => 'client@direction.com',
            'items'             => [
                [
                    'description' => 'Prestation de conseil stratégique',
                    'quantity'    => 2,
                    'unit_price'  => 500000,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/invoices', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.client.name', 'Client Test Direction');

        $this->assertDatabaseHas('invoices', [
            'organization_id' => $this->organizationA->id,
            'client_name'     => 'Client Test Direction',
        ]);
    }

    /**
     * Test 2 : Le Directeur peut modifier et supprimer une facture brouillon de son entreprise.
     */
    public function test_director_can_modify_and_delete_draft_invoice_of_own_organization(): void
    {
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organizationA->id,
            'status'          => InvoiceStatus::DRAFT,
        ]);

        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        // Vérification des droits Policy
        $this->assertTrue(Gate::forUser($this->directorA)->allows('update', $invoice));
        $this->assertTrue(Gate::forUser($this->directorA)->allows('delete', $invoice));

        // Mise à jour via le portail
        $updateResponse = $this->actingAs($this->directorA)->put(
            route('portal.invoices.update', $invoice->id),
            [
                'client_name'  => 'Client SARL Direction Modif',
                'client_email' => 'client@direction.com',
                'due_date'     => now()->addDays(45)->toDateString(),
                'action'       => 'draft',
                'items'        => [
                    [
                        'description' => 'Audit financier et gouvernance',
                        'quantity'    => 1,
                        'unit_price'  => 750000,
                    ],
                ],
            ]
        );

        $updateResponse->assertRedirect(route('portal.invoices.show', $invoice->id));

        $this->assertDatabaseHas('invoices', [
            'id'          => $invoice->id,
            'client_name' => 'Client SARL Direction Modif',
        ]);
    }

    /**
     * Test 3 : Seul le Directeur possède l'habilitation d'approbation (Gate approve).
     */
    public function test_only_director_can_approve_invoices_for_own_organization(): void
    {
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organizationA->id,
            'status'          => InvoiceStatus::DRAFT,
        ]);

        // Le directeur de l'entreprise A peut approuver
        $this->assertTrue(Gate::forUser($this->directorA)->allows('approve', $invoice));

        // Le comptable NE peut PAS approuver
        $this->assertFalse(Gate::forUser($this->accountantA)->allows('approve', $invoice));

        // Le collaborateur NE peut PAS approuver
        $this->assertFalse(Gate::forUser($this->collaboratorA)->allows('approve', $invoice));

        // Le directeur d'une autre entreprise NE peut PAS approuver
        $this->assertFalse(Gate::forUser($this->directorB)->allows('approve', $invoice));
    }

    /**
     * Test 4 : Le Collaborateur peut consulter les factures de son entreprise (lecture seule).
     */
    public function test_collaborator_can_view_invoices_of_own_organization(): void
    {
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organizationA->id,
            'status'          => InvoiceStatus::SENT,
        ]);

        // Accès à la liste du portail
        $indexResponse = $this->actingAs($this->collaboratorA)->get(route('portal.invoices.index'));
        $indexResponse->assertOk();

        // Accès à la vue détaillée du portail
        $showPortalResponse = $this->actingAs($this->collaboratorA)->get(route('portal.invoices.show', $invoice->id));
        $showPortalResponse->assertOk();

        // Consultation de la facture via l'API REST
        $token = $this->collaboratorA->createToken('collab-token')->plainTextToken;
        $showResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/invoices/{$invoice->id}");

        $showResponse->assertOk()
            ->assertJsonPath('data.id', $invoice->id);
    }

    /**
     * Test 5 : Le Collaborateur reçoit un refus HTTP 403 s'il tente une mutation (création, modification, suppression).
     */
    public function test_collaborator_is_forbidden_from_mutating_invoices(): void
    {
        $invoice = Invoice::factory()->create([
            'organization_id' => $this->organizationA->id,
            'status'          => InvoiceStatus::DRAFT,
        ]);

        // Vérification stricte des Gates Policy
        $this->assertFalse(Gate::forUser($this->collaboratorA)->allows('create', Invoice::class));
        $this->assertFalse(Gate::forUser($this->collaboratorA)->allows('update', $invoice));
        $this->assertFalse(Gate::forUser($this->collaboratorA)->allows('delete', $invoice));
        $this->assertFalse(Gate::forUser($this->collaboratorA)->allows('approve', $invoice));

        // Tentative d'accès au formulaire de création
        $createFormResponse = $this->actingAs($this->collaboratorA)->get(route('portal.invoices.create'));
        $createFormResponse->assertForbidden();

        // Tentative d'accès au formulaire d'édition
        $editFormResponse = $this->actingAs($this->collaboratorA)->get(route('portal.invoices.edit', $invoice->id));
        $editFormResponse->assertForbidden();

        // Tentative de modification via requête PUT sur le portail (avec données valides)
        $updateResponse = $this->actingAs($this->collaboratorA)->put(
            route('portal.invoices.update', $invoice->id),
            [
                'client_name'  => 'Tentative Collaborateur',
                'client_email' => 'collaborateur@test.com',
                'due_date'     => now()->addDays(20)->toDateString(),
                'action'       => 'draft',
                'items'        => [
                    [
                        'description' => 'Service non autorisé',
                        'quantity'    => 1,
                        'unit_price'  => 10000,
                    ],
                ],
            ]
        );
        $updateResponse->assertForbidden();

        // Tentative de création via requête POST sur l'API
        $token = $this->collaboratorA->createToken('collab-token')->plainTextToken;
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/invoices', [
                'due_date'     => now()->addDays(15)->toDateString(),
                'client_name'  => 'Tentative API',
                'client_email' => 'tentative@api.com',
                'items'        => [['description' => 'Test', 'quantity' => 1, 'unit_price' => 1000]],
            ]);
        $createResponse->assertForbidden();
    }

    /**
     * Test 6 : Isolation Multi-Tenancy stricte entre entreprises différentes.
     */
    public function test_multi_tenancy_isolation_prevents_cross_organization_access(): void
    {
        $invoiceA = Invoice::factory()->create([
            'organization_id' => $this->organizationA->id,
        ]);

        $tokenB = $this->directorB->createToken('director-b-token')->plainTextToken;

        // Le directeur B ne peut pas voir la facture de l'entreprise A via l'API
        $response = $this->withHeader('Authorization', "Bearer {$tokenB}")
            ->getJson("/api/invoices/{$invoiceA->id}");

        $response->assertForbidden();

        // Le directeur B ne peut pas voir la facture de l'entreprise A via le portail
        $portalResponse = $this->actingAs($this->directorB)->get(route('portal.invoices.show', $invoiceA->id));
        $portalResponse->assertForbidden();
    }
}
