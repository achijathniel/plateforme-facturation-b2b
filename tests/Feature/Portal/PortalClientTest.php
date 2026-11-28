<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PortalClientTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1 : Un utilisateur du portail peut rechercher les clients de son organisation.
     */
    public function test_authenticated_portal_user_can_search_clients_of_their_organization(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        $client1 = Client::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Société Industrielle d’Abidjan',
            'email' => 'contact@sia.ci',
        ]);

        $client2 = Client::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Boutique Alpha Tech',
            'email' => 'alpha@tech.ci',
        ]);

        $response = $this->actingAs($accountant)->getJson(route('portal.clients.search', ['q' => 'Industrielle']));

        $response->assertOk()
            ->assertJsonCount(1, 'clients')
            ->assertJsonPath('clients.0.id', $client1->id)
            ->assertJsonPath('clients.0.name', 'Société Industrielle d’Abidjan')
            ->assertJsonPath('clients.0.email', 'contact@sia.ci');
    }

    /**
     * Test 2 : Étanchéité multi-tenancy : la recherche ne fait fuiter aucun client d'une autre organisation.
     */
    public function test_client_search_does_not_leak_clients_from_other_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $accountantA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        Client::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Confidentiel Corp B',
            'email' => 'secret@orgb.ci',
        ]);

        $response = $this->actingAs($accountantA)->getJson(route('portal.clients.search', ['q' => 'Confidentiel']));

        $response->assertOk()
            ->assertJsonCount(0, 'clients');
    }

    /**
     * Test 3 : Un comptable ou directeur peut créer un client directement via le portail.
     */
    public function test_accountant_can_create_new_client_via_portal(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        $payload = [
            'name' => 'Nouveau Client SARL',
            'email' => 'info@nouveau-client.ci',
            'phone' => '+225 0102030405',
            'address' => 'Plateau, Boulevard Lagunaire',
            'tax_number' => 'CI-ABJ-2026-B-9988',
        ];

        $response = $this->actingAs($accountant)->postJson(route('portal.clients.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('client.name', 'Nouveau Client SARL')
            ->assertJsonPath('client.email', 'info@nouveau-client.ci');

        $this->assertDatabaseHas('clients', [
            'organization_id' => $org->id,
            'name' => 'Nouveau Client SARL',
            'email' => 'info@nouveau-client.ci',
            'phone' => '+225 0102030405',
        ]);
    }

    /**
     * Test 4 : Sécurité RBAC : un collaborateur ne peut pas ajouter un client dans l'annuaire (HTTP 403).
     */
    public function test_collaborator_cannot_create_client(): void
    {
        $org = Organization::factory()->create();
        $collaborator = User::factory()->create([
            'organization_id' => $org->id,
            'role' => UserRole::COLLABORATOR,
        ]);

        $payload = [
            'name' => 'Tentative Interdite',
            'email' => 'hacker@test.com',
        ];

        $response = $this->actingAs($collaborator)->postJson(route('portal.clients.store'), $payload);

        $response->assertForbidden();
    }

    /**
     * Test 5 : Création de facture avec client_id associe bien le client et préserve les snapshots immuables.
     */
    public function test_creating_invoice_with_client_id_associates_client_and_preserves_snapshots(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        $client = Client::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Client Fidélisé SA',
            'email' => 'fidelise@client.ci',
            'address' => 'Zone 4, Rue du Dr Blanchard',
            'tax_number' => 'CI-ABJ-12345',
            'phone' => '+225 0506070809',
        ]);

        $payload = [
            'client_id' => $client->id,
            'client_name' => 'Client Fidélisé SA',
            'client_email' => 'fidelise@client.ci',
            'client_address' => 'Zone 4, Rue du Dr Blanchard',
            'due_date' => now()->addDays(30)->toDateString(),
            'notes' => 'Prestation trimestrielle',
            'action' => 'draft',
            'items' => [
                [
                    'description' => 'Abonnement Cloud Annuel',
                    'quantity' => 1,
                    'unit_price' => 600000,
                ],
            ],
        ];

        $response = $this->actingAs($accountant)->post(route('portal.invoices.store'), $payload);

        $response->assertRedirect(route('portal.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'client_id' => $client->id,
            'client_name' => 'Client Fidélisé SA',
            'client_email' => 'fidelise@client.ci',
            'status' => InvoiceStatus::DRAFT->value,
        ]);
    }

    /**
     * Test 6 : Création de facture sans client_id crée automatiquement le client dans l'annuaire clients.
     */
    public function test_creating_invoice_without_client_id_auto_creates_client_in_directory(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        $payload = [
            'client_name' => 'Client Entièrement Nouveau',
            'client_email' => 'nouveau@client-inconnu.ci',
            'client_address' => 'Plateau Immeuble Trade Center',
            'client_tax_number' => 'CI-NEW-9999',
            'client_phone' => '+225 2720202020',
            'due_date' => now()->addDays(15)->toDateString(),
            'notes' => 'Premier contrat',
            'action' => 'draft',
            'items' => [
                [
                    'description' => 'Étude de faisabilité',
                    'quantity' => 1,
                    'unit_price' => 450000,
                ],
            ],
        ];

        $response = $this->actingAs($accountant)->post(route('portal.invoices.store'), $payload);

        $response->assertRedirect(route('portal.dashboard'));

        // Le client DOIT avoir été créé automatiquement dans la table clients
        $this->assertDatabaseHas('clients', [
            'organization_id' => $org->id,
            'name' => 'Client Entièrement Nouveau',
            'email' => 'nouveau@client-inconnu.ci',
            'tax_number' => 'CI-NEW-9999',
        ]);

        $client = Client::where('organization_id', $org->id)
            ->where('name', 'Client Entièrement Nouveau')
            ->firstOrFail();

        // La facture doit référencer ce nouveau client via client_id
        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'client_id' => $client->id,
            'client_name' => 'Client Entièrement Nouveau',
        ]);
    }

    /**
     * Test 7 : Validation d'unicité : échec 422 si un client avec le même nom existe déjà dans l'organisation.
     */
    public function test_cannot_create_duplicate_client_name_in_same_organization(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        Client::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Doublon SARL',
        ]);

        $payload = [
            'name' => 'Doublon SARL',
            'email' => 'autre@doublon.ci',
        ];

        $response = $this->actingAs($accountant)->postJson(route('portal.clients.store'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test 8 : Deux organisations distinctes peuvent avoir un client du même nom sans conflit.
     */
    public function test_same_client_name_can_exist_in_different_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $accountantB = User::factory()->create([
            'organization_id' => $orgB->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        Client::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Même Nom SAS',
        ]);

        $payload = [
            'name' => 'Même Nom SAS',
            'email' => 'client@orgb.ci',
        ];

        $response = $this->actingAs($accountantB)->postJson(route('portal.clients.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseCount('clients', 2);
    }

    /**
     * Test 9 : L'annuaire clients affiche uniquement les clients de l'organisation avec statistiques agrégées et recherche.
     */
    public function test_accountant_can_list_and_filter_clients_with_aggregated_stats(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $accountant = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        $clientMatch = Client::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Alpha Logistique 100% Pro',
            'tax_number' => 'CI-TAX_99',
        ]);
        $clientOther = Client::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Zeta Commerce',
        ]);
        Client::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Alpha Confidentiel Org B',
        ]);

        Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'client_id' => $clientMatch->id,
            'status' => InvoiceStatus::PAID,
            'total' => 250000,
        ]);

        // Liste complète isolée à orgA
        $this->actingAs($accountant)
            ->get(route('portal.clients.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Clients/Index')
                ->where('canManageClients', true)
                ->has('clients.data', 2)
                ->where('clients.data.0.id', $clientMatch->id)
                ->where('clients.data.0.invoices_count', 1)
                ->where('clients.data.1.id', $clientOther->id)
            );

        // Recherche avec jokers SQL % et _ échappés correctement
        $this->actingAs($accountant)
            ->get(route('portal.clients.index', ['search' => '100%']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Clients/Index')
                ->has('clients.data', 1)
                ->where('clients.data.0.id', $clientMatch->id)
            );
    }

    /**
     * Test 10 : Un collaborateur consulte l'annuaire en lecture seule (canManageClients = false).
     */
    public function test_collaborator_can_view_clients_directory_in_read_only_mode(): void
    {
        $org = Organization::factory()->create();
        $collaborator = User::factory()->create([
            'organization_id' => $org->id,
            'role' => UserRole::COLLABORATOR,
        ]);

        Client::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($collaborator)
            ->get(route('portal.clients.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Clients/Index')
                ->where('canManageClients', false)
                ->has('clients.data', 1)
            );

        $this->actingAs($collaborator)
            ->get(route('portal.clients.create'))
            ->assertForbidden();
    }

    /**
     * Test 11 : La création Inertia redirige vers la fiche client avec message flash (sans casser le flux JSON).
     */
    public function test_accountant_can_create_client_via_inertia_form(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        $this->actingAs($accountant)
            ->get(route('portal.clients.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Portal/Clients/Create'));

        $response = $this->actingAs($accountant)
            ->withHeaders(['X-Inertia' => 'true'])
            ->post(route('portal.clients.store'), [
                'name' => 'Ivoirie Services SARL',
                'email' => 'factures@ivoirie.ci',
                'phone' => '+225 27 21 00 00 00',
                'address' => 'Cocody Deux-Plateaux',
                'tax_number' => 'CI-ABJ-2026-B-4455',
                'notes' => 'Virement 30 jours',
            ]);

        $created = Client::where('organization_id', $org->id)
            ->where('name', 'Ivoirie Services SARL')
            ->firstOrFail();

        $response->assertRedirect(route('portal.clients.show', $created->id))
            ->assertSessionHas('success');
    }

    /**
     * Test 12 : La fiche 360° affiche les KPIs financiers, l'historique des factures et bloque l'accès cross-tenant.
     */
    public function test_accountant_can_view_client_show_with_stats_and_cannot_access_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $accountantA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        $clientA = Client::factory()->create(['organization_id' => $orgA->id]);
        $clientB = Client::factory()->create(['organization_id' => $orgB->id]);

        Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'client_id' => $clientA->id,
            'status' => InvoiceStatus::PAID,
            'total' => 150000,
        ]);
        Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'client_id' => $clientA->id,
            'status' => InvoiceStatus::SENT,
            'total' => 75000,
        ]);

        $this->actingAs($accountantA)
            ->get(route('portal.clients.show', $clientA->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Clients/Show')
                ->where('canManageClients', true)
                ->where('client.id', $clientA->id)
                ->where('client.invoices_count', 2)
                ->where('client.total_billed', fn ($val) => (float) $val === 225000.0)
                ->where('client.total_paid', fn ($val) => (float) $val === 150000.0)
                ->where('client.total_pending', fn ($val) => (float) $val === 75000.0)
                ->has('client.invoices', 2)
            );

        $this->actingAs($accountantA)
            ->get(route('portal.clients.show', $clientB->id))
            ->assertNotFound();
    }

    /**
     * Test 13 : Un comptable peut modifier un client (en conservant son propre nom) et un doublon est rejeté en 422.
     */
    public function test_accountant_can_update_client_and_respects_uniqueness_and_rbac(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $accountantA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::ACCOUNTANT,
        ]);
        $collaboratorA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::COLLABORATOR,
        ]);

        $client1 = Client::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Entreprise Un',
        ]);
        $client2 = Client::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Entreprise Deux',
        ]);
        $clientOtherOrg = Client::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Client Org B',
        ]);

        $this->actingAs($accountantA)
            ->get(route('portal.clients.edit', $client1->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Clients/Edit')
                ->where('client.id', $client1->id)
            );

        // Mise à jour valide en conservant son propre nom
        $this->actingAs($accountantA)
            ->put(route('portal.clients.update', $client1->id), [
                'name' => 'Entreprise Un',
                'email' => 'nouveau-contact@un.ci',
                'phone' => '+225 0101010101',
                'notes' => 'Coordonnées mises à jour',
            ])
            ->assertRedirect(route('portal.clients.show', $client1->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'id' => $client1->id,
            'email' => 'nouveau-contact@un.ci',
            'notes' => 'Coordonnées mises à jour',
        ]);

        // Rejet doublon avec client2
        $this->actingAs($accountantA)
            ->put(route('portal.clients.update', $client1->id), [
                'name' => 'Entreprise Deux',
            ])
            ->assertSessionHasErrors(['name']);

        // Refus collaborateur et cross-tenant
        $this->actingAs($collaboratorA)
            ->put(route('portal.clients.update', $client1->id), ['name' => 'Interdit'])
            ->assertForbidden();

        $this->actingAs($accountantA)
            ->put(route('portal.clients.update', $clientOtherOrg->id), ['name' => 'Hacking'])
            ->assertNotFound();
    }

    /**
     * Test 14 : Archiver un client effectue un soft-delete, conserve ses factures et permet de recréer un client homonyme.
     */
    public function test_accountant_can_archive_client_and_recreate_same_name_while_preserving_invoices(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $accountantA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::ACCOUNTANT,
        ]);
        $collaboratorA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::COLLABORATOR,
        ]);

        $client = Client::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Client Archivable SA',
        ]);
        $clientB = Client::factory()->create(['organization_id' => $orgB->id]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'client_id' => $client->id,
            'client_name' => 'Client Archivable SA',
            'total' => 100000,
        ]);

        $this->actingAs($collaboratorA)
            ->delete(route('portal.clients.destroy', $client->id))
            ->assertForbidden();

        $this->actingAs($accountantA)
            ->delete(route('portal.clients.destroy', $clientB->id))
            ->assertNotFound();

        $this->actingAs($accountantA)
            ->delete(route('portal.clients.destroy', $client->id))
            ->assertRedirect(route('portal.clients.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'client_name' => 'Client Archivable SA',
            'deleted_at' => null,
        ]);

        // Grâce à l'index unique partiel WHERE deleted_at IS NULL, un nouveau client homonyme peut être créé
        $this->actingAs($accountantA)
            ->postJson(route('portal.clients.store'), [
                'name' => 'Client Archivable SA',
                'email' => 'reprise@archivable.ci',
            ])
            ->assertStatus(201);
    }

    /**
     * Test 15 : L'ouverture du formulaire de facture avec ?client_id= pré-remplit uniquement les clients de l'organisation.
     */
    public function test_invoice_create_preselects_client_from_same_organization_only(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $accountantA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::ACCOUNTANT,
        ]);

        $clientA = Client::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Client Pré-sélectionné',
            'email' => 'pre@client.ci',
        ]);
        $clientB = Client::factory()->create(['organization_id' => $orgB->id]);

        $this->actingAs($accountantA)
            ->get(route('portal.invoices.create', ['client_id' => $clientA->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Invoices/Create')
                ->where('preselectedClient.id', $clientA->id)
                ->where('preselectedClient.name', 'Client Pré-sélectionné')
                ->where('preselectedClient.email', 'pre@client.ci')
            );

        $this->actingAs($accountantA)
            ->get(route('portal.invoices.create', ['client_id' => $clientB->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Invoices/Create')
                ->where('preselectedClient', null)
            );
    }
}
