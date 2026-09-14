<?php

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface
{
    /**
     * Récupère les factures paginées pour un utilisateur (multi-tenancy)
     * avec préchargement (Eager Loading) des relations pour éviter le problème N+1.
     */
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    /**
     * Recherche une facture par son identifiant avec ses relations chargées.
     */
    public function findById(int $id): ?Invoice;

    /**
     * Crée une facture en base de données.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Invoice;
}
