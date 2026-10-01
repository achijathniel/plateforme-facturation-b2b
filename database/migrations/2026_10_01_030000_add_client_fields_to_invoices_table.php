<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('client_name')->nullable()->after('invoice_number');
            $table->string('client_email')->nullable()->after('client_name');
            $table->text('client_address')->nullable()->after('client_email');
            $table->string('client_tax_number', 50)->nullable()->after('client_address');
            $table->string('client_phone', 30)->nullable()->after('client_tax_number');

            $table->index('client_name', 'idx_invoices_client_name');
            $table->index('client_email', 'idx_invoices_client_email');
        });

        // Rétrocompatibilité : assigner un client par défaut aux factures historiques
        DB::table('invoices')
            ->whereNull('client_name')
            ->update([
                'client_name'  => 'Client Entreprise Partenaire',
                'client_email' => 'contact@client-partenaire.com',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_client_name');
            $table->dropIndex('idx_invoices_client_email');

            $table->dropColumn([
                'client_name',
                'client_email',
                'client_address',
                'client_tax_number',
                'client_phone',
            ]);
        });
    }
};
