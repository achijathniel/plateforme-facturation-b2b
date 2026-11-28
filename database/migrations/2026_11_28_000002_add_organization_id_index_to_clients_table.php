<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conserve un index complet sur clients.organization_id (y compris lignes archivées)
     * après le passage de la contrainte unique sur un index partiel WHERE deleted_at IS NULL.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->index('organization_id', 'clients_organization_id_idx');
        });
    }

    /**
     * Supprime l'index ajouté sur clients.organization_id.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_organization_id_idx');
        });
    }
};
