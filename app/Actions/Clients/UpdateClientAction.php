<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\DTOs\Clients\ClientDTO;
use App\Models\Client;
use App\Models\User;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateClientAction
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    /**
     * Met à jour les coordonnées d'un client après vérification des droits RBAC et multi-tenancy.
     */
    public function execute(User $user, Client $client, ClientDTO $dto): Client
    {
        Gate::forUser($user)->authorize('update', $client);

        return $this->clientRepository->update($client, $dto);
    }
}
