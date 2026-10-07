<?php

declare(strict_types=1);

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
        // 1. Création de la table 'clients' (annuaire client par organisation)
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Rattachement multi-tenancy à l'organisation
            $table->foreignId('organization_id')
                  ->constrained('organizations')
                  ->cascadeOnDelete();

            // Coordonnées et identité du client B2B
            $table->string('name')->index();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Unicité : un client avec le même nom ne peut pas être dupliqué au sein d'une même organisation
            $table->unique(['organization_id', 'name']);
        });

        // 2. Ajout de la clé étrangère 'client_id' dans la table 'invoices'
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('client_id')
                  ->nullable()
                  ->after('organization_id')
                  ->constrained('clients')
                  ->nullOnDelete();
        });

        // 3. Migration des données existantes : peupler 'clients' à partir des factures déjà enregistrées
        $distinctClients = DB::table('invoices')
            ->select('organization_id', 'client_name')
            ->selectRaw('MAX(client_email) as client_email')
            ->selectRaw('MAX(client_address) as client_address')
            ->selectRaw('MAX(client_tax_number) as client_tax_number')
            ->selectRaw('MAX(client_phone) as client_phone')
            ->whereNotNull('client_name')
            ->groupBy('organization_id', 'client_name')
            ->get();

        foreach ($distinctClients as $clientData) {
            $clientId = DB::table('clients')->insertGetId([
                'organization_id' => $clientData->organization_id,
                'name'            => $clientData->client_name,
                'email'           => $clientData->client_email,
                'address'         => $clientData->client_address,
                'tax_number'      => $clientData->client_tax_number,
                'phone'           => $clientData->client_phone,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('invoices')
                ->where('organization_id', $clientData->organization_id)
                ->where('client_name', $clientData->client_name)
                ->update(['client_id' => $clientId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });

        Schema::dropIfExists('clients');
    }
};
