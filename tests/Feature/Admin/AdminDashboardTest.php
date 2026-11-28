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

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_forbidden_from_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::COLLABORATOR,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_dashboard_with_real_metrics(): void
    {
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::ADMIN,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'total' => '100000.00',
            'status' => InvoiceStatus::PAID,
            'currency' => 'XOF',
            'issue_date' => now(),
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'total' => '50000.00',
            'status' => InvoiceStatus::OVERDUE,
            'currency' => 'XOF',
            'issue_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('filters', fn (Assert $filters) => $filters
                ->where('period', '12m')
                ->where('status', null)
                ->where('organization_id', null)
            )
            ->has('organizations', 1)
            ->has('statuses', 5)
            ->has('stats', fn (Assert $stats) => $stats
                ->where('total_invoices_count', 2)
                ->where('total_organizations_count', 1)
                ->where('overdue_count', 1)
                ->where('collection_rate', 66.7)
                ->where('average_invoice_amount', '75000.00')
                ->has('total_billed')
                ->has('total_paid')
                ->has('total_overdue')
                ->has('monthly_series', 12)
                ->has('status_breakdown', 4)
                ->has('top_entities', 1)
                ->where('top_entities.0.name', 'Acme Corp')
                ->has('recent_invoices', 2)
                ->where('recent_invoices.0.organization_name', 'Acme Corp')
            )
        );
    }

    public function test_admin_can_filter_dashboard_by_period_status_and_organization(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Alpha SA']);
        $orgB = Organization::factory()->create(['name' => 'Beta SARL']);
        $admin = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => UserRole::ADMIN,
        ]);

        // Facture récente payée Alpha (incluse sur 30d + status=paid + orgA)
        Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'total' => '200000.00',
            'status' => InvoiceStatus::PAID,
            'issue_date' => now()->subDays(5),
        ]);

        // Facture ancienne Alpha (exclue sur 30d)
        Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'total' => '90000.00',
            'status' => InvoiceStatus::PAID,
            'issue_date' => now()->subDays(90),
        ]);

        // Facture Beta récente (exclue par organization_id=orgA)
        Invoice::factory()->create([
            'organization_id' => $orgB->id,
            'total' => '400000.00',
            'status' => InvoiceStatus::PAID,
            'issue_date' => now()->subDays(2),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'period' => '30d',
            'status' => 'paid',
            'organization_id' => $orgA->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('filters.period', '30d')
            ->where('filters.status', 'paid')
            ->where('filters.organization_id', $orgA->id)
            ->where('stats.total_invoices_count', 1)
            ->where('stats.collection_rate', 100)
            ->has('stats.monthly_series', 30)
            ->has('stats.top_entities', 1)
            ->where('stats.top_entities.0.name', 'Alpha SA')
            ->has('stats.recent_invoices', 1)
        );
    }

    public function test_admin_dashboard_rejects_invalid_period_filter(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', [
            'period' => 'invalid_period',
        ]));

        $response->assertSessionHasErrors('period');
    }
}
