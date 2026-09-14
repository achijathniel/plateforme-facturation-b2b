<?php

namespace Tests\Feature\Invoices;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1 : Un comptable peut créer une facture avec calculs exacts de TVA et totaux.
     */
    public function test_accountant_can_create_invoice_with_accurate_tax_calculations(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);
        $token = $accountant->createToken('acc-token')->plainTextToken;

        $payload = [
            'due_date' => now()->addDays(30)->toDateString(),
            'notes'    => 'Facture de prestation de services IT.',
            'items'    => [
                [
                    'description' => 'Développement API REST Laravel',
                    'quantity'    => 2,
                    'unit_price'  => 500000,
                ],
                [
                    'description' => 'Configuration Infrastructure Docker & Nginx',
                    'quantity'    => 1,
                    'unit_price'  => 350000,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/invoices', $payload);

        // Sous-total = 1 000 000 + 350 000 = 1 350 000 XOF
        // TVA 18% = 243 000 XOF
        // Total TTC = 1 593 000 XOF
        $response->assertStatus(201)
            ->assertJsonPath('data.organization.id', $org->id)
            ->assertJsonPath('data.financials.subtotal', 1350000)
            ->assertJsonPath('data.financials.tax_amount', 243000)
            ->assertJsonPath('data.financials.total', 1593000)
            ->assertJsonPath('data.financials.currency', 'XOF')
            ->assertJsonCount(2, 'data.items');

        // Vérification en base de données
        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'subtotal'        => '1350000.00',
            'tax_amount'      => '243000.00',
            'total'           => '1593000.00',
            'currency'        => 'XOF',
        ]);

        $this->assertDatabaseCount('invoice_items', 2);
    }

    /**
     * Test 2 : Sécurité RBAC - Un client ne peut pas créer de facture (HTTP 403).
     */
    public function test_client_cannot_create_invoice(): void
    {
        $org = Organization::factory()->create();
        $client = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::CLIENT,
        ]);
        $token = $client->createToken('client-token')->plainTextToken;

        $payload = [
            'due_date' => now()->addDays(15)->toDateString(),
            'items'    => [
                [
                    'description' => 'Tentative frauduleuse',
                    'quantity'    => 1,
                    'unit_price'  => 100000,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/invoices', $payload);

        $response->assertStatus(403);
    }

    /**
     * Test 3 : Un administrateur peut créer une facture pour n'importe quelle organisation.
     */
    public function test_admin_can_create_invoice_for_any_organization(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $admin->createToken('admin-token')->plainTextToken;

        $payload = [
            'organization_id' => $org->id,
            'due_date'        => now()->addDays(20)->toDateString(),
            'items'           => [
                [
                    'description' => 'Audit de sécurité annuel',
                    'quantity'    => 1,
                    'unit_price'  => 800000,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/invoices', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.organization.id', $org->id)
            ->assertJsonPath('data.financials.subtotal', 800000)
            ->assertJsonPath('data.financials.tax_amount', 144000)
            ->assertJsonPath('data.financials.total', 944000);

        $this->assertDatabaseHas('invoices', [
            'organization_id' => $org->id,
            'total'           => '944000.00',
        ]);
    }

    /**
     * Test 4 : Échec de validation en cas de données erronées ou manquantes (HTTP 422).
     */
    public function test_invoice_creation_fails_validation_with_invalid_data(): void
    {
        $org = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $org->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);
        $token = $accountant->createToken('acc-token')->plainTextToken;

        // Date passée et liste d'items vide
        $payload = [
            'due_date' => now()->subDay()->toDateString(),
            'items'    => [],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/invoices', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date', 'items']);
    }
}
