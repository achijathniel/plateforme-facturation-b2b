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

class AdminInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.invoices.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_forbidden_from_admin_invoices(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'role'            => UserRole::ACCOUNTANT,
        ]);

        $response = $this->actingAs($user)->get(route('admin.invoices.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_invoices_page_with_pagination(): void
    {
        $organization = Organization::factory()->create(['name' => 'Tech Solutions']);
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role'            => UserRole::ADMIN,
        ]);

        Invoice::factory()->count(5)->create([
            'organization_id' => $organization->id,
            'status'          => InvoiceStatus::PAID,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 5)
            ->has('organizations')
            ->has('filters')
            ->has('statuses')
        );
    }

    public function test_admin_can_filter_invoices_by_organization(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Alpha Logistics']);
        $orgB = Organization::factory()->create(['name' => 'Beta Industries']);

        $admin = User::factory()->create([
            'organization_id' => $orgA->id,
            'role'            => UserRole::ADMIN,
        ]);

        Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'invoice_number'  => 'INV-ALPHA-001',
        ]);

        Invoice::factory()->create([
            'organization_id' => $orgB->id,
            'invoice_number'  => 'INV-BETA-001',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'organization_id' => $orgA->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-ALPHA-001')
        );
    }

    public function test_admin_can_filter_invoices_by_status(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role'            => UserRole::ADMIN,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'status'          => InvoiceStatus::PAID,
            'invoice_number'  => 'INV-PAID-001',
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'status'          => InvoiceStatus::OVERDUE,
            'invoice_number'  => 'INV-OVERDUE-001',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', ['status' => 'paid']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-PAID-001')
        );
    }

    public function test_admin_can_search_invoices_by_number(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role'            => UserRole::ADMIN,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'invoice_number'  => 'INV-SEARCH-TARGET',
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'invoice_number'  => 'INV-OTHER-999',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', ['search' => 'TARGET']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-SEARCH-TARGET')
        );
    }

    public function test_admin_can_search_invoices_by_organization_name(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Target Enterprise']);
        $orgB = Organization::factory()->create(['name' => 'Other Corp']);

        $admin = User::factory()->create([
            'organization_id' => $orgA->id,
            'role'            => UserRole::ADMIN,
        ]);

        Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'invoice_number'  => 'INV-ORG-TARGET',
        ]);

        Invoice::factory()->create([
            'organization_id' => $orgB->id,
            'invoice_number'  => 'INV-ORG-OTHER',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', ['search' => 'Enterprise']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-ORG-TARGET')
        );
    }
}
