<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Clients\ClientDTO;
use App\DTOs\Clients\ClientFilterData;
use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ClientRepositoryInterface
{
    /**
     * Paginer les clients d'une organisation avec recherche, tri et statistiques agrégées.
     */
    public function paginateForOrganization(int $organizationId, ClientFilterData $filter): LengthAwarePaginator;

    /**
     * Recherche des clients par nom, email ou NIF pour une organisation donnée.
     *
     * @return Collection<int, Client>
     */
    public function search(int $organizationId, ?string $term = null, int $limit = 10): Collection;

    /**
     * Recherche un client par son identifiant et son organisation.
     */
    public function findById(int $id, int $organizationId): ?Client;

    /**
     * Charge la fiche complète d'un client avec ses statistiques financières et ses dernières factures.
     */
    public function findWithStats(int $id, int $organizationId): ?Client;

    /**
     * Recherche un client par son nom exact pour une organisation.
     */
    public function findByName(string $name, int $organizationId): ?Client;

    /**
     * Crée un nouveau client pour une organisation.
     */
    public function create(int $organizationId, ClientDTO $dto): Client;

    /**
     * Met à jour les informations d'un client.
     */
    public function update(Client $client, ClientDTO $dto): Client;

    /**
     * Archive un client (suppression douce).
     */
    public function delete(Client $client): bool;
}
