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
            // 1. Index composite pour les agrégations financières globales (espace admin)
            $table->index(['status', 'total'], 'invoices_status_total_idx');

            // 2. Index composite pour les agrégations financières isolées par tenant (espace portail comptable)
            $table->index(['organization_id', 'status', 'total'], 'invoices_org_status_total_idx');

            // 3. Index pour le tri rapide des factures récentes globales (admin)
            $table->index(['issue_date', 'id'], 'invoices_issue_date_id_idx');

            // 4. Index pour le tri rapide des factures récentes par tenant (portail)
            $table->index(['organization_id', 'issue_date', 'id'], 'invoices_org_issue_date_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_status_total_idx');
            $table->dropIndex('invoices_org_status_total_idx');
            $table->dropIndex('invoices_issue_date_id_idx');
            $table->dropIndex('invoices_org_issue_date_id_idx');
        });
    }
};
