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
            'role'            => UserRole::COLLABORATOR,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_dashboard_with_real_metrics(): void
    {
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role'            => UserRole::ADMIN,
        ]);

        // Création de 2 factures
        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'total'           => '100000.00',
            'status'          => InvoiceStatus::PAID,
            'currency'        => 'XOF',
            'issue_date'      => now(),
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'total'           => '50000.00',
            'status'          => InvoiceStatus::OVERDUE,
            'currency'        => 'XOF',
            'issue_date'      => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('stats', fn (Assert $stats) => $stats
                ->where('total_invoices_count', 2)
                ->where('total_organizations_count', 1)
                ->has('total_billed')
                ->has('total_paid')
                ->has('total_overdue')
                ->has('recent_invoices', 2)
                ->where('recent_invoices.0.organization_name', 'Acme Corp')
            )
        );
    }
}
