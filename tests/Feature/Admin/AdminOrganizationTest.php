<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AdminOrganizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1 : Un invité non authentifié est redirigé vers la page de connexion admin.
     */
    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.organizations.index'));

        $response->assertRedirect(route('admin.login'));
    }

    /**
     * Test 2 : Un utilisateur ayant le rôle ACCOUNTANT ne peut pas accéder aux organisations (HTTP 403).
     */
    public function test_accountant_cannot_access_admin_organizations(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $response = $this->actingAs($accountant)->get(route('admin.organizations.index'));

        $response->assertForbidden();
    }

    /**
     * Test 3 : Un administrateur peut consulter la liste paginée des entreprises.
     */
    public function test_admin_can_view_paginated_organizations(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Organization::factory()->count(4)->create();

        $response = $this->actingAs($admin)->get(route('admin.organizations.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Organizations/Index')
            ->has('organizations.data', 4)
            ->has('filters')
        );
    }

    /**
     * Test 4 : Un administrateur peut filtrer les entreprises par terme de recherche.
     */
    public function test_admin_can_filter_organizations_by_search_term(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Organization::factory()->create([
            'name'       => 'Société Ivoirienne de Cacao',
            'tax_number' => 'CI-ABJ-2026-B-001',
        ]);

        Organization::factory()->create([
            'name'       => 'Boutique Alpha Tech',
            'tax_number' => 'CI-ABJ-2026-B-999',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.organizations.index', [
            'search' => 'Cacao',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Organizations/Index')
            ->has('organizations.data', 1)
            ->where('organizations.data.0.name', 'Société Ivoirienne de Cacao')
            ->where('filters.search', 'Cacao')
        );
    }

    /**
     * Test 5 : Un administrateur peut filtrer les entreprises par statut d'activité (actif / inactif).
     */
    public function test_admin_can_filter_organizations_by_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Organization::factory()->create([
            'name'      => 'Entreprise Active SARL',
            'is_active' => true,
        ]);

        Organization::factory()->create([
            'name'      => 'Entreprise Suspendue SA',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.organizations.index', [
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Organizations/Index')
            ->has('organizations.data', 1)
            ->where('organizations.data.0.name', 'Entreprise Suspendue SA')
            ->where('filters.status', 'inactive')
        );
    }

    /**
     * Test 6 : Un administrateur peut accéder au formulaire de création d'entreprise.
     */
    public function test_admin_can_view_create_organization_form(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.organizations.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Organizations/Create')
        );
    }

    /**
     * Test 7 : Un administrateur peut enregistrer une nouvelle entreprise avec succès.
     */
    public function test_admin_can_create_organization_successfully(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $payload = [
            'name'       => 'Nouvelle Entreprise Logistique',
            'tax_number' => 'CI-ABJ-2026-B-12345',
            'email'      => 'contact@logistique-ci.com',
            'phone'      => '+225 07 00 00 11 22',
            'address'    => 'Port Autonome d’Abidjan, Zone Industrielle',
            'is_active'  => true,
        ];

        $response = $this->actingAs($admin)->post(route('admin.organizations.store'), $payload);

        $created = Organization::where('tax_number', 'CI-ABJ-2026-B-12345')->first();
        $this->assertNotNull($created);

        $response->assertRedirect(route('admin.organizations.show', $created->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('organizations', [
            'name'       => 'Nouvelle Entreprise Logistique',
            'tax_number' => 'CI-ABJ-2026-B-12345',
            'email'      => 'contact@logistique-ci.com',
            'is_active'  => true,
        ]);
    }

    /**
     * Test 8 : Rejet de la création si l'email ou le numéro fiscal existe déjà (HTTP 422).
     */
    public function test_organization_creation_fails_with_duplicate_tax_number_or_email(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Organization::factory()->create([
            'tax_number' => 'CI-EXISTING-TAX-01',
            'email'      => 'deja-pris@entreprise.com',
        ]);

        $payload = [
            'name'       => 'Tentative Doublon',
            'tax_number' => 'CI-EXISTING-TAX-01',
            'email'      => 'deja-pris@entreprise.com',
        ];

        $response = $this->actingAs($admin)->post(route('admin.organizations.store'), $payload);

        $response->assertSessionHasErrors(['tax_number', 'email']);
    }

    /**
     * Test 9 : Un administrateur peut consulter la fiche détaillée 360° d'une organisation.
     */
    public function test_admin_can_view_organization_details(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $org = Organization::factory()->create(['name' => 'Acme Holding SA']);

        User::factory()->create([
            'organization_id' => $org->id,
            'name'            => 'Jean Dupont',
            'role'            => UserRole::ACCOUNTANT,
        ]);

        Invoice::factory()->create([
            'organization_id' => $org->id,
            'invoice_number'  => 'INV-ACME-001',
            'status'          => InvoiceStatus::PAID,
            'total'           => 1500000.00,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.organizations.show', $org->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Organizations/Show')
            ->where('organization.id', $org->id)
            ->where('organization.name', 'Acme Holding SA')
            ->where('organization.invoices_count', 1)
            ->where('organization.users_count', 1)
            ->has('organization.users', 1)
            ->has('organization.invoices', 1)
        );
    }

    /**
     * Test 10 : Un administrateur peut modifier les informations d'une organisation.
     */
    public function test_admin_can_update_organization_successfully(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $org = Organization::factory()->create([
            'name'       => 'Ancien Nom SARL',
            'tax_number' => 'CI-OLD-TAX-99',
            'email'      => 'ancien@email.com',
        ]);

        $payload = [
            'name'       => 'Nouveau Nom SARL',
            'tax_number' => 'CI-OLD-TAX-99', // Même numéro fiscal conservé sans erreur unique
            'email'      => 'nouveau@email.com',
            'phone'      => '+225 01 02 03 04 05',
            'address'    => 'Plateau, Rue du Commerce',
            'is_active'  => true,
        ];

        $response = $this->actingAs($admin)->put(route('admin.organizations.update', $org->id), $payload);

        $response->assertRedirect(route('admin.organizations.show', $org->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('organizations', [
            'id'    => $org->id,
            'name'  => 'Nouveau Nom SARL',
            'email' => 'nouveau@email.com',
        ]);
    }

    /**
     * Test 11 : Un administrateur peut activer ou suspendre une organisation en un clic.
     */
    public function test_admin_can_toggle_organization_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $org = Organization::factory()->create(['is_active' => true]);

        // Première bascule : Actif -> Suspendu
        $response = $this->actingAs($admin)->post(route('admin.organizations.toggle-status', $org->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse($org->fresh()->is_active);

        // Deuxième bascule : Suspendu -> Actif
        $this->actingAs($admin)->post(route('admin.organizations.toggle-status', $org->id));
        $this->assertTrue($org->fresh()->is_active);
    }
}
