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
        // 1. Modification de la valeur par défaut pour les futures insertions
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('collaborator')->change();
        });

        // 2. Migration des utilisateurs existants avec l'ancien rôle 'client' vers 'collaborator'
        DB::table('users')
            ->where('role', 'client')
            ->orderBy('id')
            ->chunk(1000, function ($users) {
                $ids = $users->pluck('id')->toArray();
                DB::table('users')
                    ->whereIn('id', $ids)
                    ->update(['role' => 'collaborator']);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restauration de l'ancienne valeur par défaut
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client')->change();
        });

        // 2. Rollback des données vers 'client'
        DB::table('users')
            ->where('role', 'collaborator')
            ->orderBy('id')
            ->chunk(1000, function ($users) {
                $ids = $users->pluck('id')->toArray();
                DB::table('users')
                    ->whereIn('id', $ids)
                    ->update(['role' => 'client']);
            });
    }
};
