<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remplace la contrainte unique stricte (organization_id, name) par un index unique partiel
     * afin de permettre la recréation d'un client portant le nom d'un client archivé (soft-deleted).
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'name']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX clients_org_name_active_unique ON clients (organization_id, name) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Restaure la contrainte unique d'origine.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS clients_org_name_active_unique');

        Schema::table('clients', function (Blueprint $table) {
            $table->unique(['organization_id', 'name']);
        });
    }
};
