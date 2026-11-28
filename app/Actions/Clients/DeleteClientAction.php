<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\User;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\Gate;

final readonly class DeleteClientAction
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    /**
     * Archive un client (suppression douce) après vérification des droits RBAC et multi-tenancy.
     */
    public function execute(User $user, Client $client): bool
    {
        Gate::forUser($user)->authorize('delete', $client);

        return $this->clientRepository->delete($client);
    }
}
