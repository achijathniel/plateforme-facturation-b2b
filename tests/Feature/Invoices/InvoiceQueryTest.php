<?php

namespace Tests\Feature\Invoices;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceQueryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1 : Un administrateur peut voir toutes les factures de toutes les entreprises.
     */
    public function test_admin_can_view_all_invoices_across_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Invoice::factory()->count(3)->create(['organization_id' => $orgA->id]);
        Invoice::factory()->count(2)->create(['organization_id' => $orgB->id]);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 5);
    }

    /**
     * Test 2 : Multi-tenancy - Un utilisateur ne liste que les factures de sa propre entreprise.
     */
    public function test_user_can_only_view_invoices_from_own_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Invoice::factory()->count(3)->create(['organization_id' => $orgA->id]);
        Invoice::factory()->count(4)->create(['organization_id' => $orgB->id]);

        $accountant = User::factory()->create([
            'organization_id' => $orgA->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);
        $token = $accountant->createToken('accountant-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 3);
    }

    /**
     * Test 3 : Consultation détaillée d'une facture autorisée avec formatage financier.
     */
    public function test_user_can_view_single_invoice_from_own_organization(): void
    {
        $org = Organization::factory()->create(['name' => 'Tech Solution SAS']);
        $invoice = Invoice::factory()->create([
            'organization_id' => $org->id,
            'subtotal'        => '100000.00',
            'tax_amount'      => '18000.00',
            'total'           => '118000.00',
            'currency'        => 'XOF',
        ]);

        InvoiceItem::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $collaborator = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::COLLABORATOR,
        ]);
        $token = $collaborator->createToken('collaborator-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.organization.name', 'Tech Solution SAS')
            ->assertJsonPath('data.financials.currency', 'XOF')
            ->assertJsonPath('data.financials.total', 118000)
            ->assertJsonCount(2, 'data.items');
    }

    /**
     * Test 4 : Sécurité Policy - Rejet 403 en cas de tentative d'accès à la facture d'une autre entreprise.
     */
    public function test_user_cannot_view_invoice_from_another_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $invoiceOrgB = Invoice::factory()->create(['organization_id' => $orgB->id]);

        $userOrgA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role'            => UserRole::COLLABORATOR,
        ]);
        $token = $userOrgA->createToken('orgA-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/invoices/{$invoiceOrgB->id}");

        $response->assertStatus(403);
    }

    /**
     * Test 5 : Accès rejeté en 401 si non authentifié.
     */
    public function test_unauthenticated_user_cannot_view_invoices(): void
    {
        $this->getJson('/api/invoices')->assertStatus(401);
        $this->getJson('/api/invoices/1')->assertStatus(401);
    }

    /**
     * Test 6 : Erreur 404 si la facture demandée n'existe pas.
     */
    public function test_viewing_nonexistent_invoice_returns_404(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $user->createToken('admin-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/invoices/99999');

        $response->assertStatus(404);
    }
}
