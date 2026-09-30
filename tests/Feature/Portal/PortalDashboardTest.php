<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_portal_login(): void
    {
        $response = $this->get(route('portal.dashboard'));

        $response->assertRedirect(route('portal.login'));
    }

    public function test_admin_is_forbidden_from_portal_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role'            => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('portal.dashboard'));

        $response->assertForbidden();
    }

    public function test_accountant_can_login_via_portal_login(): void
    {
        $organization = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $organization->id,
            'role'            => UserRole::ACCOUNTANT,
            'password'        => bcrypt('password'),
        ]);

        $response = $this->post(route('portal.login.store'), [
            'email'    => $accountant->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($accountant);
    }

    public function test_accountant_can_access_dashboard_with_isolated_organization_metrics(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Entreprise Alpha']);
        $orgB = Organization::factory()->create(['name' => 'Entreprise Beta']);

        $accountantA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        // Factures pour Entreprise Alpha
        Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'invoice_number'  => 'INV-ALPHA-01',
            'status'          => InvoiceStatus::PAID,
            'total'           => 1000.00,
            'currency'        => 'XOF',
            'issue_date'      => now()->subDays(2),
        ]);

        Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'invoice_number'  => 'INV-ALPHA-02',
            'status'          => InvoiceStatus::OVERDUE,
            'total'           => 500.00,
            'currency'        => 'XOF',
            'issue_date'      => now()->subDay(),
        ]);

        // Factures pour Entreprise Beta (ne doivent PAS apparaître pour le comptable A)
        Invoice::factory()->create([
            'organization_id' => $orgB->id,
            'invoice_number'  => 'INV-BETA-SECRET',
            'status'          => InvoiceStatus::PAID,
            'total'           => 99999.00,
            'currency'        => 'XOF',
            'issue_date'      => now(),
        ]);

        $response = $this->actingAs($accountantA)->get(route('portal.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Portal/Dashboard')
            ->has('stats')
            ->where('stats.total_invoices_count', 2)
            ->where('stats.total_billed', '1500')
            ->where('stats.total_paid', '1000')
            ->where('stats.total_overdue', '500')
            ->has('stats.recent_invoices', 2)
            ->where('organization.name', 'Entreprise Alpha')
        );
    }

    public function test_accountant_can_logout_from_portal(): void
    {
        $organization = Organization::factory()->create();
        $accountant = User::factory()->create([
            'organization_id' => $organization->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $response = $this->actingAs($accountant)->post(route('portal.logout'));

        $response->assertRedirect(route('portal.login'));
        $this->assertGuest();
    }
}
