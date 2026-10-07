<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Clients\ClientDTO;
use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

interface ClientRepositoryInterface
{
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
}
