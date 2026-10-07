<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $client1 = Client::factory()->create([
            'organization_id' => $org->id,
            'name'            => 'Société Industrielle d’Abidjan',
            'email'           => 'contact@sia.ci',
        ]);

        $client2 = Client::factory()->create([
            'organization_id' => $org->id,
            'name'            => 'Boutique Alpha Tech',
            'email'           => 'alpha@tech.ci',
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
            'role'            => UserRole::ACCOUNTANT,
        ]);

        Client::factory()->create([
            'organization_id' => $orgB->id,
            'name'            => 'Confidentiel Corp B',
            'email'           => 'secret@orgb.ci',
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
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $payload = [
            'name'       => 'Nouveau Client SARL',
            'email'      => 'info@nouveau-client.ci',
            'phone'      => '+225 0102030405',
            'address'    => 'Plateau, Boulevard Lagunaire',
            'tax_number' => 'CI-ABJ-2026-B-9988',
        ];

        $response = $this->actingAs($accountant)->postJson(route('portal.clients.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('client.name', 'Nouveau Client SARL')
            ->assertJsonPath('client.email', 'info@nouveau-client.ci');

        $this->assertDatabaseHas('clients', [
            'organization_id' => $org->id,
            'name'            => 'Nouveau Client SARL',
            'email'           => 'info@nouveau-client.ci',
            'phone'           => '+225 0102030405',
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
            'role'            => UserRole::COLLABORATOR,
        ]);

        $payload = [
            'name'  => 'Tentative Interdite',
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
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $client = Client::factory()->create([
            'organization_id' => $org->id,
            'name'            => 'Client Fidélisé SA',
            'email'           => 'fidelise@client.ci',
            'address'         => 'Zone 4, Rue du Dr Blanchard',
            'tax_number'      => 'CI-ABJ-12345',
            'phone'           => '+225 0506070809',
        ]);

        $payload = [
            'client_id'      => $client->id,
            'client_name'    => 'Client Fidélisé SA',
            'client_email'   => 'fidelise@client.ci',
            'client_address' => 'Zone 4, Rue du Dr Blanchard',
            'due_date'       => now()->addDays(30)->toDateString(),
            'notes'          => 'Prestation trimestrielle',
            'action'         => 'draft',
            'items'          => [
                [
                    'description' => 'Abonnement Cloud Annuel',
                    'quantity'    => 1,
                    'unit_price'  => 600000,
                ],
            ],
        ];

        $response = $this->actingAs($accountant)->post(route('portal.invoices.store'), $payload);

        $response->assertRedirect(route('portal.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'client_id'       => $client->id,
            'client_name'     => 'Client Fidélisé SA',
            'client_email'    => 'fidelise@client.ci',
            'status'          => InvoiceStatus::DRAFT->value,
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
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $payload = [
            'client_name'         => 'Client Entièrement Nouveau',
            'client_email'        => 'nouveau@client-inconnu.ci',
            'client_address'      => 'Plateau Immeuble Trade Center',
            'client_tax_number'   => 'CI-NEW-9999',
            'client_phone'        => '+225 2720202020',
            'due_date'            => now()->addDays(15)->toDateString(),
            'notes'               => 'Premier contrat',
            'action'              => 'draft',
            'items'               => [
                [
                    'description' => 'Étude de faisabilité',
                    'quantity'    => 1,
                    'unit_price'  => 450000,
                ],
            ],
        ];

        $response = $this->actingAs($accountant)->post(route('portal.invoices.store'), $payload);

        $response->assertRedirect(route('portal.dashboard'));

        // Le client DOIT avoir été créé automatiquement dans la table clients
        $this->assertDatabaseHas('clients', [
            'organization_id' => $org->id,
            'name'            => 'Client Entièrement Nouveau',
            'email'           => 'nouveau@client-inconnu.ci',
            'tax_number'      => 'CI-NEW-9999',
        ]);

        $client = Client::where('organization_id', $org->id)
            ->where('name', 'Client Entièrement Nouveau')
            ->firstOrFail();

        // La facture doit référencer ce nouveau client via client_id
        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'client_id'       => $client->id,
            'client_name'     => 'Client Entièrement Nouveau',
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
            'role'            => UserRole::ACCOUNTANT,
        ]);

        Client::factory()->create([
            'organization_id' => $org->id,
            'name'            => 'Doublon SARL',
        ]);

        $payload = [
            'name'  => 'Doublon SARL',
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
            'role'            => UserRole::ACCOUNTANT,
        ]);

        Client::factory()->create([
            'organization_id' => $orgA->id,
            'name'            => 'Même Nom SAS',
        ]);

        $payload = [
            'name'  => 'Même Nom SAS',
            'email' => 'client@orgb.ci',
        ];

        $response = $this->actingAs($accountantB)->postJson(route('portal.clients.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseCount('clients', 2);
    }
}
