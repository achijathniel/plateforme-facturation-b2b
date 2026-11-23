<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les index de clés étrangères manquants et des index partiels PostgreSQL
     * pour accélérer les agrégations financières et le filtrage multi-tenant.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index('organization_id', 'users_organization_id_idx');
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->index('invoice_id', 'invoice_items_invoice_id_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index('invoice_id', 'payments_invoice_id_idx');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index('client_id', 'invoices_client_id_idx');
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->index(['deleted_at', 'created_at'], 'organizations_deleted_created_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE INDEX IF NOT EXISTS invoices_active_status_incl_total_idx ON invoices (status) INCLUDE (total) WHERE deleted_at IS NULL'
            );
            DB::statement(
                'CREATE INDEX IF NOT EXISTS invoices_active_org_status_incl_total_idx ON invoices (organization_id, status) INCLUDE (total) WHERE deleted_at IS NULL'
            );
        }
    }

    /**
     * Annule les index créés par cette migration.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS invoices_active_status_incl_total_idx');
            DB::statement('DROP INDEX IF EXISTS invoices_active_org_status_incl_total_idx');
        }

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropIndex('organizations_deleted_created_idx');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_client_id_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_invoice_id_idx');
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropIndex('invoice_items_invoice_id_idx');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_organization_id_idx');
        });
    }
};
