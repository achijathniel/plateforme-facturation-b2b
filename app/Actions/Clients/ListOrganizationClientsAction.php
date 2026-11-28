<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\DTOs\Clients\ClientFilterData;
use App\Models\Client;
use App\Models\User;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

final readonly class ListOrganizationClientsAction
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    /**
     * Retourne la liste paginée des clients de l'organisation de l'utilisateur.
     */
    public function execute(User $user, ClientFilterData $filter): LengthAwarePaginator
    {
        Gate::forUser($user)->authorize('viewAny', Client::class);

        return $this->clientRepository->paginateForOrganization(
            (int) $user->organization_id,
            $filter
        );
    }
}
