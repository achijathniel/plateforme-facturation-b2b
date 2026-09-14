<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // 1. Index composite pour la pagination et le tri multi-tenancy (GET /api/invoices)
            $table->index(['organization_id', 'issue_date'], 'invoices_org_issue_date_idx');

            // 2. Index composite pour le reporting et les factures impayées (Règle ESR : Egalité, Statut, Plages/Tris)
            $table->index(['organization_id', 'status', 'issue_date', 'due_date'], 'invoices_org_status_dates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_org_issue_date_idx');
            $table->dropIndex('invoices_org_status_dates_idx');
        });
    }
};
