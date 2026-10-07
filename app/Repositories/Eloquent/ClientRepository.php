<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Clients\ClientDTO;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class ClientRepository implements ClientRepositoryInterface
{
    /**
     * Recherche des clients par mot-clé (nom, email, nif) pour l'autocomplétion.
     *
     * @return Collection<int, Client>
     */
    public function search(int $organizationId, ?string $term = null, int $limit = 10): Collection
    {
        $query = Client::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name');

        if ($term !== null && trim($term) !== '') {
            $cleanTerm = trim($term);
            $likeOperator = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'like';

            $query->where(function ($q) use ($cleanTerm, $likeOperator) {
                $q->where('name', $likeOperator, "%{$cleanTerm}%")
                  ->orWhere('email', $likeOperator, "%{$cleanTerm}%")
                  ->orWhere('tax_number', $likeOperator, "%{$cleanTerm}%");
            });
        }

        return $query->limit($limit)->get();
    }

    /**
     * Recherche un client par ID et organisation.
     */
    public function findById(int $id, int $organizationId): ?Client
    {
        return Client::query()
            ->where('organization_id', $organizationId)
            ->where('id', $id)
            ->first();
    }

    /**
     * Recherche un client par son nom exact pour une organisation.
     */
    public function findByName(string $name, int $organizationId): ?Client
    {
        return Client::query()
            ->where('organization_id', $organizationId)
            ->where('name', $name)
            ->first();
    }

    /**
     * Crée un nouveau client pour une organisation.
     */
    public function create(int $organizationId, ClientDTO $dto): Client
    {
        return Client::create(array_merge(
            $dto->toArray(),
            ['organization_id' => $organizationId]
        ));
    }

    /**
     * Met à jour les informations d'un client.
     */
    public function update(Client $client, ClientDTO $dto): Client
    {
        $client->update($dto->toArray());

        return $client->fresh();
    }
}
